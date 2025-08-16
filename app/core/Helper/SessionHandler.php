<?php

namespace phpSPA\Core\Helper;

use phpSPA\Http\Session;

class SessionHandler
{
	static function get(string $session): mixed
	{
		$default = serialize([]);
		$default = base64_encode($default);

		$sessionData = base64_decode(Session::get($session, $default));
		$sessionData = unserialize($sessionData);

		return $sessionData;
	}

	static function set(string $session, $value): void
	{
		$v = serialize($value);
		Session::set($session, base64_encode($v));
	}
}
