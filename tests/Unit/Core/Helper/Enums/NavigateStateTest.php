<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\Enums\NavigateState;

final class NavigateStateTest extends TestCase
{
   public function testEnumValues(): void
   {
      $this->assertSame('push', NavigateState::PUSH->value);
      $this->assertSame('replace', NavigateState::REPLACE->value);
   }
}
