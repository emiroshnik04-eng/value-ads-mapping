<?php
/**
 * Convert Serbian Cyrillic to Latin script
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

// Test conversions
$tests = [
    'Хаљине' => 'Haljine',
    'Играчке' => 'Igračke',
    'Електроника' => 'Elektronika',
    'Возила' => 'Vozila',
    'Непокретности' => 'Nekretnine',
    'Женска одећа' => 'Ženska odeća',
    'Мушка одећа' => 'Muška odeća',
    'Обућа' => 'Obuća',
    'Додаци' => 'Dodaci',
    'Стање' => 'Stanje',
    'Достава' => 'Dostava',
    'Бренд' => 'Brend',
    'Боја' => 'Boja',
    'Величина' => 'Veličina',
    'Материјал' => 'Materijal',
    'Цена' => 'Cena',
    'Тип' => 'Tip',
    'Модел' => 'Model',
    'Годиште' => 'Godište',
    'Километража' => 'Kilometraža',
    'Мењач' => 'Menjač',
    'Врста горива' => 'Vrsta goriva',
    'Тип каросерије' => 'Tip karoserije',
    'Собе' => 'Sobe',
    'Површина' => 'Površina',
    'Спрат' => 'Sprat',
    'Сезона' => 'Sezona',
    'Тип хаљине' => 'Tip haljine',
    'Дужина' => 'Dužina',
    'Бренд одеће' => 'Brend odeće',
    'Шара' => 'Šara',
    'Рукави' => 'Rukavi',
    'Подстава' => 'Podstava',
    'Порекло' => 'Poreklo',
    'Декор' => 'Dekor',
    'Ново' => 'Novo',
    'Коришћено' => 'Korišćeno',
    'Одлично' => 'Odlično',
    'Добро' => 'Dobro',
    'Да' => 'Da',
    'Не' => 'Ne',
    'Лично преузимање' => 'Lično preuzimanje',
    'Плаћена достава' => 'Plaćena dostava',
    'Бесплатна достава' => 'Besplatna dostava',
    'Мануелни' => 'Manuelni',
    'Аутоматски' => 'Automatski',
    'Бензин' => 'Benzin',
    'Дизел' => 'Dizel',
    'Вечерња' => 'Večernja',
    'Коктел' => 'Koktel',
    'Оверсајз' => 'Oversajz',
    'Други тип хаљине' => 'Drugi tip haljine',
    'Свакодневна' => 'Svakodnevna',
    'Пословна' => 'Poslovna',
    'Макси' => 'Maksi',
    'Мини' => 'Mini',
    'Миди' => 'Midi',
    'Уска хаљина' => 'Uska haljina',
    'Широка хаљина' => 'Široka haljina',
    'Хаљина русалка' => 'Haljina rusalk a',
    'Хаљина за труднице' => 'Haljina za trudnice',
];

echo "=== Testing Serbian Cyrillic to Latin conversion ===\n\n";

$correct = 0;
$total = count($tests);

foreach ($tests as $cyrillic => $expectedLatin) {
    $converted = cyrillicToLatin($cyrillic);
    $match = ($converted === $expectedLatin) ? '✅' : '❌';

    if ($converted === $expectedLatin) {
        $correct++;
    }

    echo "$match '$cyrillic' → '$converted' (expected: '$expectedLatin')\n";
}

echo "\nAccuracy: $correct/$total (" . round($correct/$total * 100, 1) . "%)\n";
