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
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;

echo "=== PregMatchDetector Usage Examples ===\n\n";

// Example 1: Basic usage with CharsetHelper
echo "1. Basic usage with CharsetHelper:\n";
CharsetHelper::registerDetector(new PregMatchDetector());

$asciiString = 'Hello World';
$utf8String = 'Café';
$isoString = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');

echo "   ASCII detection: " . CharsetHelper::detect($asciiString) . "\n";
echo "   UTF-8 detection: " . CharsetHelper::detect($utf8String) . "\n";
echo "   ISO-8859-1 detection: " . (CharsetHelper::detect($isoString) ?? 'null (fallback to MbStringDetector)') . "\n\n";

// Example 2: Using with CharsetProcessor
echo "2. Using with CharsetProcessor:\n";
$processor = new CharsetProcessor();
$processor->registerDetector(new PregMatchDetector());

$data = ['name' => 'Test', 'city' => 'São Paulo'];
$encoding = $processor->detect(\json_encode($data));
echo "   Detected encoding: {$encoding}\n\n";

// Example 3: Performance comparison
echo "3. Performance comparison (10,000 detections):\n";

$testStrings = [
    'ASCII only string',
    'UTF-8 with accents: café',
    'UTF-8 with emoji: 👋',
];

// With PregMatchDetector (priority 150)
$processor1 = new CharsetProcessor();
$processor1->registerDetector(new PregMatchDetector());

$start = \microtime(true);
for ($i = 0; $i < 10000; $i++) {
    foreach ($testStrings as $str) {
        $processor1->detect($str);
    }
}
$time1 = \microtime(true) - $start;

// Without PregMatchDetector (only MbStringDetector)
$processor2 = new CharsetProcessor();
$processor2->resetDetectors(); // Remove PregMatchDetector

$start = \microtime(true);
for ($i = 0; $i < 10000; $i++) {
    foreach ($testStrings as $str) {
        $processor2->detect($str);
    }
}
$time2 = \microtime(true) - $start;

echo "   With PregMatchDetector: " . \number_format($time1 * 1000, 2) . " ms\n";
echo "   Without PregMatchDetector: " . \number_format($time2 * 1000, 2) . " ms\n";
echo "   Improvement: " . \number_format((($time2 - $time1) / $time2) * 100, 1) . "%\n\n";

// Example 4: Chain of Responsibility demonstration
echo "4. Chain of Responsibility demonstration:\n";
$processor = new CharsetProcessor();
$processor->registerDetector(new PregMatchDetector());

$testCases = [
    ['string' => 'Hello', 'expected' => 'ASCII'],
    ['string' => 'Café', 'expected' => 'UTF-8'],
    ['string' => \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8'), 'expected' => 'ISO-8859-1 (via MbStringDetector)'],
];

foreach ($testCases as $test) {
    $detected = $processor->detect($test['string']);
    echo "   String: '{$test['string']}' → Detected: {$detected} (Expected: {$test['expected']})\n";
}

echo "\n=== Examples completed ===\n";
