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

use Ducks\Component\EncodingRepair\CharsetProcessorInterface;
use stdClass;

abstract class ObjEncoded extends Obj
{
    use ObjTrait;
    use ReadOnlyPropertiesTrait;

    final public function __construct()
    {
        parent::__construct(...$this->getConstructArgs());
    }

    /**
     * Return constructor arguments values as it was expeted.
     *
     * @return list<string>
     */
    private function getConstructArgs(): array
    {
        $args = [];
        $expected = $this->getExpected();
        foreach ($expected as $value) {
            $args[] = $this->encode($value);
        }

        return $args;
    }

    private function encode(string $value): string
    {
        return \mb_convert_encoding(
            $value,
            $this->getObjectEncoding(),
            CharsetProcessorInterface::ENCODING_UTF8
        ) ?: $value;
    }

    /**
     * @return object
     *
     * @psalm-return stdClass&object{name: string, email:string, secret: string}
     */
    public static function getValue(): object
    {
        $instance = new static();

        /** @var stdClass&object{name: string, email:string, secret: string} $object */
        $object = (object) $instance->__toArray();

        return $object;
    }

    abstract public function getObjectEncoding(): string;
}
