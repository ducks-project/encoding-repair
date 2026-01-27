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

use stdClass;

/**
 * @property-read string $name
 * @property-read string $email
 * @property-read string $country
 * @property-read string $city
 * @property-read string $secret
 */
class Obj
{
    use ReadOnlyPropertiesTrait;

    protected string $name;

    protected string $email;

    protected string $country;

    protected string $city;

    protected string $secret;

    protected string $description;

    public function __construct(
        string $name,
        string $email,
        string $country,
        string $city,
        string $secret,
        string $description = ''
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->country = $country;
        $this->city = $city;
        $this->description = $description;
        $this->secret = $secret;
    }

    /**
     * Return object as an array.
     *
     * @return array<string, string>
     */
    public function __toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city,
            'secret' => $this->secret,
            'description' => $this->description,
        ];
    }

    /**
     * Return expected values ordered by attempts in constructor.
     *
     * @return array<string,string>
     */
    public static function getExpected(): array
    {
        return [
            'name' => 'José García',
            'email' => 'josé@example.com',
            'country' => 'Brésil',
            'city' => 'São Paulo',
            'secret' => 'passwörd!',
            'description' => 'Description en français',
        ];
    }

    /**
     * @return object
     *
     * @psalm-return stdClass&object{name: string, email:string, secret: string}
     */
    public static function getValue(): object
    {
        $args = \array_values(static::getExpected());
        $instance = new self(...$args);

        /** @var stdClass&object{name: string, email:string, secret: string} $object */
        $object = (object) $instance->__toArray();

        return $object;
    }
}
