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

namespace Ducks\Component\EncodingRepair\Traits;

use Closure;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Common functionality for callable adapters.
 */
trait CallableAdapterTrait
{
    /**
     * @var int
     */
    private $priority;

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get reflection for callable.
     *
     * @param callable $callable Callable to reflect
     *
     * @return ReflectionFunctionAbstract
     *
     * @throws ReflectionException
     *
     * @codeCoverageIgnore
     */
    private static function getReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (\is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if (\is_object($callable) && !$callable instanceof Closure) {
            return new ReflectionMethod($callable, '__invoke');
        }

        $closure = Closure::fromCallable($callable);

        return new ReflectionFunction($closure);
    }
}
