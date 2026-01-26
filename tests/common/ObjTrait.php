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

namespace Ducks\Component\EncodingRepair\Tests\common;

trait ObjTrait
{
    /**
     * @return object
     * @psalm-return \stdClass&object{name: string, email:string, secret: string}
     */
    public static function getValue(): object
    {
        $instance = new self();

        /** @var \stdClass&object{name: string, email:string, secret: string} $object */
        $object = (object) $instance->__toArray();

        return $object;
    }
}
