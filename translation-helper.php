<?php
/**
 * Translation helper for admin tools
 *
 * Uses fast_message_country table from database for real translations
 * Falls back to local translations.php file if database translation not found
 */

class TranslationHelper
{
    private static $translations = null;
    private static $pdo = null;
    private static $countryLanguageMap = [
        1 => 'ru',   // Kazakhstan -> Russian
        11 => 'sr',  // Serbia -> Serbian
        12 => 'ru',  // Kyrgyzstan -> Russian
        13 => 'az',  // Azerbaijan -> Azerbaijani
        14 => 'pl',  // Poland -> Polish
        18 => 'ro',  // Moldova -> Romanian
        21 => 'uz',  // Uzbekistan -> Uzbek
        // Add more countries as needed
    ];

    /**
     * Get database connection
     */
    private static function getDb()
    {
        if (self::$pdo === null) {
            try {
                $config = require __DIR__ . '/db-config.php';
                self::$pdo = new PDO(
                    "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
                    $config['user'],
                    $config['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (Exception $e) {
                error_log("TranslationHelper DB connection error: " . $e->getMessage());
                return null;
            }
        }
        return self::$pdo;
    }

    /**
     * Load translations from file (fallback only)
     */
    private static function loadTranslations()
    {
        if (self::$translations === null) {
            self::$translations = require __DIR__ . '/translations.php';
        }
        return self::$translations;
    }

    /**
     * Get language code for country
     *
     * @param int $countryId
     * @return string Language code (ru, ky, pl, etc.)
     */
    public static function getLanguageForCountry($countryId)
    {
        return self::$countryLanguageMap[$countryId] ?? 'en';
    }

    /**
     * Get param translation directly from param table (name_sr, name_ru, etc.)
     *
     * @param string $paramName Parameter name
     * @param int $countryId Country ID
     * @return string|null Translation or null if not found
     */
    private static function getParamFromDb($paramName, $countryId)
    {
        try {
            $db = self::getDb();
            if ($db === null) {
                return null;
            }

            $lang = self::getLanguageForCountry($countryId);
            $columnName = 'name_' . $lang;

            $stmt = $db->prepare("
                SELECT $columnName as translation
                FROM param
                WHERE name = :name
                AND $columnName IS NOT NULL
                AND $columnName != ''
                AND $columnName != name
                LIMIT 1
            ");
            $stmt->execute([':name' => $paramName]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get category translation directly from category table (name_sr, name_ru, etc.)
     *
     * @param string $categoryName Category name
     * @param int $countryId Country ID
     * @return string|null Translation or null if not found
     */
    private static function getCategoryFromDb($categoryName, $countryId)
    {
        try {
            $db = self::getDb();
            if ($db === null) {
                return null;
            }

            $lang = self::getLanguageForCountry($countryId);
            $columnName = 'name_' . $lang;

            $stmt = $db->prepare("
                SELECT $columnName as translation
                FROM category
                WHERE name = :name
                AND $columnName IS NOT NULL
                AND $columnName != ''
                AND $columnName != name
                LIMIT 1
            ");
            $stmt->execute([':name' => $categoryName]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Translate parameter name
     *
     * @param string $paramName Original parameter name (e.g. "Condition")
     * @param int $countryId Country ID
     * @return string Translated name or original if translation not found
     */
    public static function translateParam($paramName, $countryId)
    {
        // First: Try database (param.name_sr, param.name_ru, etc.)
        $dbTranslation = self::getParamFromDb($paramName, $countryId);
        if ($dbTranslation && $dbTranslation !== $paramName) {
            return $dbTranslation;
        }

        // Fallback: Use local translations.php file
        $translations = self::loadTranslations();
        $lang = self::getLanguageForCountry($countryId);

        if (isset($translations[$lang]['params'][$paramName])) {
            return $translations[$lang]['params'][$paramName];
        }

        // Return original if no translation found
        return $paramName;
    }

    /**
     * Translate parameter value
     *
     * @param string $valueName Original value name (e.g. "New")
     * @param int $countryId Country ID
     * @return string Translated value or original if translation not found
     */
    public static function translateValue($valueName, $countryId)
    {
        // TEMPORARY FIX: Use only translations.php to restore functionality
        // Database lookup disabled until we verify fast_message_country table exists

        // Use local translations.php file
        $translations = self::loadTranslations();
        $lang = self::getLanguageForCountry($countryId);

        if (isset($translations[$lang]['values'][$valueName])) {
            return $translations[$lang]['values'][$valueName];
        }

        // Return original if no translation found
        return $valueName;
    }

    /**
     * Translate category name
     *
     * @param string $categoryName Original category name (e.g. "Baby monitors")
     * @param int $countryId Country ID
     * @return string Translated category or original if translation not found
     */
    public static function translateCategory($categoryName, $countryId)
    {
        // First: Try database (category.name_sr, category.name_ru, etc.)
        $dbTranslation = self::getCategoryFromDb($categoryName, $countryId);
        if ($dbTranslation && $dbTranslation !== $categoryName) {
            return $dbTranslation;
        }

        // Fallback: Use local translations.php file
        $translations = self::loadTranslations();
        $lang = self::getLanguageForCountry($countryId);

        if (isset($translations[$lang]['categories'][$categoryName])) {
            return $translations[$lang]['categories'][$categoryName];
        }

        // Return original if no translation found
        return $categoryName;
    }

    /**
     * Bulk translate parameters
     *
     * @param array $params Array of parameters with 'name' field
     * @param int $countryId Country ID
     * @return array Parameters with added 'name_translated' field
     */
    public static function translateParams($params, $countryId)
    {
        foreach ($params as &$param) {
            $param['name_translated'] = self::translateParam($param['name'], $countryId);
        }
        return $params;
    }

    /**
     * Bulk translate values
     *
     * @param array $values Array of values with 'name' field
     * @param int $countryId Country ID
     * @return array Values with added 'name_translated' field
     */
    public static function translateValues($values, $countryId)
    {
        foreach ($values as &$value) {
            $value['name_translated'] = self::translateValue($value['name'], $countryId);
        }
        return $values;
    }
}
