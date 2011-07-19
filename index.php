<?php

# start microchronometer to determine page creation time
define('RENDER_START', microtime(true));
# set max. amount of memory that is allowed to allocate
ini_set('memory_limit', '16M');
# set max. execution time in sec.
set_time_limit(8);
# check for PHP version, is faster than version_compare()
if (floatval(phpversion()) < 5.3)
	exit('<!doctype html><html dir=ltr lang=en><head><meta charset=utf-8><title>Forget the machine!</title><meta name=robots content=noarchive><body><h1>Forget the machine!</h1><p>I found no PHP/5.3 or higher on it.');

require_once('app/saft/app.php');
new \Saft\App(__DIR__);
