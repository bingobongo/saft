<?php
														# Saft, Maat Version 0.4
define('PAGE_RENDER_START', microtime(true));			# start microchronometer to determine page creation time
ini_set('memory_limit', '16M');							# set max. amount of memory that is allowed to allocate
set_time_limit(8);										# set max. execution time in sec.

if (floatval(phpversion()) < 5.3)						# is faster than “version_compare(PHP_VERSION, ‘5.3.0’, ‘<’)”
	throw new Exception('PHP/5.3 or higher could not be found on this server.');

require_once('app/saft/app.php');
new \Saft\App(__DIR__);
