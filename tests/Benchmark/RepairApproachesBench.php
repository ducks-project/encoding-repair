<?php

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair\Tests\Benchmark;

use Ducks\Component\EncodingRepair\CharsetHelper;

/**
 * @Groups({"repairappraches"})
 *
 * @BeforeMethods({"setUp"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class RepairApproachesBench
{
    private string $corrupted;
    private string $corruptedLong;

    public function setUp(): void
    {
        $this->corrupted = 'FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football';

        // Long string for realistic testing
        $this->corruptedLong = str_repeat($this->corrupted . ' ', 100);
    }

    public function benchCurrentApproach(): void
    {
        CharsetHelper::repair($this->corrupted);
    }

    public function benchCurrentApproachLong(): void
    {
        CharsetHelper::repair($this->corruptedLong);
    }

    public function benchPatternOnlyApproach(): void
    {
        $this->patternOnlyRepair($this->corrupted);
    }

    public function benchPatternOnlyApproachLong(): void
    {
        $this->patternOnlyRepair($this->corruptedLong);
    }

    public function benchPortableUtf8Approach(): void
    {
        $this->portableUtf8Repair($this->corrupted);
    }

    public function benchPortableUtf8ApproachLong(): void
    {
        $this->portableUtf8Repair($this->corruptedLong);
    }

    public function benchPregReplaceApproach(): void
    {
        $this->pregReplaceRepair($this->corrupted);
    }

    public function benchPregReplaceApproachLong(): void
    {
        $this->pregReplaceRepair($this->corruptedLong);
    }

    private function patternOnlyRepair(string $value): string
    {
        // Pure pattern replacement without encoding conversion
        $fixed = \str_replace("\xC3\x82", '', $value);
        $fixed = \str_replace("\xC3\x83\xC2\xA9", "\xC3\xA9", $fixed);
        $fixed = \str_replace("\xC3\x83\xC2\xA8", "\xC3\xA8", $fixed);
        $fixed = \str_replace("\xC3\x83\xC2\xAA", "\xC3\xAA", $fixed);
        $fixed = \str_replace("\xC3\x83\xC2\xA0", "\xC3\xA0", $fixed);

        return $fixed;
    }

    private function portableUtf8Repair(string $value): string
    {
        // Portable UTF-8 approach: recursive utf8_decode
        $fixed = $value;
        $maxDepth = 5;

        for ($i = 0; $i < $maxDepth; $i++) {
            if (!mb_check_encoding($fixed, 'UTF-8')) {
                break;
            }

            $test = @utf8_decode($fixed);
            if ($test === $fixed || strlen($test) >= strlen($fixed)) {
                break;
            }

            $fixed = $test;
        }

        // Convert back to UTF-8 if needed
        if (!mb_check_encoding($fixed, 'UTF-8')) {
            $fixed = utf8_encode($fixed);
        }

        return $fixed;
    }

    private function pregReplaceRepair(string $value): string
    {
        // Using preg_replace for pattern matching
        $fixed = preg_replace('/\xC3\x82/', '', $value);
        $fixed = preg_replace('/\xC3\x83\xC2([\xA0-\xFF])/', "\xC3$1", $fixed);

        return $fixed;
    }
}
