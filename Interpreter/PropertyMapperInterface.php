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
 * Contract for custom object property mapping.
 *
 * Allows fine-grained control over which properties are transcoded.
 *
 * @psalm-api
 */
interface PropertyMapperInterface
{
    /**
     * Map object properties using the transcoder callback.
     *
     * @param object $object Object to map
     * @param callable $transcoder Transcoding callback
     * @param array<string, mixed> $options Processing options
     *
     * @return object Cloned object with mapped properties
     */
    public function map(object $object, callable $transcoder, array $options): object;
}
