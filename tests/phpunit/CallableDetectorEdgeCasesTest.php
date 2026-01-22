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
use PHPUnit\Framework\TestCase;

final class CallableDetectorEdgeCasesTest extends TestCase
{
    public function testIsValidCallableWithArrayCallable(): void
    {
        $callable = [self::class, 'staticMethod'];

        $this->assertTrue(CallableDetector::isValidCallable($callable));
    }

    public function testIsValidCallableWithInvokableObject(): void
    {
        $callable = new class {
            // @phpstan-ignore missingType.iterableValue
            public function __invoke(string $string, ?array $options = null): ?string
            {
                return null;
            }
        };

        $this->assertTrue(CallableDetector::isValidCallable($callable));
    }

    // @phpstan-ignore missingType.iterableValue
    public static function staticMethod(string $string, ?array $options = null): ?string
    {
        return null;
    }
}
