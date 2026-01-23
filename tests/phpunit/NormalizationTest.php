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

namespace Ducks\Component\EncodingRepair\Tests\phpunit;

use Ducks\Component\EncodingRepair\CharsetProcessor;
use PHPUnit\Framework\TestCase;

final class NormalizationTest extends TestCase
{
    public function testNormalizationWithCombiningCharacters(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // Create a string with combining characters (NFD form)
        // e + combining acute accent = é
        $nfd = "e\u{0301}"; // NFD: e + ́
        
        // Convert to UTF-8 with normalization enabled (default)
        $result = $processor->toCharset($nfd, 'UTF-8', 'UTF-8', ['normalize' => true]);

        // Should be normalized to NFC form (single character é)
        $expected = "\u{00E9}"; // NFC: é
        
        $this->assertSame($expected, $result);
        $this->assertSame(2, strlen($result)); // NFC é is 2 bytes in UTF-8
    }

    public function testNormalizationDisabled(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // Create a string with combining characters (NFD form)
        $nfd = "e\u{0301}"; // NFD: e + ́
        
        // Convert to UTF-8 with normalization disabled
        $result = $processor->toCharset($nfd, 'UTF-8', 'UTF-8', ['normalize' => false]);

        // Should remain in NFD form
        $this->assertSame($nfd, $result);
        $this->assertSame(3, strlen($result)); // NFD is 3 bytes (e + combining)
    }

    public function testNormalizationOnlyForUtf8(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // Create a string with combining characters
        $nfd = "e\u{0301}";
        
        // Convert to ISO-8859-1 (normalization should not apply)
        $result = $processor->toCharset($nfd, 'ISO-8859-1', 'UTF-8', ['normalize' => true]);

        // Should be converted but not normalized (ISO doesn't support combining chars)
        $this->assertIsString($result);
    }

    public function testNormalizationWithRealWorldExample(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // Real-world example: "Café" with combining accent
        $nfd = "Cafe\u{0301}"; // NFD form
        
        $result = $processor->toUtf8($nfd, 'UTF-8', ['normalize' => true]);

        // Should be normalized to NFC
        $expected = "Café"; // NFC form
        
        $this->assertSame($expected, $result);
    }

    public function testNormalizationDefaultBehavior(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // Create a string with combining characters
        $nfd = "e\u{0301}";
        
        // Convert without specifying normalize option (should default to true)
        $result = $processor->toUtf8($nfd, 'UTF-8');

        // Should be normalized by default
        $expected = "\u{00E9}";
        
        $this->assertSame($expected, $result);
    }

    public function testNormalizationWithFalsyValues(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // Create a string with combining characters
        $nfd = "e\u{0301}";
        $expected = "\u{00E9}"; // NFC form
        
        // Only boolean false should disable normalization (strict comparison)
        $result = $processor->toUtf8($nfd, 'UTF-8', ['normalize' => false]);
        $this->assertSame($nfd, $result, 'Boolean false should disable normalization');
        
        // Other falsy values should NOT disable normalization (they are not boolean false)
        $falsyValues = [0, '0', '', null];
        
        foreach ($falsyValues as $falsyValue) {
            $result = $processor->toUtf8($nfd, 'UTF-8', ['normalize' => $falsyValue]);
            
            // Should BE normalized (falsy values are not boolean false)
            $this->assertSame($expected, $result, "Should normalize for falsy value: " . var_export($falsyValue, true));
        }
    }
}
