<?php

/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\CharsetProcessor;

echo "=== Encoding Validation Examples ===\n\n";

// Example 1: Validate UTF-8 strings
echo "1. Validate UTF-8 strings:\n";
$utf8String = 'Café résumé';
if (CharsetHelper::is($utf8String, 'UTF-8')) {
    echo "   ✓ String is valid UTF-8: {$utf8String}\n";
} else {
    echo "   ✗ String is NOT UTF-8\n";
}

// Example 2: Detect non-UTF-8 strings
echo "\n2. Detect non-UTF-8 strings:\n";
$isoString = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
if (CharsetHelper::is($isoString, 'UTF-8')) {
    echo "   ✓ String is UTF-8\n";
} else {
    echo "   ✗ String is NOT UTF-8 (detected: " . CharsetHelper::detect($isoString) . ")\n";
}

// Example 3: Conditional conversion
echo "\n3. Conditional conversion (avoid unnecessary conversions):\n";
$data = 'São Paulo';
if (!CharsetHelper::is($data, 'UTF-8')) {
    echo "   Converting to UTF-8...\n";
    $data = CharsetHelper::toUtf8($data);
} else {
    echo "   ✓ Already UTF-8, no conversion needed\n";
}

// Example 4: Encoding aliases (CP1252 = ISO-8859-1)
echo "\n4. Encoding aliases:\n";
$cp1252String = \mb_convert_encoding('€', 'CP1252', 'UTF-8');
if (CharsetHelper::is($cp1252String, 'ISO-8859-1')) {
    echo "   ✓ CP1252 string recognized as ISO-8859-1 (alias)\n";
}
if (CharsetHelper::is($cp1252String, 'CP1252')) {
    echo "   ✓ CP1252 string recognized as CP1252\n";
}

// Example 5: Case-insensitive encoding names
echo "\n5. Case-insensitive encoding names:\n";
if (CharsetHelper::is('test', 'utf-8')) {
    echo "   ✓ Lowercase 'utf-8' works\n";
}
if (CharsetHelper::is('test', 'UTF-8')) {
    echo "   ✓ Uppercase 'UTF-8' works\n";
}

// Example 6: Using with CharsetProcessor service
echo "\n6. Using with CharsetProcessor service:\n";
$processor = new CharsetProcessor();
$mixedData = [
    'utf8' => 'Café',
    'iso' => \mb_convert_encoding('Thé', 'ISO-8859-1', 'UTF-8'),
];

foreach ($mixedData as $key => $value) {
    if ($processor->is($value, 'UTF-8')) {
        echo "   ✓ {$key}: Already UTF-8\n";
    } else {
        echo "   ✗ {$key}: Not UTF-8, converting...\n";
        $mixedData[$key] = $processor->toUtf8($value, CharsetProcessor::AUTO);
    }
}

// Example 7: Validation before database insert
echo "\n7. Database insert validation:\n";
$userInput = 'Gérard Müller';
if (CharsetHelper::is($userInput, 'UTF-8')) {
    echo "   ✓ Safe to insert into UTF-8 database\n";
    // $db->insert('users', ['name' => $userInput]);
} else {
    echo "   ✗ Converting before insert...\n";
    $userInput = CharsetHelper::toUtf8($userInput, CharsetHelper::AUTO);
    // $db->insert('users', ['name' => $userInput]);
}

// Example 8: API response validation
echo "\n8. API response validation:\n";
$apiData = ['name' => 'José', 'city' => 'São Paulo'];
$allUtf8 = true;
foreach ($apiData as $key => $value) {
    if (!\is_string($value) || !CharsetHelper::is($value, 'UTF-8')) {
        $allUtf8 = false;
        break;
    }
}
if ($allUtf8) {
    echo "   ✓ All data is UTF-8, safe for JSON encoding\n";
    $json = \json_encode($apiData);
} else {
    echo "   ✗ Converting data before JSON encoding...\n";
    $apiData = CharsetHelper::toUtf8($apiData, CharsetHelper::AUTO);
    $json = \json_encode($apiData);
}

echo "\n=== Done ===\n";
