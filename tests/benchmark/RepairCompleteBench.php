<?php

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair\Tests\benchmark;

use Ducks\Component\EncodingRepair\CharsetHelper;

/**
 * @Groups({"repaircomplete"})
 *
 * @BeforeMethods({"setUp"})
 *
 * @Revs(1000)
 *
 * @Iterations(10)
 *
 * @Warmup(2)
 */
final class RepairCompleteBench
{
    private string $corrupted;
    private string $corruptedLong;

    public function setUp(): void
    {
        $this->corrupted = 'FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football';
        $this->corruptedLong = str_repeat($this->corrupted . ' ', 100);
    }

    public function benchCharsetHelper(): void
    {
        CharsetHelper::repair($this->corrupted);
    }

    public function benchCharsetHelperLong(): void
    {
        CharsetHelper::repair($this->corruptedLong);
    }

    public function benchPregReplaceOnly(): void
    {
        $fixed = \preg_replace('/\xC3\x82/', '', $this->corrupted);
        $fixed = \preg_replace('/\xC3\x83\xC2([\xA0-\xFF])/', "\xC3$1", $fixed);
    }

    public function benchPregReplaceOnlyLong(): void
    {
        $fixed = \preg_replace('/\xC3\x82/', '', $this->corruptedLong);
        $fixed = \preg_replace('/\xC3\x83\xC2([\xA0-\xFF])/', "\xC3$1", $fixed);
    }
}
