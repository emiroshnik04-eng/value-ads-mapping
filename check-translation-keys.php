<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking how translations work in production ===\n\n";

// The key insight: в production Yii2 использует Yii::t() для переводов
// Например: Yii::t('app', 'black') вернет перевод для 'black'

// Проверим какие ключи используются для значений параметров
$testValues = [
    'black',
    'white',
    'red',
    'blue',
    'Silver Iphone',
    'Blue-1',
];

echo "Translation keys that should be used:\n\n";

foreach ($testValues as $value) {
    echo "  Key: '$value'\n";
    echo "    - This is the param_value.value from database\n";
    echo "    - Translation system uses this as the key\n";
    echo "    - translations.php should have: '$value' => 'Перевод'\n";
    echo "\n";
}

echo "\n=== How it works in production ===\n";
echo "1. Database stores English value: param_value.value = 'black'\n";
echo "2. Yii2 i18n system: Yii::t('app', 'black') \n";
echo "3. HybridMessageSource looks for translation:\n";
echo "   a) First in local files (messages/ru/app.php)\n";
echo "   b) Then in translation-microservice API\n";
echo "4. Returns: 'Черный'\n";

echo "\n\n=== How it works in admin tools ===\n";
echo "1. We use TranslationHelper::translateValue('black', 12)\n";
echo "2. Gets language for country: ru\n";
echo "3. Looks up in translations.php: \$translations['ru']['values']['black']\n";
echo "4. Returns: 'Черный'\n";

echo "\n\n=== The problem ===\n";
echo "We added translations to translations.php, but they might not cover all values.\n";
echo "We need to either:\n";
echo "  1. Add ALL value translations to translations.php (time consuming)\n";
echo "  2. Use translation-microservice API (requires integration)\n";
echo "  3. Check if translation files exist in catalog-microservice\n";

echo "\n\n=== Checking for existing translation files ===\n";
$translationDirs = [
    dirname(__DIR__, 2) . '/messages',
    dirname(__DIR__, 3) . '/messages',
    __DIR__ . '/../../messages',
];

foreach ($translationDirs as $dir) {
    if (is_dir($dir)) {
        echo "Found translation directory: $dir\n";
        $langs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($langs as $lang) {
            echo "  Language: " . basename($lang) . "\n";
            $files = glob($lang . '/*.php');
            foreach ($files as $file) {
                echo "    - " . basename($file) . "\n";
            }
        }
    }
}
