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

namespace Ducks\Component\EncodingRepair\Tests\Benchmark;

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Tests\Common\ObjBadUtf8;
use Ducks\Component\EncodingRepair\Tests\Common\ObjIso;
use Ducks\Component\EncodingRepair\Tests\Common\Word;

/**
 * Benchmark for type interpreter performance.
 */
final class InterpreterBench
{
    private CharsetProcessor $processor;
    private CharsetProcessor $processorWithMapper;

    private string $simpleText;

    private object $largeObject;

    private object $simpleObject;

    private array $simpleArray;

    public function __construct()
    {
        $this->processor = new CharsetProcessor();
        $this->processorWithMapper = new CharsetProcessor();

        // Register custom mapper that only processes 2 properties
        $mapper = new class () implements PropertyMapperInterface {
            public function map(object $object, callable $transcoder, array $options): object
            {
                $copy = clone $object;
                $copy->name = $transcoder($object->name);
                $copy->email = $transcoder($object->email);
                // Other 48 properties are NOT processed
                return $copy;
            }
        };

        $this->processorWithMapper->registerPropertyMapper(\stdClass::class, $mapper);

        $this->simpleText = \mb_convert_encoding(Word::getGoodUtf8Word(), 'ISO-8859-1', 'UTF-8');

        $this->simpleObject = ObjIso::getValue();

        $this->simpleArray = (array) $this->simpleObject;

        // Create object with 50 properties (only 2 need conversion)
        $this->largeObject = ObjBadUtf8::getValue();
        for ($i = 0; $i < 48; $i++) {
            $prop = "prop{$i}";
            $this->largeObject->$prop = "value{$i}"; // Already UTF-8, no conversion needed
        }
    }

    /**
     * @Revs(1000)
     *
     * @Iterations(5)
     */
    public function benchObjectWithoutMapper(): void
    {
        $this->processor->toUtf8($this->largeObject, CharsetProcessor::ENCODING_ISO);
    }

    /**
     * @Revs(1000)
     *
     * @Iterations(5)
     */
    public function benchObjectWithMapper(): void
    {
        $this->processorWithMapper->toUtf8($this->largeObject, CharsetProcessor::ENCODING_ISO);
    }

    /**
     * @Revs(1000)
     *
     * @Iterations(5)
     */
    public function benchSimpleString(): void
    {
        $this->processor->toUtf8($this->simpleText, CharsetProcessor::ENCODING_ISO);
    }

    /**
     * @Revs(1000)
     *
     * @Iterations(5)
     */
    public function benchSimpleArray(): void
    {
        $this->processor->toUtf8($this->simpleArray, CharsetProcessor::ENCODING_ISO);
    }
}
