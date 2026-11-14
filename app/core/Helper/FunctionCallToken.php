<?php

namespace PhpSPA\Core\Helper;

/**
 * Encodes and validates the opaque token used by the browser-side __call bridge.
 *
 * The token is data, never a PHP object graph. Keeping this codec JSON-only is a
 * security boundary: values received from the network must never reach
 * unserialize().
 */
final class FunctionCallToken
{
    private const int VERSION = 1;
    private const int MAX_ENCODED_LENGTH = 4096;
    private const int MAX_PUBLIC_NAME_LENGTH = 512;

    public static function encode(string $publicName, string $csrfToken, bool $useOnce): string
    {
        $payload = json_encode([
            'v' => self::VERSION,
            'name' => $publicName,
            'token' => $csrfToken,
            'once' => $useOnce,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return base64_encode($payload);
    }

    /**
     * @return array{0: string, 1: string, 2: bool}|null
     */
    public static function decode(mixed $encoded): ?array
    {
        if (!is_string($encoded) || $encoded === '' || strlen($encoded) > self::MAX_ENCODED_LENGTH) {
            return null;
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || $raw === '' || strlen($raw) > self::MAX_ENCODED_LENGTH) {
            return null;
        }

        try {
            $payload = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($payload) || count($payload) !== 4) {
            return null;
        }

        $version = $payload['v'] ?? null;
        $publicName = $payload['name'] ?? null;
        $csrfToken = $payload['token'] ?? null;
        $useOnce = $payload['once'] ?? null;

        if (
            $version !== self::VERSION
            || !is_string($publicName)
            || $publicName === ''
            || strlen($publicName) > self::MAX_PUBLIC_NAME_LENGTH
            || preg_match('/[\x00-\x1F\x7F]/', $publicName) === 1
            || !is_string($csrfToken)
            || preg_match('/^[a-f0-9]{64}$/D', $csrfToken) !== 1
            || !is_bool($useOnce)
        ) {
            return null;
        }

        return [$publicName, $csrfToken, $useOnce];
    }
}
