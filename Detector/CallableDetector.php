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

namespace Ducks\Component\EncodingRepair\Detector;

use Ducks\Component\EncodingRepair\Traits\CallableAdapterTrait;
use InvalidArgumentException;
use ReflectionException;

/**
 * Adapter for legacy callable detectors.
 *
 * @final
 */
final class CallableDetector implements DetectorInterface
{
    use CallableAdapterTrait;

    /**
     * @var callable(string, null|array<string, mixed>): (string|null)
     */
    private $callable;

    /**
     * @param callable(string, null|array<string, mixed>): (string|null) $callable Detection function
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
    public function detect(string $string, ?array $options = null): ?string
    {
        /** @var string|null|mixed $result */
        $result = ($this->callable)($string, $options);

        if (null !== $result && !\is_string($result)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Callable detector must return string|null, %s returned',
                    \gettype($result)
                )
            );
        }

        return $result;
    }



    /**
     * Return true if callable is a valid callable detector.
     *
     * @param callable $callable
     *
     * @return bool
     */
    public static function isValidCallable(callable $callable): bool
    {
        try {
            $reflection = self::getReflection($callable);

            return $reflection->getNumberOfParameters() >= 1;
            // @codeCoverageIgnoreStart
        } catch (ReflectionException $e) {
            return true;
        }
        // @codeCoverageIgnoreEnd
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
                'Callable detector must accept at least 1 parameter: (string)'
            );
        }
    }

}
