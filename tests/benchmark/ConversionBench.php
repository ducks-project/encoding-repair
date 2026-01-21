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
use stdClass;

/**
 * @Groups({"conversion"})
 * @Revs(1000)
 * @Iterations(5)
 * @Warmup(2)
 */
final class ConversionBench
{
    private string $utf8String = 'Café résumé avec des accents éèêë';
    private array $smallArray;
    private array $largeArray;
    private object $object;

    public function __construct()
    {
        $this->smallArray = [
            'name' => 'José García',
            'city' => 'São Paulo',
            'country' => 'Brésil',
        ];
        
        $this->largeArray = [];
        for ($i = 0; $i < 100; $i++) {
            $this->largeArray["field_{$i}"] = "Café résumé {$i}";
        }
        
        $this->object = new stdClass();
        $this->object->name = 'José García';
        $this->object->email = 'test@example.com';
    }

    /**
     * @Subject
     */
    public function benchSimpleStringToUtf8(): void
    {
        CharsetHelper::toUtf8($this->utf8String, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchSimpleStringToIso(): void
    {
        CharsetHelper::toIso($this->utf8String, CharsetHelper::ENCODING_UTF8);
    }

    /**
     * @Subject
     */
    public function benchSmallArrayConversion(): void
    {
        CharsetHelper::toUtf8($this->smallArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchLargeArrayConversion(): void
    {
        CharsetHelper::toUtf8($this->largeArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchObjectConversion(): void
    {
        CharsetHelper::toUtf8($this->object, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchAutoDetection(): void
    {
        CharsetHelper::toCharset($this->utf8String, CharsetHelper::ENCODING_UTF8, CharsetHelper::AUTO);
    }
}
