<?php

use PhpSPA\Component;

return (new Component(function () {
	$time = date('h:i:s');
	return "Time: $time";
}))
	->route('/timer')
	->title('Timer')
	->reload(800);
