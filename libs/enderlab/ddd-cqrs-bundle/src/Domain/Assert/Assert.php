<?php

/*
 * Fork of the webmozart/assert package.
 */

namespace EnderLab\DddCqrsBundle\Domain\Assert;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use Countable;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use EnderLab\DddCqrsBundle\Domain\Exception\InvalidArgument;
use Exception;
use ResourceBundle;
use SimpleXMLElement;
use Throwable;
use Traversable;

class Assert
{
    use Mixin;

    /**
     * @psalm-pure
     * @psalm-assert string $value
     *
     * @throws InvalidArgument
     */
    public static function string(mixed $value, string $message = ''): void
    {
        if (!\is_string($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_string',
                ['%value%' => static::typeToString($value)],
                'A0001'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert non-empty-string $value
     *
     * @throws InvalidArgument
     */
    public static function stringNotEmpty(mixed $value, string $message = ''): void
    {
        static::string($value, $message);
        static::notEq($value, '', $message);
    }

    /**
     * @psalm-pure
     * @psalm-assert int $value
     *
     * @throws InvalidArgument
     */
    public static function integer(mixed $value, string $message = ''): void
    {
        if (!\is_int($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_int',
                ['%value%' => static::typeToString($value)],
                'A0002'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert positive-int $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function positiveInteger(mixed $value, string $message = ''): void
    {
        if (!(\is_int($value) && $value > 0)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_positive_int',
                ['%value%' => static::typeToString($value)],
                'A0003'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert float $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function float(mixed $value, string $message = ''): void
    {
        if (!\is_float($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_float',
                ['%value%' => static::typeToString($value)],
                'A0004'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert numeric $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function numeric(mixed $value, string $message = ''): void
    {
        if (!\is_numeric($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_numeric',
                ['%value%' => static::typeToString($value)],
                'A0005'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert positive-int|0 $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function natural(mixed $value, string $message = ''): void
    {
        if (!\is_int($value) || $value < 0) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_natural',
                ['%value%' => static::typeToString($value)],
                'A0006'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert bool $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function boolean(mixed $value, string $message = ''): void
    {
        if (!\is_bool($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_boolean',
                ['%value%' => static::typeToString($value)],
                'A0007'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert scalar $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function scalar(mixed $value, string $message = ''): void
    {
        if (!\is_scalar($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_scalar',
                ['%value%' => static::typeToString($value)],
                'A0008'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert object $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function object(mixed $value, string $message = ''): void
    {
        if (!\is_object($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_object',
                ['%value%' => static::typeToString($value)],
                'A0009'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert resource $value
     *
     * @param mixed       $value
     * @param string|null $type    type of resource this should be. @see https://www.php.net/manual/en/function.get-resource-type.php
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function resource(mixed $value, ?string $type = null, string $message = ''): void
    {
        if (!\is_resource($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_resource',
                ['%value%' => static::typeToString($value)],
                'A0010'
            );
        }

        if ($type && $type !== \get_resource_type($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_resource_type',
                [
                    '%value%' => static::typeToString($value),
                    '%type%'  => $type,
                ],
                'A0011'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert callable $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isCallable(mixed $value, string $message = ''): void
    {
        if (!\is_callable($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_callable',
                ['%value%' => static::typeToString($value)],
                'A0012'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert array $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isArray(mixed $value, string $message = ''): void
    {
        if (!\is_array($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_array',
                ['%value%' => static::typeToString($value)],
                'A0013'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert array|ArrayAccess $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isArrayAccessible(mixed $value, string $message = ''): void
    {
        if (!\is_array($value) && !($value instanceof ArrayAccess)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_array_access',
                ['%value%' => static::typeToString($value)],
                'A0014'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert countable $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isCountable(mixed $value, string $message = ''): void
    {
        if (
            !\is_array($value)
            && !($value instanceof Countable)
            && !($value instanceof ResourceBundle)
            && !($value instanceof SimpleXMLElement)
        ) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_countable',
                ['%value%' => static::typeToString($value)],
                'A0015'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert iterable $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isIterable(mixed $value, string $message = ''): void
    {
        if (!\is_array($value) && !($value instanceof Traversable)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_iterable',
                ['%value%' => static::typeToString($value)],
                'A0016'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert ExpectedType $value
     *
     * @param mixed         $value
     * @param object|string $class
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isInstanceOf(mixed $value, object|string $class, string $message = ''): void
    {
        if (!($value instanceof $class)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_instance_of',
                [
                    '%value%' => static::typeToString($value),
                    '%class%' => $class,
                ],
                'A0017'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert !ExpectedType $value
     *
     * @param mixed         $value
     * @param object|string $class
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notInstanceOf(mixed $value, object|string $class, string $message = ''): void
    {
        if ($value instanceof $class) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_not_instance_of',
                [
                    '%value%' => static::typeToString($value),
                    '%class%' => $class,
                ],
                'A0018'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-param array<class-string> $classes
     *
     * @param mixed                $value
     * @param array<object|string> $classes
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isInstanceOfAny(mixed $value, array $classes, string $message = ''): void
    {
        foreach ($classes as $class) {
            if ($value instanceof $class) {
                return;
            }
        }

        static::reportInvalidArgument(
            $message ?: 'assert.expected_is_instance_of_any',
            [
                '%value%' => static::typeToString($value),
                '%classes%' => \implode(', ', \array_map(array(static::class, 'valueToString'), $classes))
            ],
            'A0019'
        );
    }

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert ExpectedType|class-string<ExpectedType> $value
     *
     * @param object|string $value
     * @param string $class
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isAOf(object|string $value, string $class, string $message = ''): void
    {
        static::string($class, 'Expected class as a string. Got: %s');

        if (!\is_a($value, $class, \is_string($value))) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_is_a_of',
                [
                    '%value%' => static::typeToString($value),
                    '%class%' => $class,
                ],
                'A0020'
            );
        }
    }

    public static function isValidTimezone(string $value, string $message = ''): void
    {
        if (!\in_array($value, DateTimeZone::listIdentifiers(), true)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_valid_timezone',
                ['%value%' => $value],
                'A0021'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-template UnexpectedType of object
     * @psalm-param class-string<UnexpectedType> $class
     * @psalm-assert !UnexpectedType $value
     * @psalm-assert !class-string<UnexpectedType> $value
     *
     * @param object|string $value
     * @param string $class
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isNotA(object|string $value, string $class, string $message = ''): void
    {
        static::string($class, 'Expected class as a string. Got: %s');

        if (\is_a($value, $class, \is_string($value))) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected an instance of this class or to this class among its parents other than "%2$s". Got: %s',
                static::valueToString($value),
                $class
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-param array<class-string> $classes
     *
     * @param object|string $value
     * @param string[]      $classes
     * @param string        $message
     *
     * @throws InvalidArgument
     */
    public static function isAnyOf($value, array $classes, $message = '')
    {
        foreach ($classes as $class) {
            static::string($class, 'Expected class as a string. Got: %s');

            if (\is_a($value, $class, \is_string($value))) {
                return;
            }
        }

        static::reportInvalidArgument(sprintf(
            $message ?: 'Expected an instance of any of this classes or any of those classes among their parents "%2$s". Got: %s',
            static::valueToString($value),
            \implode(', ', $classes)
        ));
    }

    /**
     * @psalm-pure
     * @psalm-assert empty $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isEmpty($value, $message = '')
    {
        if (!empty($value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected an empty value. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert !empty $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notEmpty($value, $message = '')
    {
        if (empty($value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a non-empty value. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert null $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function null($value, $message = '')
    {
        if (null !== $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected null. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert !null $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notNull($value, $message = '')
    {
        if (null === $value) {
            static::reportInvalidArgument(
                $message ?: 'Expected a value other than null.'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert true $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function true($value, $message = '')
    {
        if (true !== $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to be true. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert false $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function false($value, $message = '')
    {
        if (false !== $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to be false. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert !false $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notFalse($value, $message = '')
    {
        if (false === $value) {
            static::reportInvalidArgument(
                $message ?: 'Expected a value other than false.'
            );
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function ip($value, $message = '')
    {
        if (false === \filter_var($value, \FILTER_VALIDATE_IP)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to be an IP. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function ipv4($value, $message = '')
    {
        if (false === \filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to be an IPv4. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function ipv6($value, $message = '')
    {
        if (false === \filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to be an IPv6. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function email($value, $message = '')
    {
        if (false === \filter_var($value, FILTER_VALIDATE_EMAIL)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to be a valid e-mail address. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * Does non strict comparisons on the items, so ['3', 3] will not pass the assertion.
     *
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function uniqueValues(array $values, $message = '')
    {
        $allValues = \count($values);
        $uniqueValues = \count(\array_unique($values));

        if ($allValues !== $uniqueValues) {
            $difference = $allValues - $uniqueValues;

            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected an array of unique values, but %s of them %s duplicated',
                $difference,
                (1 === $difference ? 'is' : 'are')
            ));
        }
    }

    /**
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function eq($value, $expect, $message = '')
    {
        if ($expect != $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value equal to %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($expect)
            ));
        }
    }

    /**
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notEq($value, $expect, $message = '')
    {
        if ($expect == $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a different value than %s.',
                static::valueToString($expect)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function same($value, $expect, $message = '')
    {
        if ($expect !== $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value identical to %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($expect)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notSame($value, $expect, $message = '')
    {
        if ($expect === $value) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value not identical to %s.',
                static::valueToString($expect)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function greaterThan($value, $limit, $message = '')
    {
        if ($value <= $limit) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value greater than %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($limit)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function greaterThanEq($value, $limit, $message = '')
    {
        if ($value < $limit) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value greater than or equal to %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($limit)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function lessThan($value, $limit, $message = '')
    {
        if ($value >= $limit) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value less than %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($limit)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function lessThanEq($value, $limit, $message = '')
    {
        if ($value > $limit) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value less than or equal to %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($limit)
            ));
        }
    }

    /**
     * Inclusive range, so Assert::(3, 3, 5) passes.
     *
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $min
     * @param mixed  $max
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function range($value, $min, $max, $message = '')
    {
        if ($value < $min || $value > $max) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value between %2$s and %3$s. Got: %s',
                static::valueToString($value),
                static::valueToString($min),
                static::valueToString($max)
            ));
        }
    }

    /**
     * A more human-readable alias of Assert::inArray().
     *
     * @psalm-pure
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function oneOf($value, array $values, $message = '')
    {
        static::inArray($value, $values, $message);
    }

    /**
     * Does strict comparison, so Assert::inArray(3, ['3']) does not pass the assertion.
     *
     * @psalm-pure
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function inArray($value, array $values, $message = '')
    {
        if (!\in_array($value, $values, true)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected one of: %2$s. Got: %s',
                static::valueToString($value),
                \implode(', ', \array_map(array(static::class, 'valueToString'), $values))
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $subString
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function contains($value, $subString, $message = '')
    {
        if (false === \strpos($value, $subString)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($subString)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $subString
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notContains($value, $subString, $message = '')
    {
        if (false !== \strpos($value, $subString)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: '%2$s was not expected to be contained in a value. Got: %s',
                static::valueToString($value),
                static::valueToString($subString)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notWhitespaceOnly($value, $message = '')
    {
        if (\preg_match('/^\s*$/', $value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a non-whitespace string. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $prefix
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function startsWith($value, $prefix, $message = '')
    {
        if (0 !== \strpos($value, $prefix)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to start with %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($prefix)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $prefix
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notStartsWith($value, $prefix, $message = '')
    {
        if (0 === \strpos($value, $prefix)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value not to start with %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($prefix)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function startsWithLetter($value, $message = '')
    {
        static::string($value);

        $valid = isset($value[0]);

        if ($valid) {
            $locale = \setlocale(LC_CTYPE, 0);
            \setlocale(LC_CTYPE, 'C');
            $valid = \ctype_alpha($value[0]);
            \setlocale(LC_CTYPE, $locale);
        }

        if (!$valid) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to start with a letter. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $suffix
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function endsWith($value, $suffix, $message = '')
    {
        if ($suffix !== \substr($value, -\strlen($suffix))) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to end with %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($suffix)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $suffix
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notEndsWith($value, $suffix, $message = '')
    {
        if ($suffix === \substr($value, -\strlen($suffix))) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value not to end with %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($suffix)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $pattern
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function regex($value, $pattern, $message = '')
    {
        if (!\preg_match($pattern, $value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'The value %s does not match the expected pattern.',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $pattern
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function notRegex($value, $pattern, $message = '')
    {
        if (\preg_match($pattern, $value, $matches, PREG_OFFSET_CAPTURE)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'The value %s matches the pattern %s (at offset %d).',
                static::valueToString($value),
                static::valueToString($pattern),
                $matches[0][1]
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function unicodeLetters($value, $message = '')
    {
        static::string($value);

        if (!\preg_match('/^\p{L}+$/u', $value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain only Unicode letters. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function alpha($value, $message = '')
    {
        static::string($value);

        $locale = \setlocale(LC_CTYPE, 0);
        \setlocale(LC_CTYPE, 'C');
        $valid = !\ctype_alpha($value);
        \setlocale(LC_CTYPE, $locale);

        if ($valid) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain only letters. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function digits($value, $message = '')
    {
        $locale = \setlocale(LC_CTYPE, 0);
        \setlocale(LC_CTYPE, 'C');
        $valid = !\ctype_digit($value);
        \setlocale(LC_CTYPE, $locale);

        if ($valid) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain digits only. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function alnum($value, $message = '')
    {
        $locale = \setlocale(LC_CTYPE, 0);
        \setlocale(LC_CTYPE, 'C');
        $valid = !\ctype_alnum($value);
        \setlocale(LC_CTYPE, $locale);

        if ($valid) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain letters and digits only. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert lowercase-string $value
     *
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function lower($value, $message = '')
    {
        $locale = \setlocale(LC_CTYPE, 0);
        \setlocale(LC_CTYPE, 'C');
        $valid = !\ctype_lower($value);
        \setlocale(LC_CTYPE, $locale);

        if ($valid) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain lowercase characters only. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert !lowercase-string $value
     *
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function upper($value, $message = '')
    {
        $locale = \setlocale(LC_CTYPE, 0);
        \setlocale(LC_CTYPE, 'C');
        $valid = !\ctype_upper($value);
        \setlocale(LC_CTYPE, $locale);

        if ($valid) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain uppercase characters only. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @param string $value
     * @param int    $length
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function length($value, $length, $message = '')
    {
        if ($length !== static::strlen($value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain %2$s characters. Got: %s',
                static::valueToString($value),
                $length
            ));
        }
    }

    /**
     * Inclusive min.
     *
     * @psalm-pure
     *
     * @param string    $value
     * @param int|float $min
     * @param string    $message
     *
     * @throws InvalidArgument
     */
    public static function minLength($value, $min, $message = '')
    {
        if (static::strlen($value) < $min) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain at least %2$s characters. Got: %s',
                static::valueToString($value),
                $min
            ));
        }
    }

    /**
     * Inclusive max.
     *
     * @psalm-pure
     *
     * @param string    $value
     * @param int|float $max
     * @param string    $message
     *
     * @throws InvalidArgument
     */
    public static function maxLength($value, $max, $message = '')
    {
        if (static::strlen($value) > $max) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain at most %2$s characters. Got: %s',
                static::valueToString($value),
                $max
            ));
        }
    }

    /**
     * Inclusive , so Assert::lengthBetween('asd', 3, 5); passes the assertion.
     *
     * @psalm-pure
     *
     * @param string    $value
     * @param int|float $min
     * @param int|float $max
     * @param string    $message
     *
     * @throws InvalidArgument
     */
    public static function lengthBetween($value, $min, $max, $message = '')
    {
        $length = static::strlen($value);

        if ($length < $min || $length > $max) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a value to contain between %2$s and %3$s characters. Got: %s',
                static::valueToString($value),
                $min,
                $max
            ));
        }
    }

    /**
     * Will also pass if $value is a directory, use Assert::file() instead if you need to be sure it is a file.
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function fileExists(mixed $value, string $message = ''): void
    {
        static::string($value);

        if (!\file_exists($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.file_does_not_exist',
                ['%value%' => static::valueToString($value)],
                'A0100'
            );
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function file(mixed $value, string $message = ''): void
    {
        static::fileExists($value, $message);

        if (!\is_file($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_file',
                ['%value%' => static::valueToString($value)],
                'A0101'
            );
        }
    }

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function directory(mixed $value, string $message = ''): void
    {
        static::fileExists($value, $message);

        if (!\is_dir($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_directory',
                ['%value%' => static::valueToString($value)],
                'A0102'
            );
        }
    }

    /**
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function readable($value, $message = '')
    {
        if (!\is_readable($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.path_not_readable',
                ['%value%' => static::valueToString($value)],
                'A0103'
            );
        }
    }

    /**
     * @param string $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function writable($value, $message = '')
    {
        if (!\is_writable($value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.path_not_writable',
                ['%value%' => static::valueToString($value)],
                'A0104'
            );
        }
    }

    /**
     * @psalm-assert class-string $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function classExists($value, $message = '')
    {
        if (!\class_exists($value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected an existing class name. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert class-string<ExpectedType>|ExpectedType $value
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgument
     */
    public static function subclassOf($value, $class, $message = '')
    {
        if (!\is_subclass_of($value, $class)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected a sub-class of %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($class)
            ));
        }
    }

    /**
     * @psalm-assert class-string $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function interfaceExists($value, $message = '')
    {
        if (!\interface_exists($value)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected an existing interface name. got %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $interface
     * @psalm-assert class-string<ExpectedType> $value
     *
     * @param mixed  $value
     * @param mixed  $interface
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function implementsInterface($value, $interface, $message = '')
    {
        if (!\in_array($interface, \class_implements($value))) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected an implementation of %2$s. Got: %s',
                static::valueToString($value),
                static::valueToString($interface)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-param class-string|object $classOrObject
     *
     * @throws InvalidArgument
     */
    public static function propertyExists(object|string $classOrObject, mixed $property, string $message = ''): void
    {
        if (!\property_exists($classOrObject, $property)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected the property %s to exist.',
                static::valueToString($property)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-param class-string|object $classOrObject
     *
     * @throws InvalidArgument
     */
    public static function propertyNotExists(object|string $classOrObject, mixed $property, string $message = ''): void
    {
        if (\property_exists($classOrObject, $property)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected the property %s to not exist.',
                static::valueToString($property)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-param class-string|object $classOrObject
     *
     * @throws InvalidArgument
     */
    public static function methodExists(object|string $classOrObject, mixed $method, string $message = ''): void
    {
        if (!(\is_string($classOrObject) || \is_object($classOrObject)) || !\method_exists($classOrObject, $method)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected the method %s to exist.',
                static::valueToString($method)
            ));
        }
    }

    /**
     * @psalm-pure
     * @psalm-param class-string|object $classOrObject
     *
     * @throws InvalidArgument
     */
    public static function methodNotExists(object|string $classOrObject, mixed $method, string $message = ''): void
    {
        if ((\is_string($classOrObject) || \is_object($classOrObject)) && \method_exists($classOrObject, $method)) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected the method %s to not exist.',
                static::valueToString($method)
            ));
        }
    }

    /**
     * @psalm-pure
     *
     * @throws InvalidArgument
     */
    public static function keyExists(array $array, int|string $key, string $message = ''): void
    {
        if (!(isset($array[$key]) || \array_key_exists($key, $array))) {
            static::reportInvalidArgument(\sprintf(
                $message ?: 'Expected the key %s to exist.',
                static::valueToString($key)
            ));
        }
    }

    /**
     * @psalm-pure

     * @throws InvalidArgument
     */
    public static function keyNotExists(array $array, int|string $key, string $message = ''): void
    {
        if (isset($array[$key]) || \array_key_exists($key, $array)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_key_not_exist',
                ['%key%' => static::typeToString($key)],
                'A0201'
            );
        }
    }

    /**
     * Checks if a value is a valid array key (int or string).
     *
     * @psalm-pure
     * @psalm-assert array-key $value

     * @throws InvalidArgument
     */
    public static function validArrayKey(mixed $value, string $message = ''): void
    {
        if (!(\is_int($value) || \is_string($value))) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_valid_array_key',
                ['%value%' => static::typeToString($value)],
                'A0202'
            );
        }
    }

    /**
     * Does not check if $array is countable, this can generate a warning on php versions after 7.2.

     * @throws InvalidArgument
     */
    public static function count(Countable|array $array, int $number, string $message = ''): void
    {
        static::eq(
            \count($array),
            $number,
            \sprintf(
                $message ?: 'Expected an array to contain %d elements. Got: %d.',
                $number,
                \count($array)
            )
        );
    }

    /**
     * Does not check if $array is countable, this can generate a warning on php versions after 7.2.

     * @throws InvalidArgument
     */
    public static function minCount(Countable|array $array, float|int $min, string $message = ''): void
    {
        if (\count($array) < $min) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_min_count',
                [
                    '%count%' => \count($array),
                    '%min%' => $min
                ],
                'A0203'
            );
        }
    }

    /**
     * Does not check if $array is countable, this can generate a warning on php versions after 7.2.
     *
     * @throws InvalidArgument
     */
    public static function maxCount(Countable|array $array, float|int $max, string $message = ''): void
    {
        if (\count($array) > $max) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_max_count',
                [
                    '%count%' => \count($array),
                    '%max%' => $max
                ],
                'A0204'
            );
        }
    }

    /**
     * Does not check if $array is countable, this can generate a warning on php versions after 7.2.
     *
     * @throws InvalidArgument
     */
    public static function countBetween(Countable|array $array, float|int $min, float|int $max, string $message = ''): void
    {
        $count = \count($array);

        if ($count < $min || $count > $max) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_count_between',
                [
                    '%count%' => $count,
                    '%min%' => $min,
                    '%max%' => $max
                ],
                'A0205'
            );
        }
    }

    /**
     * @psalm-pure
     *
     * @throws InvalidArgument
     */
    public static function isList(mixed $array, string $message = ''): void
    {
        if (!\is_array($array)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_list',
                [],
                'A0206'
            );
        }

        if ($array === \array_values($array)) {
            return;
        }

        $nextKey = -1;
        foreach ($array as $k => $v) {
            if ($k !== ++$nextKey) {
                static::reportInvalidArgument(
                    $message ?: 'assert.expected_list',
                    [],
                    'A0206'
                );
            }
        }
    }

    /**
     * @psalm-pure
     * @psalm-assert non-empty-list $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function isNonEmptyList(mixed $array, string $message = ''): void
    {
        static::isList($array, $message);
        static::notEmpty($array, $message);
    }

    /**
     * @psalm-pure
     * @psalm-template T
     * @psalm-param mixed|array<T> $array
     * @psalm-assert array<string, T> $array
     *
     * @throws InvalidArgument
     */
    public static function isMap(mixed $array, string $message = ''): void
    {
        if (
            !\is_array($array) ||
            \array_keys($array) !== \array_filter(\array_keys($array), '\is_string')
        ) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_map',
                [],
                'A0207'
            );
        }
    }

    /**
     * @psalm-pure
     * @psalm-template T
     * @psalm-param mixed|array<T> $array
     * @psalm-assert array<string, T> $array
     * @psalm-assert !empty $array
     *
     * @throws InvalidArgument
     */
    public static function isNonEmptyMap(mixed $array, string $message = ''): void
    {
        static::isMap($array, $message);
        static::notEmpty($array, $message);
    }

    /**
     * @psalm-pure
     *
     * @throws InvalidArgument
     */
    public static function uuid(string $value, string $message = ''): void
    {
        $value = \str_replace(array('urn:', 'uuid:', '{', '}'), '', $value);

        // The nil UUID is special form of UUID that is specified to have all
        // 128 bits set to zero.
        if ('00000000-0000-0000-0000-000000000000' === $value) {
            return;
        }

        if (!\preg_match('/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/', $value)) {
            static::reportInvalidArgument(
                $message ?: 'assert.expected_uuid',
                ['%value%' => static::valueToString($value)],
                'A0208'
            );
        }
    }

    /**
     * @psalm-param class-string<Throwable> $class
     *
     * @param Closure $expression
     * @param string $class
     * @param string $message
     *
     * @throws InvalidArgument
     */
    public static function throws(Closure $expression, string $class = 'Exception', string $message = ''): void
    {
        static::string($class);

        $actual = 'none';

        try {
            $expression();
        } catch (Exception $e) {
            $actual = \get_class($e);
            if ($e instanceof $class) {
                return;
            }
        } catch (Throwable $e) {
            $actual = \get_class($e);
            if ($e instanceof $class) {
                return;
            }
        }

        static::reportInvalidArgument(
            $message ?: 'assert.expected_throw',
            ['%class%' => $class, '%actual%' => $actual],
            'A0209'
        );
    }

    /**
     * @throws BadMethodCallException
     */
    public static function __callStatic($name, $arguments)
    {
        if ('nullOr' === \substr($name, 0, 6)) {
            if (null !== $arguments[0]) {
                $method = \lcfirst(\substr($name, 6));
                \call_user_func_array(array(static::class, $method), $arguments);
            }

            return;
        }

        if ('all' === \substr($name, 0, 3)) {
            static::isIterable($arguments[0]);

            $method = \lcfirst(\substr($name, 3));
            $args = $arguments;

            foreach ($arguments[0] as $entry) {
                $args[0] = $entry;

                \call_user_func_array(array(static::class, $method), $args);
            }

            return;
        }

        throw new BadMethodCallException('No such method: '.$name);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected static function valueToString(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (true === $value) {
            return 'true';
        }

        if (false === $value) {
            return 'false';
        }

        if (\is_array($value)) {
            return 'array';
        }

        if (\is_object($value)) {
            if (\method_exists($value, '__toString')) {
                return \get_class($value).': '.self::valueToString($value->__toString());
            }

            if ($value instanceof DateTime || $value instanceof DateTimeImmutable) {
                return \get_class($value).': '.self::valueToString($value->format('c'));
            }

            return \get_class($value);
        }

        if (\is_resource($value)) {
            return 'resource';
        }

        if (\is_string($value)) {
            return '"'.$value.'"';
        }

        return (string) $value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected static function typeToString(mixed $value): string
    {
        return \is_object($value) ? \get_class($value) : \gettype($value);
    }

    protected static function strlen($value)
    {
        if (!\function_exists('mb_detect_encoding')) {
            return \strlen($value);
        }

        if (false === $encoding = \mb_detect_encoding($value)) {
            return \strlen($value);
        }

        return \mb_strlen($value, $encoding);
    }

    /**
     * @param string $message
     * @param array $parameters
     * @param string $internalCode
     * @return void
     * @psalm-pure this method is not supposed to perform side-effects
     */
    protected static function reportInvalidArgument(string $message, array $parameters = [], string $internalCode = 'E9999'): void
    {
        throw new InvalidArgument($message, $parameters, $internalCode);
    }

    private function __construct() {
    }
}
