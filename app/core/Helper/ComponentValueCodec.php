<?php

namespace PhpSPA\Core\Helper;

use PhpSPA\Http\Session;

/**
 * Authenticated codec for rich values passed through component-tag attributes.
 *
 * Component values may contain application objects, so JSON alone is not a
 * compatible transport. The HMAC ensures unserialize() is reached only for a
 * value emitted by this server in the current session, never for arbitrary HTML
 * supplied by a visitor.
 */
final class ComponentValueCodec
{
    private const string PREFIX = 'phpspa-v1';
    private const string SESSION_KEY = '__phpspa_component_value_key';
    private const int MAX_SERIALIZED_BYTES = 16_777_216;

    public static function encode(mixed $value): string
    {
        $serialized = serialize($value);
        if (strlen($serialized) > self::MAX_SERIALIZED_BYTES) {
            throw new \LengthException('Component value exceeds the maximum encoded size');
        }

        $payload = base64_encode($serialized);
        $signature = hash_hmac('sha256', $serialized, self::signingKey());

        return self::PREFIX . '.' . $payload . '.' . $signature;
    }

    public static function decode(string $encoded, ?bool &$valid = null): mixed
    {
        $valid = false;
        if ($encoded === '' || strlen($encoded) > (self::MAX_SERIALIZED_BYTES * 2)) {
            return null;
        }

        $parts = explode('.', $encoded, 3);
        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return null;
        }

        [$prefix, $payload, $signature] = $parts;
        if (preg_match('/^[a-f0-9]{64}$/D', $signature) !== 1) {
            return null;
        }

        $serialized = base64_decode($payload, true);
        if (
            $serialized === false
            || strlen($serialized) > self::MAX_SERIALIZED_BYTES
            || !hash_equals(hash_hmac('sha256', $serialized, self::signingKey()), $signature)
        ) {
            return null;
        }

        try {
            $value = unserialize($serialized, ['allowed_classes' => true]);
        } catch (\Throwable) {
            return null;
        }

        $valid = true;
        return $value;
    }

    private static function signingKey(): string
    {
        $encoded = Session::get(self::SESSION_KEY);
        if (is_string($encoded)) {
            $key = base64_decode($encoded, true);
            if ($key !== false && strlen($key) === 32) {
                return $key;
            }
        }

        $key = random_bytes(32);
        Session::set(self::SESSION_KEY, base64_encode($key), true);
        return $key;
    }
}
