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

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Cache\ArrayCache;

echo "=== Detection Caching Examples ===\n\n";

// Example 1: Enable default caching (InternalArrayCache)
echo "1. Enable default caching:\n";
$processor = new CharsetProcessor();
$processor->enableDetectionCache(); // Uses InternalArrayCache by default

$testStrings = ['Hello', 'Café', 'Hello', 'Café']; // Repeated strings

$start = microtime(true);
foreach ($testStrings as $str) {
    $encoding = $processor->detect($str);
    echo "   '{$str}' → {$encoding}\n";
}
$time = (microtime(true) - $start) * 1000;
echo "   Time: " . number_format($time, 2) . " ms (cache hits on repeated strings)\n\n";

// Example 2: Use custom PSR-16 cache with TTL
echo "2. Use ArrayCache with custom TTL:\n";
$processor2 = new CharsetProcessor();
$cache = new ArrayCache();
$processor2->enableDetectionCache($cache, 7200); // 2 hours TTL

$encoding = $processor2->detect('Hello World');
echo "   First detection: {$encoding}\n";

$encoding = $processor2->detect('Hello World');
echo "   Second detection: {$encoding} (from cache)\n\n";

// Example 3: Disable caching
echo "3. Disable caching:\n";
$processor3 = new CharsetProcessor();
$processor3->enableDetectionCache();
echo "   Cache enabled\n";

$processor3->disableDetectionCache();
echo "   Cache disabled\n\n";

// Example 4: Clear cache
echo "4. Clear cache:\n";
$processor4 = new CharsetProcessor();
$processor4->enableDetectionCache();

$processor4->detect('Test 1');
$processor4->detect('Test 2');
echo "   Detected 2 strings (cached)\n";

$processor4->clearDetectionCache();
echo "   Cache cleared\n\n";

// Example 5: Performance comparison
echo "5. Performance comparison (1000 detections with repeated strings):\n";

$testData = array_merge(
    array_fill(0, 250, 'Hello'),
    array_fill(0, 250, 'Café'),
    array_fill(0, 250, 'Test'),
    array_fill(0, 250, 'World')
);

// Without cache
$processor5a = new CharsetProcessor();
$start = microtime(true);
foreach ($testData as $str) {
    $processor5a->detect($str);
}
$timeWithoutCache = (microtime(true) - $start) * 1000;

// With cache
$processor5b = new CharsetProcessor();
$processor5b->enableDetectionCache();
$start = microtime(true);
foreach ($testData as $str) {
    $processor5b->detect($str);
}
$timeWithCache = (microtime(true) - $start) * 1000;

echo "   Without cache: " . number_format($timeWithoutCache, 2) . " ms\n";
echo "   With cache: " . number_format($timeWithCache, 2) . " ms\n";
$improvement = (($timeWithoutCache - $timeWithCache) / $timeWithoutCache) * 100;
echo "   Improvement: " . number_format($improvement, 1) . "%\n\n";

echo "=== Examples completed ===\n";
