<?php

declare(strict_types=1);

namespace PhpSPA\Core\Utils;

/**
 * Data validation and sanitization utilities
 *
 * This trait provides methods for validating and sanitizing various types of data
 * within the PhpSPA framework. It ensures data integrity and security by applying
 * appropriate validation rules and sanitization techniques.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
class Validate
{
    /**
     * Validates and sanitizes the provided data.
     *
     * This method handles both individual values and arrays of values. It applies the appropriate validation
     * and sanitization to each item in the array or to the single provided value.
     * It also ensures that each item is validated according to its type.
     *
     * @param mixed $data The data to validate. Can be a single value or an array of values.
     *
     * @return mixed Returns the validated data, maintaining its original type(s).
     * If an array is passed, an array of validated values is returned.
     */
    public static function validate($data) {
        if (!\is_bool($data) || !\is_int($data) || !\is_numeric($data) || !\is_float($data) || !\is_double($data) || !\is_string($data)) {
            return $data;
        }
        
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
     * Performs the actual validation and sanitization of a single value.
     *
     * This method converts the value to a string, sanitizes it using `htmlspecialchars`, and then converts it
     * back to its original type (boolean, integer, float, or string) based on the input type.
     *
     * @param mixed $value The value to be validated and sanitized.
     * @return mixed The validated and sanitized value, converted back to its original type.
     */
    private static function realValidate($value) {
        if (!\is_bool($value) || !\is_int($value) || !\is_numeric($value) || !\is_float($value) || !\is_double($value) || !\is_string($value)) {
            return $value;
        }

        // Convert the value to string for sanitation
        $validatedValue = (string) $value;

        // Sanitize the string to prevent potential HTML injection issues
        $sanitizedValue = htmlspecialchars($validatedValue);
        $type = \gettype($value);

        // Convert the sanitized string back to its original type based on the initial value's type
        $convertedValue = \is_bool($value) || $type === 'boolean'
            ? (bool) $sanitizedValue
            : (\is_numeric($value) || \is_int($value) || $type === 'integer'
                ? (\is_double($value) || \is_float($value) || $type === 'double' || strpos((string) $value, '.') !== false
                    ? (float) $sanitizedValue
                    : (int) $sanitizedValue)
                : $sanitizedValue);

        return $convertedValue;
    }
}
