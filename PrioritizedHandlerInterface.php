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

namespace Ducks\Component\EncodingRepair;

/**
 * Interface for handler to get a getPriority method.
 *
 * @psalm-api
 */
interface PrioritizedHandlerInterface
{
    /**
     * Get handler priority (higher = executed first).
     *
     * @return int Priority value
     */
    public function getPriority(): int;
}
