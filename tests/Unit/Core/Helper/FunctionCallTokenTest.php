<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\FunctionCallToken;

final class FunctionCallTokenTest extends TestCase
{
    public function testRoundTripsJsonTokenData(): void
    {
        $csrf = str_repeat('a', 64);
        $encoded = FunctionCallToken::encode('Profile::save', $csrf, true);

        $this->assertSame(['Profile::save', $csrf, true], FunctionCallToken::decode($encoded));
    }

    public function testRejectsLegacySerializedAndObjectPayloads(): void
    {
        FunctionCallTokenWakeupProbe::$woke = false;
        $encoded = base64_encode(serialize(new FunctionCallTokenWakeupProbe()));

        $this->assertNull(FunctionCallToken::decode($encoded));
        $this->assertFalse(FunctionCallTokenWakeupProbe::$woke);
    }

    public function testRejectsMalformedTokenFields(): void
    {
        $payload = base64_encode(json_encode([
            'v' => 1,
            'name' => 'strlen',
            'token' => 'short',
            'once' => false,
        ], JSON_THROW_ON_ERROR));

        $this->assertNull(FunctionCallToken::decode($payload));
    }
}

final class FunctionCallTokenWakeupProbe
{
    public static bool $woke = false;

    public function __wakeup(): void
    {
        self::$woke = true;
    }
}
