<?php
/**
 * Convert all Serbian translations from Cyrillic to Latin in translations.php
 */

function cyrillicToLatin($text) {
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

$file = __DIR__ . '/translations.php';
$content = file_get_contents($file);

echo "=== Converting Serbian translations from Cyrillic to Latin ===\n\n";

// Find all single-quoted strings in Serbian section
// Pattern: 'cyrillic text' => 'cyrillic translation'

$converted = 0;
$pattern = "/'([А-Яа-яЂђЉљЊњЋћЏџ][^']*)'\\s*=>\\s*'([А-Яа-яЂђЉљЊњЋћЏџ][^']*)'/u";

$content = preg_replace_callback($pattern, function($matches) use (&$converted) {
    $key = $matches[1];
    $value = $matches[2];

    // Convert value to Latin (key stays as is - it's English)
    $latinValue = cyrillicToLatin($value);

    $converted++;

    // Return the replacement
    return "'" . $key . "' => '" . $latinValue . "'";
}, $content);

// Also convert standalone values that might be in comments or other places
$pattern2 = "/=>\\s*'([А-Яа-яЂђЉљЊњЋћЏџ][^']*)'/u";

$content = preg_replace_callback($pattern2, function($matches) use (&$converted) {
    $value = $matches[1];
    $latinValue = cyrillicToLatin($value);
    return "=> '" . $latinValue . "'";
}, $content);

echo "Converted $converted Serbian Cyrillic translations to Latin\n\n";

// Save to a new file first for safety
$backupFile = __DIR__ . '/translations.php.backup';
$newFile = __DIR__ . '/translations-latin.php';

file_put_contents($backupFile, file_get_contents($file));
file_put_contents($newFile, $content);

echo "✅ Backup saved to: translations.php.backup\n";
echo "✅ New file saved to: translations-latin.php\n\n";

echo "Please review translations-latin.php and if correct, run:\n";
echo "mv translations-latin.php translations.php\n";
