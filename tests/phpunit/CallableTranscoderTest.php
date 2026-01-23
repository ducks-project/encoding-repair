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

use Ducks\Component\EncodingRepair\Transcoder\CallableTranscoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CallableTranscoderTest extends TestCase
{
    public function testConstructorAcceptsValidCallable(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = fn (string $data, string $to, string $from, ?array $options = null): ?string => 'result';

        $transcoder = new CallableTranscoder($callable, 50);

        $this->assertSame(50, $transcoder->getPriority());
        $this->assertTrue($transcoder->isAvailable());
    }

    public function testTranscodeReturnsCallableResult(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = fn (string $data, string $to, string $from, ?array $options = null): ?string => $data . '_transcoded';

        $transcoder = new CallableTranscoder($callable, 50);
        $result = $transcoder->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame('test_transcoded', $result);
    }

    public function testTranscodeReturnsNull(): void
    {
        $callable = fn (string $data, string $to, string $from, ?array $options = null): ?string => null;

        $transcoder = new CallableTranscoder($callable, 50);
        $result = $transcoder->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertNull($result);
    }

    public function testTranscodeThrowsOnInvalidReturnType(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = fn (string $data, string $to, string $from, ?array $options = null) => 123;

        // @phpstan-ignore argument.type
        $transcoder = new CallableTranscoder($callable, 50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return string|null');

        $transcoder->transcode('test', 'UTF-8', 'ISO-8859-1', []);
    }

    public function testConstructorThrowsOnInvalidParameterCount(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = fn (string $data, string $to): ?string => 'result';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must accept at least 3 parameters');

        new CallableTranscoder($callable, 50);
    }

    public function testIsValidCallableWithArrayCallable(): void
    {
        $callable = [self::class, 'staticMethod'];

        $this->assertTrue(CallableTranscoder::isValidCallable($callable));
    }

    public function testIsValidCallableWithInvokableObject(): void
    {
        $callable = new class () {
            // @phpstan-ignore missingType.iterableValue
            public function __invoke(string $data, string $to, string $from, ?array $options = null): ?string
            {
                return null;
            }
        };

        $this->assertTrue(CallableTranscoder::isValidCallable($callable));
    }

    // @phpstan-ignore missingType.iterableValue
    public static function staticMethod(string $data, string $to, string $from, ?array $options = null): ?string
    {
        return null;
    }
}
