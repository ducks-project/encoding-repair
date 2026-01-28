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
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\CompositeCleaner;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;

echo "=== CompositeCleaner Examples ===\n\n";

// Example 1: Group related cleaners with Pipeline
echo "1. Group BOM + HTML cleaners (Pipeline):\n";
$bomHtmlGroup = new CompositeCleaner(
    new PipelineStrategy(),
    150,
    new BomCleaner(),
    new HtmlEntityCleaner()
);

$processor = new CharsetProcessor();
$processor->registerCleaner($bomHtmlGroup);

$data = "\xEF\xBB\xBF" . 'Caf&eacute;';
$result = $processor->toUtf8($data, 'UTF-8', ['clean' => true]);
echo "   Input:  BOM + 'Caf&eacute;'\n";
echo "   Output: '{$result}'\n";
echo "   Both cleaners in composite applied\n\n";

// Example 2: Fallback group with FirstMatch
echo "2. Fallback group (FirstMatch):\n";
$fallbackGroup = new CompositeCleaner(
    new FirstMatchStrategy(),
    100,
    new MbScrubCleaner(),
    new PregMatchCleaner()
);

$processor2 = new CharsetProcessor();
$processor2->registerCleaner($fallbackGroup);

$data2 = "Simple string";
$result2 = $processor2->toUtf8($data2, 'UTF-8', ['clean' => true]);
echo "   Input:  '{$data2}'\n";
echo "   Output: '{$result2}'\n";
echo "   Only MbScrubCleaner executed (first match)\n\n";

// Example 3: Mixed usage - Composite + Individual cleaners
echo "3. Mixed usage (Composite + Individual):\n";
$processor3 = new CharsetProcessor();

// High priority composite for critical cleaners
$criticalGroup = new CompositeCleaner(
    new PipelineStrategy(),
    200,
    new BomCleaner(),
    new HtmlEntityCleaner()
);
$processor3->registerCleaner($criticalGroup);

// Lower priority individual cleaner
$processor3->registerCleaner(new WhitespaceCleaner(), 50);

$data3 = "\xEF\xBB\xBF" . 'Caf&eacute;   with   spaces';
$result3 = $processor3->toUtf8($data3, 'UTF-8', ['clean' => true]);
echo "   Input:  BOM + 'Caf&eacute;   with   spaces'\n";
echo "   Output: '{$result3}'\n";
echo "   Composite (BOM+HTML) executed first, then Whitespace\n\n";

// Example 4: Nested composites
echo "4. Nested composites:\n";
$innerGroup = new CompositeCleaner(
    new PipelineStrategy(),
    100,
    new BomCleaner(),
    new HtmlEntityCleaner()
);

$outerGroup = new CompositeCleaner(
    new PipelineStrategy(),
    150,
    $innerGroup,
    new WhitespaceCleaner()
);

$processor4 = new CharsetProcessor();
$processor4->registerCleaner($outerGroup);

$data4 = "\xEF\xBB\xBF" . 'Caf&eacute;   spaces';
$result4 = $processor4->toUtf8($data4, 'UTF-8', ['clean' => true]);
echo "   Input:  BOM + 'Caf&eacute;   spaces'\n";
echo "   Output: '{$result4}'\n";
echo "   Nested: (BOM+HTML) then Whitespace\n\n";

// Example 5: Reusable cleaner groups
echo "5. Reusable cleaner groups:\n";

// Define reusable groups
$webContentGroup = new CompositeCleaner(
    new PipelineStrategy(),
    150,
    new BomCleaner(),
    new HtmlEntityCleaner(),
    new WhitespaceCleaner()
);

$basicCleaningGroup = new CompositeCleaner(
    new FirstMatchStrategy(),
    100,
    new MbScrubCleaner(),
    new PregMatchCleaner()
);

// Use in different processors
$webProcessor = new CharsetProcessor();
$webProcessor->registerCleaner($webContentGroup);

$apiProcessor = new CharsetProcessor();
$apiProcessor->registerCleaner($basicCleaningGroup);

echo "   Web processor: Uses webContentGroup (BOM+HTML+Whitespace)\n";
echo "   API processor: Uses basicCleaningGroup (MbScrub/PregMatch fallback)\n\n";

echo "=== Examples Complete ===\n";
