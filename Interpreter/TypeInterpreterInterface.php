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

use Ducks\Component\EncodingRepair\PrioritizedHandlerInterface;

/**
 * Contract for type-specific data interpreters.
 *
 * Implements Strategy pattern for optimized transcoding based on data type.
 *
 * @psalm-api
 */
interface TypeInterpreterInterface extends PrioritizedHandlerInterface
{
    /**
     * Check if this interpreter supports the given data type.
     *
     * @param mixed $data Data to check
     *
     * @return bool True if supported
     */
    public function supports($data): bool;

    /**
     * Interpret and process the data using the transcoder callback.
     *
     * @param mixed $data Data to process
     * @param callable $transcoder Transcoding callback
     * @param array<string, mixed> $options Processing options
     *
     * @return mixed Processed data
     */
    public function interpret($data, callable $transcoder, array $options);
}
