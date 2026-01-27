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

namespace Ducks\Component\EncodingRepair\Tests\Common;

trait ReadOnlyPropertiesTrait
{
    /**
     * Magic getter.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (\property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }
}
