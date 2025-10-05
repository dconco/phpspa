<?php

use phpSPA\Component;

return (new Component(function () {
	$time = date('h:i:s');
	return "Time: $time";
}))
	->route('/timer')
	->title('Timer')
	->reload(1000);
