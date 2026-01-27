<?php

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair\Tests\benchmark;

use Ducks\Component\EncodingRepair\CharsetHelper;
use PhpBench\Attributes as Bench;
use voku\helper\UTF8;

/**
 * @BeforeMethods({"setUp"})
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

    #[Bench\Revs(1000)]
    #[Bench\Iterations(10)]
    public function benchCharsetHelper(): void
    {
        CharsetHelper::repair($this->corrupted);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(10)]
    public function benchCharsetHelperLong(): void
    {
        CharsetHelper::repair($this->corruptedLong);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(10)]
    public function benchPortableUtf8(): void
    {
        UTF8::fix_utf8($this->corrupted);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(10)]
    public function benchPortableUtf8Long(): void
    {
        UTF8::fix_utf8($this->corruptedLong);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(10)]
    public function benchPregReplaceOnly(): void
    {
        $fixed = \preg_replace('/\xC3\x82/', '', $this->corrupted);
        $fixed = \preg_replace('/\xC3\x83\xC2([\xA0-\xFF])/', "\xC3$1", $fixed);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(10)]
    public function benchPregReplaceOnlyLong(): void
    {
        $fixed = \preg_replace('/\xC3\x82/', '', $this->corruptedLong);
        $fixed = \preg_replace('/\xC3\x83\xC2([\xA0-\xFF])/', "\xC3$1", $fixed);
    }
}
