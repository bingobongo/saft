<?php

namespace Saft;


Class App {
											#                 0 = off, 1 = on
	const									# -- START CONFIG -----------------
		ON = 1,								# app
		CACHE = 0,							# cache
		DEBUG_MODE = 1,						# debug mode, turn on for install
		DEBUG_IP = '127.0.0.1|::1',			# vertical-bar-separated list of
											#    remote adrresses that may be
											#    shown extended debug info
		CHRONO = 1,							# memory, memory peak usage; page
											#    creation time, CPU time (user,
											#    system) as comment in source
		PAGINATE = 1,						# paginate
		PREV_NEXT = 1,						# previous/next index/permalink
		POT_FILTER = 1,						# content pot filter
		DATE_FILTER = 1,					# year/season filter, archive page
		PUBLIC_REGEX = '{^.*?/web/public}',	# regex that matches intern docu-
											#    ment root till items become
											#    publicly available, i.e. on a
											#    Joyent (Shared) SmartMachine
											#    {^.*?/web/public} or local
											#    MAMP {^.*?/MAMP/htdocs}
		LANG = 'en',						# language code
		ARCHIVE_STR = 'archive',			# "archive" for a URI like 
											#    http://domain.tld/archive
		PAGE_STR = 'page',					# "page" for a URI like
											#    http://domain.tld/page/1
		PER_PAGE = 20,						# number of entries that show up
											#    on index pages, feed
		DEPTH = 2,							# depth that is followed in each
											#    content pot; 0 = content pot,
											#    n = n levels down
		TITLE = 'Site Title',				# site title, site descripton 
		DESCR = 'Site Description',
											# -- END CONFIG -------------------
											#
											# valid content file extensions, in
											#    case of addition: align with
											#    RewriteRule (1) in htaccess +
											#    getFiletype() inside elf.php
											#    + checkPerms() inside env.php
		FILE_EXT = 'gif|jp(?:e|g|eg)|md|png|txt|text|webm|webp',
		ARR_CACHE_SUFFIX = 'array.json';	# entry/pot list cache file suffix

		# DO NOT EDIT ANYTHING BELOW HERE, UNLESS ONE IS DOING SOMETHING CUSTOM

	public static
		$absolute,
		$root,
		$potRoot,
		$propelRoot = __DIR__,
		$assetRoot,
		$cacheRoot,
		$baseURI,
		$baseURL,
		$today,
		$perms,
		$pots,
		$maat = 0,
		$author = 0,
		$archive = 0,
		$rw;


	public function __construct($appRoot){
		require_once('elves/elf.php');
		try {
			if (self::ON !== 1)
				throw new Fruit('', 503);

			self::$root = $appRoot;
			self::$potRoot = $appRoot . '/pot';
			self::$absolute = '/' . trim(preg_replace(rtrim(self::PUBLIC_REGEX, '/'), '', $appRoot), '/');

			if (self::$absolute !== '/')
				self::$absolute.= '/';

			if (is_dir(self::$root . '/app/maat') === true)
				self::$maat = 1;

			Elf::getPerms();

			if (self::DEBUG_MODE === 1){
				require_once('elves/env.php');
				$env = new Env();
				unset($env);
			}

			$this->__checkURI();
			require_once('elves/pot.php');
			require_once('elves/pilot.php');

			# check for Maat author in URI
			if (	strpos(self::$rw, 'maat/') === 0
				&&	self::$maat === 1
			){  
				require_once($appRoot . '/app/maat/app.php');
				new Maat($appRoot);
			}

			self::$assetRoot = $appRoot . '/asset/saft';
			self::$cacheRoot = $appRoot . '/cache/saft';
			self::$baseURI = ltrim(self::$absolute, '/');
			self::$baseURL = 'http://' . $_SERVER['HTTP_HOST'] . self::$absolute;
			self::$today = Elf::getCurrentDate();
			new Pilot();
		} catch (Fruit $f){
			$f->squeeze();
		}
	}


	# @param	string	initialize variable
	# @return	string	by reference or redirect

	private function __checkURI($location = null){
		$rw = self::$rw = preg_replace('@/{2,}@i', '/', trim(strip_tags(strtolower(rawurldecode($_GET['rw']))), ' /'), -1, $r);

		# a-zA-Z0-9, underscore, minus, slash are valid
		if (	$rw !== ''
			&&	preg_match('{^[\w/-]+$}i', $rw) === 0
		)
			$location = self::$absolute;

		# multiple slashes
		else if ($r !== 0)
			$location = '/' . $rw . '/';

		# invalid path, $rw must start with $absolute path
		else if (strpos($rw === '' ? '/' : '/' . $rw . '/', self::$absolute) !== 0){

			# $rw may not start with "pot", htaccess usually handles this
			if (strpos($rw, 'pot') === 0)
				$rw = strpos($rw, '/') !== false
					? substr($rw, strpos($rw, '/') - 1)
					: self::$absolute;
			else
				$rw = self::$absolute;

			$location = strrchr($rw, '/') !== '/'
				? $rw . '/'
				: $rw;
		}

		if ($location !== null)
			throw new Fruit('http://' . $_SERVER['HTTP_HOST'] . $location, 301);

		# make ready for routing
		self::$rw = substr(self::$rw, strlen(self::$absolute) - 1) or null;
		unset($location, $rw);
	}

}
