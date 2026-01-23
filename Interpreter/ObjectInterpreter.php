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
 * Interpreter for object data with custom property mapping support.
 *
 * @final
 */
final class ObjectInterpreter implements TypeInterpreterInterface
{
    /**
     * @var array<string, PropertyMapperInterface>
     */
    private array $mappers = [];

    private InterpreterChain $chain;

    public function __construct(InterpreterChain $chain)
    {
        $this->chain = $chain;
    }

    /**
     * Register a custom property mapper for a specific class.
     *
     * @param string $className Fully qualified class name
     * @param PropertyMapperInterface $mapper Property mapper instance
     *
     * @return void
     */
    public function registerMapper(string $className, PropertyMapperInterface $mapper): void
    {
        $this->mappers[$className] = $mapper;
    }

    /**
     * @inheritDoc
     */
    public function supports($data): bool
    {
        return \is_object($data);
    }

    /**
     * @inheritDoc
     */
    public function interpret($data, callable $transcoder, array $options)
    {
        /** @var object $data */
        $class = \get_class($data);

        if (isset($this->mappers[$class])) {
            return $this->mappers[$class]->map($data, $transcoder, $options);
        }

        return $this->defaultMapping($data, $transcoder, $options);
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 30;
    }

    /**
     * Default object mapping: clone and process all public properties.
     *
     * @param object $data Object to process
     * @param callable $transcoder Transcoding callback
     * @param array<string, mixed> $options Processing options
     *
     * @return object Cloned object with processed properties
     */
    private function defaultMapping(object $data, callable $transcoder, array $options): object
    {
        $copy = clone $data;
        $properties = \get_object_vars($copy);

        /** @var mixed $value */
        foreach ($properties as $key => $value) {
            $copy->$key = $this->chain->interpret($value, $transcoder, $options);
        }

        return $copy;
    }
}
