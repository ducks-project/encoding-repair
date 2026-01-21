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

namespace Ducks\Component\EncodingRepair\Transcoder;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Adapter for legacy callable transcoders.
 *
 * @final
 */
final class CallableTranscoder implements TranscoderInterface
{
    /**
     * @var callable(string, string, string, null|array<string, mixed>): (string|null)
     */
    private $callable;

    /**
     * @var int
     */
    private $priority;

    /**
     * @param callable(string, string, string, null|array<string, mixed>): (string|null) $callable Transcoding function
     * @param int $priority Priority value
     *
     * @throws InvalidArgumentException If callable signature is invalid
     */
    public function __construct(callable $callable, int $priority)
    {
        $this->validateCallable($callable);

        $this->callable = $callable;
        $this->priority = $priority;
    }

    /**
     * @inheritDoc
     */
    public function transcode(string $data, string $to, string $from, ?array $options = null): ?string
    {
        /** @var string|null|mixed $result */
        $result = ($this->callable)($data, $to, $from, $options);

        if (null !== $result && !\is_string($result)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Callable transcoder must return string|null, %s returned',
                    \gettype($result)
                )
            );
        }

        return $result;
    }

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
     * Return true if callable is a valid callable transcoder.
     *
     * @param callable $callable
     *
     * @return bool
     */
    public static function isValidCallable(callable $callable): bool
    {
        try {
            $reflection = self::getReflection($callable);

            return 3 <= $reflection->getNumberOfParameters();
        } catch (\ReflectionException $e) {
            // Because of PHP 7.4 we need to validate the callable
            // if ReflectionException occured.
            return true;
        }
    }

    /**
     * Validates callable signature.
     *
     * @param callable $callable Callable to validate
     *
     * @throws InvalidArgumentException If signature is invalid
     */
    private function validateCallable(callable $callable): void
    {
        if (!self::isValidCallable($callable)) {
            throw new InvalidArgumentException(
                'Callable transcoder must accept at least 4 parameters: (string, string, string, array)'
            );
        }
    }

    /**
     * Get reflection for callable.
     *
     * @param callable $callable Callable to reflect
     *
     * @return ReflectionFunctionAbstract
     *
     * @throws ReflectionException
     */
    private static function getReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (\is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if (\is_object($callable) && !$callable instanceof \Closure) {
            return new ReflectionMethod($callable, '__invoke');
        }

        $closure = Closure::fromCallable($callable);

        return new ReflectionFunction($closure);
    }
}
