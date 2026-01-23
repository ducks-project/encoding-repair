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
 * Interpreter for array data with recursive processing.
 *
 * @final
 */
final class ArrayInterpreter implements TypeInterpreterInterface
{
    private InterpreterChain $chain;

    public function __construct(InterpreterChain $chain)
    {
        $this->chain = $chain;
    }

    /**
     * @inheritDoc
     */
    public function supports($data): bool
    {
        return \is_array($data);
    }

    /**
     * @inheritDoc
     */
    public function interpret($data, callable $transcoder, array $options)
    {
        /**
         * @var array<array-key, mixed> $data
         *
         * @psalm-suppress MissingClosureReturnType
         * @psalm-suppress MissingClosureParamType
         */
        return \array_map(
            fn ($item) => $this->chain->interpret($item, $transcoder, $options),
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 50;
    }
}
