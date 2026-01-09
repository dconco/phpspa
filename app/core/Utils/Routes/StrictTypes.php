<?php

declare(strict_types=1);

namespace PhpSPA\Core\Utils\Routes;

use PhpSPA\Core\Utils\Routes\Exceptions\InvalidTypesException;

/**
 * Strict type validation for route parameters
 *
 * This trait provides methods to enforce strict type checking for route parameters
 * within the PhpSPA framework. It ensures that route parameters match their expected
 * data types and provides validation mechanisms for type safety.
 *
 * @package PhpSPA\Core\Utils\Routes
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @since v1.0.0
 */
trait StrictTypes
{
    /**
     * Matches the type of a given string against an array of types.
     *
     * @param string $needle The string to check the type of.
     * @param array $haystack The array of types to match against.
     * @return bool Returns true if the type of the string matches any type in the array, false otherwise.
     */
    protected static function matchType(string $needle, array $haystack): bool
    {
        $is_typed_string = false;

        $haystack = array_map(function ($type) use (&$is_typed_string) {
            $t = strtoupper(trim($type));
            if ('STRING' === $t) {
                $is_typed_string = true;
            }
            return $t;
        }, $haystack);

        $typeOfNeedle = self::typeOfString($needle, $is_typed_string);

        foreach ($haystack as $type) {
            $type = $type === 'INTEGER' ? 'INT' : $type;
            $type = $type === 'BOOLEAN' ? 'BOOL' : $type;

            if (self::matches($needle, $type)) {
                return true;
            }

            if (strtoupper($type) === $typeOfNeedle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Matches the given string against a list of types and returns the value
     * cast to the matched type.
     *
     * @param string $needle The string to match and cast.
     * @param string[] $haystack The list of types to match against.
     * @return int|bool|float|array|string The value cast to the matched type.
     * @throws InvalidTypesException If the type of the needle does not match any type in the haystack.
     */
    protected static function matchStrictType(
        string $needle,
        array $haystack,
    ): int|bool|float|array|string {
        $is_typed_string = false;

        $types = array_map(function ($t) use (&$is_typed_string) {
            $t = strtoupper(trim($t));

            if ('STRING' === $t) {
                $is_typed_string = true;
            }

            return $t;
        }, $haystack);
        $typeOfNeedle = self::typeOfString($needle, $is_typed_string);

        if (self::matchType($needle, $types)) {
            return match ($typeOfNeedle) {
                'INT' => (int) $needle,
                'BOOL' => filter_var($needle, FILTER_VALIDATE_BOOLEAN),
                'FLOAT' => (float) $needle,
                'ARRAY' => json_decode($needle, true),
                default => $needle,
            };
        }

        return false;
        // InvalidTypesException::catchInvalidStrictTypes($haystack);
        // throw InvalidTypesException::catchInvalidParameterTypes($types, $typeOfNeedle);
    }

    /**
     * Matches the type of the given needle against the specified haystack type.
     *
     * This method checks if the type of the needle matches the type specified in the haystack.
     * If the haystack specifies an array type, it will recursively check each element of the array.
     *
     * @param string $needle The value to check.
     * @param string $haystack The type specification to match against.
     * @return bool Returns true if the needle matches the haystack type, otherwise false.
     * @throws InvalidTypesException If the needle does not match the haystack type.
     */
    private static function matches(string $needle, string $haystack): bool
    {
        $is_typed_string = $haystack === 'STRING' ? true : false;
        $typeOfNeedle = self::typeOfString((string) $needle, $is_typed_string);
        $typeOfNeedle2 = $typeOfNeedle;
        $needle2 = $needle;

        /**
         * MATCH ARRAY RECURSIVELY
         */
        if (
            preg_match('/ARRAY<(.+)>/', $haystack, $matches) &&
            $typeOfNeedle === 'ARRAY'
        ) {
            $needle = json_decode($needle, true);
            $eachArrayTypes = preg_split('/,(?![^<]*>)/', $matches[1]);

            if (!\is_array($needle)) {
                return false;
                // throw new AppException("Invalid request parameter type. {ARRAY} requested, but got {{$typeOfNeedle}}");
            }

            foreach ($eachArrayTypes as $key => $eachArrayType) {
                if (!isset($needle[$key])) {
                    return false;
                    // throw new AppException("Array index $key not found in the request parameter");
                }

                $needle2 = \is_array($needle[$key])
                    ? json_encode($needle[$key])
                    : (string) $needle[$key];

                $eachTypes = preg_split('/\|(?![^<]*>)/', trim($eachArrayType));
                $is_typed_string = false;

                $eachTypes = array_map(function ($t) use (&$is_typed_string) {
                    $t = strtoupper(trim($t));

                    if ('STRING' === $t) {
                        $is_typed_string = true;
                    }
                    return $t;
                }, $eachTypes);
                $typeOfNeedle2 = self::typeOfString($needle2, $is_typed_string);

                if (!self::matchType($needle2, $eachTypes)) {
                    return false;
                    // $requested = implode(', ', $eachTypes);
                    // InvalidTypesException::catchInvalidStrictTypes($eachTypes);
                    // throw InvalidTypesException::catchInvalidParameterTypes(
                    //  $eachTypes,
                    //  $typeOfNeedle2,
                    //  "Invalid request parameter type. {{$requested}} requested on array index $key, but got {{$typeOfNeedle2}}",
                    // );
                }
            }
            return true;
        }

        /**
         * MATCH INT<MIN, MAX>
         */
        if (
            preg_match('/INT<(\d+)(?:,\s*(\d+))?>/', $haystack, $matches) &&
            $typeOfNeedle === 'INT'
        ) {
            $min = (int) $matches[1];
            $max = (int) $matches[2] ?? null;
            $needle = (int) $needle;

            if (
                (!$max && $min < $needle) ||
                ($max && ($needle < $min || $needle > $max))
            ) {
                return false;
                // $requested = !$max ? "INT min ($min)" : "INT min ($min), max($max)";
                // throw new AppException("Invalid request parameter type. {{$requested}} requested, but got {{$needle}}");
            }
            return true;
        }

        InvalidTypesException::catchInvalidStrictTypes($haystack);
        return false;
    }

    /**
     * Determines the type of a given string.
     *
     * This method analyzes the input string and returns a string representing its type.
     * The possible return values are:
     * - 'FLOAT' if the string represents a floating-point number.
     * - 'INT' if the string represents an integer.
     * - 'BOOL' if the string represents a boolean value ('true' or 'false').
     * - 'ALPHA' if the string contains only alphabetic characters.
     * - 'ALNUM' if the string contains only alphanumeric characters.
     * - 'JSON' if the string is a valid JSON object.
     * - 'ARRAY' if the string is a valid JSON array.
     * - 'STRING' if the string does not match any of the above types.
     *
     * @param string $string The input string to be analyzed.
     * @param bool $is_typed_string If the checking route type contains a STRING type
     * @return string The type of the input string.
     */
    protected static function typeOfString(
        string $string,
        bool $is_typed_string,
    ): string {
        $decoded = json_decode($string, false);

        if (is_numeric($string)) {
            return strpos($string, '.') !== false ? 'FLOAT' : 'INT';
        } elseif (
            filter_var(
                $string,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            ) !== null
        ) {
            return 'BOOL';
        } elseif (json_last_error() === JSON_ERROR_NONE) {
            return match (gettype($decoded)) {
                'object' => 'JSON',
                'array' => 'ARRAY',
                default => 'STRING',
            };
        }

        if (true === $is_typed_string) {
            return 'STRING';
        }

        if (ctype_alpha($string)) {
            return 'ALPHA';
        } elseif (ctype_alnum($string)) {
            return 'ALNUM';
        }

        return 'STRING';
    }
}
