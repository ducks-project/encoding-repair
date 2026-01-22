<?php

/**
 * Example: Using CharsetProcessor service directly.
 */

declare(strict_types=1);

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

require_once __DIR__ . '/../vendor/autoload.php';

// Create a new processor instance
$processor = new CharsetProcessor();

// Fluent API: Queue multiple transcoders
$processor
    ->queueTranscoders(new IconvTranscoder())
    ->addEncodings('SHIFT_JIS', 'EUC-JP')
    ->resetDetectors()
    ->queueDetectors(new MbStringDetector());

// Use the processor
$data = 'Café';
$utf8 = $processor->toUtf8($data);

echo "Original: {$data}\n";
echo "UTF-8: {$utf8}\n";

// Get current encodings
print_r($processor->getEncodings());

// Create another instance with different configuration
$processor2 = new CharsetProcessor();
$processor2->removeEncodings('UTF-16', 'UTF-32');

// Both instances are independent
echo "Processor 1 encodings: " . count($processor->getEncodings()) . "\n";
echo "Processor 2 encodings: " . count($processor2->getEncodings()) . "\n";
