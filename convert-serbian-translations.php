<?php
/**
 * Convert Serbian translations from Cyrillic to Latin
 */

function cyrillicToLatin($text) {
    if (!is_string($text)) return $text;

    $map = [
        'А' => 'A', 'а' => 'a',
        'Б' => 'B', 'б' => 'b',
        'В' => 'V', 'в' => 'v',
        'Г' => 'G', 'г' => 'g',
        'Д' => 'D', 'д' => 'd',
        'Ђ' => 'Đ', 'ђ' => 'đ',
        'Е' => 'E', 'е' => 'e',
        'Ж' => 'Ž', 'ж' => 'ž',
        'З' => 'Z', 'з' => 'z',
        'И' => 'I', 'и' => 'i',
        'Ј' => 'J', 'ј' => 'j',
        'К' => 'K', 'к' => 'k',
        'Л' => 'L', 'л' => 'l',
        'Љ' => 'Lj', 'љ' => 'lj',
        'М' => 'M', 'м' => 'm',
        'Н' => 'N', 'н' => 'n',
        'Њ' => 'Nj', 'њ' => 'nj',
        'О' => 'O', 'о' => 'o',
        'П' => 'P', 'п' => 'p',
        'Р' => 'R', 'р' => 'r',
        'С' => 'S', 'с' => 's',
        'Т' => 'T', 'т' => 't',
        'Ћ' => 'Ć', 'ћ' => 'ć',
        'У' => 'U', 'у' => 'u',
        'Ф' => 'F', 'ф' => 'f',
        'Х' => 'H', 'х' => 'h',
        'Ц' => 'C', 'ц' => 'c',
        'Ч' => 'Č', 'ч' => 'č',
        'Џ' => 'Dž', 'џ' => 'dž',
        'Ш' => 'Š', 'ш' => 'š',
    ];

    return strtr($text, $map);
}

function convertArrayValues(&$array) {
    $count = 0;

    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $count += convertArrayValues($value);
        } elseif (is_string($value)) {
            $latin = cyrillicToLatin($value);
            if ($latin !== $value) {
                $value = $latin;
                $count++;
            }
        }
    }

    return $count;
}

echo "=== Loading translations.php ===\n\n";

$translations = require __DIR__ . '/translations.php';

if (!isset($translations['sr'])) {
    die("Serbian translations not found\n");
}

echo "Found Serbian translations section\n\n";

echo "=== Converting Cyrillic to Latin ===\n\n";

$converted = convertArrayValues($translations['sr']);

echo "Converted $converted values\n\n";

// Show some examples
echo "Sample conversions:\n";
if (isset($translations['sr']['categories']['Toys'])) {
    echo "  Toys: {$translations['sr']['categories']['Toys']}\n";
}
if (isset($translations['sr']['categories']['Dresses'])) {
    echo "  Dresses: {$translations['sr']['categories']['Dresses']}\n";
}
if (isset($translations['sr']['params']['Color'])) {
    echo "  Color: {$translations['sr']['params']['Color']}\n";
}
if (isset($translations['sr']['values']['Evening'])) {
    echo "  Evening: {$translations['sr']['values']['Evening']}\n";
}

// Generate PHP code
echo "\n=== Generating new translations.php ===\n\n";

$output = "<?php\n";
$output .= "/**\n";
$output .= " * Translations for categories, parameters and values\n";
$output .= " * Serbian translations converted to Latin script\n";
$output .= " */\n\n";
$output .= "return " . var_export($translations, true) . ";\n";

file_put_contents(__DIR__ . '/translations-latin-new.php', $output);

echo "✅ New file saved to: translations-latin-new.php\n";
echo "\nPlease review the file and if correct, replace translations.php\n";
