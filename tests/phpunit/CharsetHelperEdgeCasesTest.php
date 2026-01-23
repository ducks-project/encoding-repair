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

use Ducks\Component\EncodingRepair\CharsetHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CharsetHelperEdgeCasesTest extends TestCase
{
    public function testRegisterTranscoderWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transcoder must be an instance of TranscoderInterface or a callable');

        /** @var mixed $invalidTranscoder */
        $invalidTranscoder = 'not a transcoder';
        CharsetHelper::registerTranscoder($invalidTranscoder);
    }

    public function testRegisterDetectorWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Detector must be an instance of DetectorInterface or a callable');

        /** @var mixed $invalidDetector */
        $invalidDetector = 123;
        CharsetHelper::registerDetector($invalidDetector);
    }

    public function testRegisterTranscoderWithCallable(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = static fn (string $data, string $to, string $from, ?array $options = null): ?string => 'custom-' . $data;

        CharsetHelper::registerTranscoder($callable, 200);

        $result = CharsetHelper::toUtf8('test');
        $this->assertIsString($result);
    }

    public function testRegisterDetectorWithCallable(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = static fn (string $string, ?array $options): ?string => 'UTF-8';

        CharsetHelper::registerDetector($callable, 300);

        $encoding = CharsetHelper::detect('test');
        $this->assertSame('UTF-8', $encoding);
    }
}
