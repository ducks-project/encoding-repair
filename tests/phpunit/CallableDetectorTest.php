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

use Ducks\Component\EncodingRepair\Detector\CallableDetector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CallableDetectorTest extends TestCase
{
    public function testConstructorAcceptsValidCallable(): void
    {
        /**
         * @param array<string, mixed>|null $options
         */
        $callable = fn (string $string, ?array $options = null): string => 'UTF-8';

        $detector = new CallableDetector($callable, 50);

        $this->assertSame(50, $detector->getPriority());
        $this->assertTrue($detector->isAvailable());
    }

    public function testDetectReturnsCallableResult(): void
    {
        /**
         * @param array<string, mixed>|null $options
         */
        $callable = fn (string $string, ?array $options = null): string => 'ISO-8859-1';

        $detector = new CallableDetector($callable, 50);
        $result = $detector->detect('test', []);

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testDetectReturnsNull(): void
    {
        /**
         * @param array<string, mixed>|null $options
         */
        $callable = fn (string $string, ?array $options = null): ?string => null;

        $detector = new CallableDetector($callable, 50);
        $result = $detector->detect('test', []);

        $this->assertNull($result);
    }

    public function testDetectThrowsOnInvalidReturnType(): void
    {
        /**
         * @var callable(string, array<string, mixed>|null): (string|null) $callable
         */
        $callable = fn (string $string, ?array $options = null) => 123;

        // @phpstan-ignore argument.type
        $detector = new CallableDetector($callable, 50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return string|null');

        $detector->detect('test', []);
    }

    public function testConstructorThrowsOnInvalidParameterCount(): void
    {
        $callable = fn (): string => 'UTF-8';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must accept at least 1 parameter');

        new CallableDetector($callable, 50);
    }
}
