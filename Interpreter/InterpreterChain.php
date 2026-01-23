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

use Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait;

/**
 * Chain of Responsibility for type interpreters.
 *
 * @final
 */
final class InterpreterChain
{
    /**
     * @use ChainOfResponsibilityTrait<TypeInterpreterInterface>
     */
    use ChainOfResponsibilityTrait {
        ChainOfResponsibilityTrait::register as chainRegister;
        ChainOfResponsibilityTrait::unregister as chainUnregister;
    }

    /**
     * Register a interpreter with optional priority override.
     *
     * @param TypeInterpreterInterface $interpreter Detector instance
     * @param int|null $priority Priority override (null = use detector's default)
     *
     * @return void
     */
    public function register(TypeInterpreterInterface $interpreter, ?int $priority = null): void
    {
        $this->chainRegister($interpreter, $priority);
    }

    /**
     * Unregister a interpreter from the chain.
     *
     * @param TypeInterpreterInterface $interpreter Detector instance to remove
     *
     * @return void
     */
    public function unregister(TypeInterpreterInterface $interpreter): void
    {
        $this->chainUnregister($interpreter);
    }

    /**
     * Interpret data using the first matching interpreter.
     *
     * @param mixed $data Data to interpret
     * @param callable $transcoder Transcoding callback
     * @param array<string, mixed> $options Processing options
     *
     * @return mixed Interpreted data
     */
    public function interpret($data, callable $transcoder, array $options)
    {
        // Clone the queue to avoid consuming it
        $queue = clone $this->getSplPriorityQueue();

        foreach ($queue as $interpreter) {
            if ($interpreter->supports($data)) {
                return $interpreter->interpret($data, $transcoder, $options);
            }
        }

        return $data;
    }

    /**
     * Get the ObjectInterpreter from the chain if registered.
     *
     * @return ObjectInterpreter|null
     */
    public function getObjectInterpreter(): ?ObjectInterpreter
    {
        // Clone the queue to avoid consuming it
        $queue = clone $this->getSplPriorityQueue();

        foreach ($queue as $interpreter) {
            if ($interpreter instanceof ObjectInterpreter) {
                return $interpreter;
            }
        }

        return null;
    }
}
