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

namespace Ducks\Component\EncodingRepair\Tests\benchmark;

use Ducks\Component\EncodingRepair\CharsetHelper;

/**
 * @Groups({"json"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class JsonBench
{
    /**
     * @var array<string,string>
     */
    private array $data;

    private string $json;

    /**
     * @var array<string,string>
     */
    private array $largeData;

    public function __construct()
    {
        $this->data = [
            'name' => 'Gérard',
            'city' => 'São Paulo',
            'description' => 'Développeur',
        ];

        $this->json = '{"name":"Gérard","city":"São Paulo","description":"Développeur"}';

        $this->largeData = [];
        for ($i = 0; $i < 100; $i++) {
            $this->largeData["field_{$i}"] = "Café résumé {$i}";
        }
    }

    /**
     * @Subject
     */
    public function benchSafeJsonEncode(): void
    {
        CharsetHelper::safeJsonEncode($this->data);
    }

    /**
     * @Subject
     */
    public function benchSafeJsonDecode(): void
    {
        CharsetHelper::safeJsonDecode($this->json, true);
    }

    /**
     * @Subject
     */
    public function benchSafeJsonEncodeLarge(): void
    {
        CharsetHelper::safeJsonEncode($this->largeData);
    }

    /**
     * @Subject
     */
    public function benchNativeJsonEncode(): void
    {
        \json_encode($this->data);
    }

    /**
     * @Subject
     */
    public function benchNativeJsonDecode(): void
    {
        \json_decode($this->json, true);
    }
}
