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

final class ObjBadUtf8 extends Obj
{
    use ObjTrait;
    use ReadOnlyPropertiesTrait;

    public function __construct()
    {
        parent::__construct(
            'JosÃ© GarcÃ­a',
            'josÃ©@example.com',
            'BrÃ©sil',
            'SÃ£o Paulo',
            'password'
        );
    }
}
