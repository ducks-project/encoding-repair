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

namespace Ducks\Component\EncodingRepair\Tests\Phpunit;

use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use PHPUnit\Framework\TestCase;

final class StringInterpreterTest extends TestCase
{
    public function testSupports(): void
    {
        $interpreter = new StringInterpreter();

        $this->assertTrue($interpreter->supports('test'));
        $this->assertFalse($interpreter->supports(123));
        $this->assertFalse($interpreter->supports([]));
        $this->assertFalse($interpreter->supports(new \stdClass()));
    }

    public function testInterpret(): void
    {
        $interpreter = new StringInterpreter();

        $transcoder = static fn (string $data): string => \strtoupper($data);

        $result = $interpreter->interpret('test', $transcoder, []);

        $this->assertSame('TEST', $result);
    }

    public function testGetPriority(): void
    {
        $interpreter = new StringInterpreter();

        $this->assertSame(100, $interpreter->getPriority());
    }
}
