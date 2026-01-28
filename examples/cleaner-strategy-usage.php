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

use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use Ducks\Component\EncodingRepair\Cleaner\TaggedStrategy;
use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;

echo "=== Cleaner Strategy Examples ===\n\n";

// Example 1: Pipeline Strategy (Default - Middleware Pattern)
echo "1. Pipeline Strategy (applies all cleaners successively):\n";
$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner());
$chain->register(new HtmlEntityCleaner());

$data = "\xEF\xBB\xBF" . 'Caf&eacute; &amp; Restaurant'; // BOM + HTML entities
$result = $chain->clean($data, 'UTF-8', []);
echo "   Input:  " . bin2hex(substr($data, 0, 3)) . " + 'Caf&eacute; &amp; Restaurant'\n";
echo "   Output: '{$result}'\n";
echo "   Both cleaners applied: BOM removed, then HTML entities decoded\n\n";

// Example 2: First Match Strategy (Chain of Responsibility)
echo "2. First Match Strategy (stops at first success):\n";
$chain = new CleanerChain(new FirstMatchStrategy());
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());

$data = "Valid UTF-8 string";
$result = $chain->clean($data, 'UTF-8', []);
echo "   Input:  '{$data}'\n";
echo "   Output: '{$result}'\n";
echo "   Only MbScrubCleaner executed (stopped at first success)\n\n";

// Example 3: Tagged Strategy (Selective Execution)
echo "3. Tagged Strategy (selective execution based on tags):\n";
$chain = new CleanerChain(new TaggedStrategy(['bom', 'html']));
$chain->register(new BomCleaner(), null, ['bom']);
$chain->register(new HtmlEntityCleaner(), null, ['html']);
$chain->register(new WhitespaceCleaner(), null, ['whitespace']); // Will be ignored

$data = "\xEF\xBB\xBF" . 'Caf&eacute;  with   spaces';
$result = $chain->clean($data, 'UTF-8', []);
echo "   Input:  BOM + 'Caf&eacute;  with   spaces'\n";
echo "   Output: '{$result}'\n";
echo "   Only BOM and HTML cleaners executed (whitespace cleaner ignored)\n\n";

// Example 4: Dynamic Strategy Switching
echo "4. Dynamic Strategy Switching:\n";
$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner());
$chain->register(new HtmlEntityCleaner());

$data = "\xEF\xBB\xBF" . 'Caf&eacute;';

echo "   a) With PipelineStrategy:\n";
$result1 = $chain->clean($data, 'UTF-8', []);
echo "      Output: '{$result1}' (both cleaners applied)\n";

echo "   b) Switch to FirstMatchStrategy:\n";
$chain->setStrategy(new FirstMatchStrategy());
$result2 = $chain->clean($data, 'UTF-8', []);
echo "      Output: '{$result2}' (only BomCleaner applied)\n\n";

// Example 5: Complex Scenario - Multiple Issues
echo "5. Complex Scenario (BOM + HTML entities + whitespace):\n";
$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner(), 150);
$chain->register(new HtmlEntityCleaner(), 100);
$chain->register(new WhitespaceCleaner(), 50);

$data = "\xEF\xBB\xBF" . 'Caf&eacute;   &amp;   Restaurant  ';
$result = $chain->clean($data, 'UTF-8', []);
echo "   Input:  BOM + 'Caf&eacute;   &amp;   Restaurant  '\n";
echo "   Output: '{$result}'\n";
echo "   All cleaners applied in priority order: BOM → HTML → Whitespace\n\n";

// Example 6: Performance Optimization with FirstMatchStrategy
echo "6. Performance Optimization:\n";
$chain = new CleanerChain(new FirstMatchStrategy());
$chain->register(new MbScrubCleaner(), 100);
$chain->register(new PregMatchCleaner(), 50);

$iterations = 10000;
$data = "Simple ASCII string";

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $chain->clean($data, 'UTF-8', []);
}
$duration = microtime(true) - $start;

echo "   Cleaned {$iterations} strings in " . number_format($duration * 1000, 2) . "ms\n";
echo "   FirstMatchStrategy stops at MbScrubCleaner (no unnecessary processing)\n\n";

echo "=== Examples Complete ===\n";
