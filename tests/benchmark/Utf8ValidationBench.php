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

namespace Ducks\Component\EncodingRepair\Tests\Benchmark;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

/**
 * Benchmark UTF-8 validation methods.
 *
 * @Groups({"utf8validation"})
 *
 * @Revs(10000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 *
 * @final
 */
final class Utf8ValidationBench
{
    private string $validUtf8;
    private string $invalidUtf8;

    public function __construct()
    {
        $this->validUtf8 = 'Café, thé, crème brûlée, São Paulo, Москва, 北京, 🚀';
        $this->invalidUtf8 = "\xC3\x28"; // Invalid UTF-8 sequence
    }

    public function benchPregMatchValid(): void
    {
        false !== @\preg_match('//u', $this->validUtf8);
    }

    public function benchMbCheckEncodingValid(): void
    {
        \mb_check_encoding($this->validUtf8, 'UTF-8');
    }

    public function benchPregMatchInvalid(): void
    {
        false !== @\preg_match('//u', $this->invalidUtf8);
    }

    public function benchMbCheckEncodingInvalid(): void
    {
        \mb_check_encoding($this->invalidUtf8, 'UTF-8');
    }
}
