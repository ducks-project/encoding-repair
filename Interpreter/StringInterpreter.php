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

namespace Ducks\Component\EncodingRepair\Interpreter;

/**
 * Interpreter for string data.
 *
 * @final
 */
final class StringInterpreter implements TypeInterpreterInterface
{
    /**
     * @inheritDoc
     */
    public function supports($data): bool
    {
        return \is_string($data);
    }

    /**
     * @inheritDoc
     */
    public function interpret($data, callable $transcoder, array $options)
    {
        return $transcoder($data);
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 100;
    }
}
