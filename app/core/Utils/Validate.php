<?php

declare(strict_types=1);

namespace PhpSPA\Core\Utils;

/**
 * Data validation
 *
 * This trait provides methods for validating various types of data
 * within the PhpSPA framework. It ensures data integrity and security by applying
 * appropriate validation rules and techniques.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
class Validate
{
    /**
     * Validates the provided data.
     *
     * This method handles both individual values and arrays of values. It applies the appropriate validation
     * to each item in the array or to the single provided value.
     * It also ensures that each item is validated according to its type.
     *
     * @param mixed $data The data to validate. Can be a single value or an array of values.
     *
     * @return mixed Returns the validated data, maintaining its original type(s).
     * If an array is passed, an array of validated values is returned.
     */
    public static function validate($data) {
        // If the data is an array, validate each item recursively
        if (\is_array($data)) {
            return array_map(function ($item) {
                // Recursively validate each array element
                if (\is_array($item)) {
                    return static::validate($item); // If item is array, call validate on it
                }
                return static::realValidate($item); // Otherwise, validate the individual item
            }, $data);
        }

        // If the data is not an array, validate the value directly
        return static::realValidate($data);
    }

    /**
     * Checks if a value matches any of the allowed types.
     *
     * @param mixed $value The value to check.
     * @param array $allowedTypes The list of allowed types.
     * @return bool True if the value matches any of the allowed types, false otherwise.
     */
    public static function matchesType(mixed $value, array $allowedTypes): bool {
        if (in_array('mixed', $allowedTypes, true)) {
            return true;
        }

        if ($value === null) {
            return in_array('null', $allowedTypes, true);
        }

        $debugType = get_debug_type($value);

        // Normalize primitive debug types to PHP reflection type names
        $normalizedType = match ($debugType) {
            'integer' => 'int',
            'boolean' => 'bool',
            'double'  => 'float',
            'array'   => 'array',
            default   => $debugType,
        };

        foreach ($allowedTypes as $expected) {
            // Direct match for primitives (int, string, bool, float, array)
            if ($expected === $normalizedType) {
                return true;
            }

            // Generic object type hint
            if ($expected === 'object' && is_object($value)) {
                return true;
            }

            // Specific class / interface type hint (e.g. stdClass, Request, User)
            if (is_object($value) && $value instanceof $expected) {
                return true;
            }

            // Iterable check (arrays and Traversable objects)
            if ($expected === 'iterable' && is_iterable($value)) {
                return true;
            }

            // Callable check
            if ($expected === 'callable' && is_callable($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Performs the actual validation of a single value.
     *
     * @param mixed $value The value to be validated.
     * @return mixed The validated value, converted back to its original type.
     */
    private static function realValidate($value) {
        if (!\is_bool($value) && !\is_int($value) && !\is_numeric($value) && !\is_float($value) && !\is_double($value) && !\is_string($value)) {
            return $value;
        }

        $type = \gettype($value);

        // 1. Check for Strings (Date Formats)
        // if ($type === 'string') {
        //     // Matches YYYY-MM-DD or DD-MM-YYYY (with dashes or slashes)
        //     if (preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}.*)?$|^\d{2}-\d{2}-\d{4}$/', $value)) {
        //         try {
        //             return new DateTime($value);
        //         } catch (\Exception $e) {}
        //     }
        // }

        // 2. Check for Timestamps (Integers or Numeric Strings)
        // Looking for 10-digit numbers (roughly year 2001 to 2286)
        // if (\is_numeric($value) && (int)$value > 1000000000 && (int)$value < 9999999999) {
        //     try {
        //         return (new DateTime())->setTimestamp((int)$value);
        //     } catch (\Exception $e) {}
        // }

        $convertedValue = \is_bool($value) || $type === 'boolean'
            ? (bool) $value
            : (\is_numeric($value) || \is_int($value) || $type === 'integer'
                ? (\is_double($value) || \is_float($value) || $type === 'double' || strpos((string) $value, '.') !== false
                    ? (float) $value
                    : (int) $value)
                : $value);

        return $convertedValue;
    }
}
