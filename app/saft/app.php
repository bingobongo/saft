<?php

namespace Saft;


Class App {
											#                 0 = off, 1 = on
	const									# -- START CONFIG -----------------
		ON = 1,								# app
		CACHE = 0,							# cache
		DEBUG_MODE = 1,						# debug mode, turn on for install
		CHRONO = 1,							# memory, memory peak usage; page
											#    creation time, CPU time (user,
											#    system) as comment in source
		JSON = 1,							# JSON, i.e. http://domain.tld/json
		ARCHIVE = 1,						# year/season filter, archive page
		POT_FILTER = 1,						# content pot filter
		PAGINATE = 1,						# paginate
		PREV_NEXT = 1,						# previous/next index/permalink
		PUBLIC_REGEX = '{^.*?/web/public}',	# regex that matches intern docu-
											#    ment root till items become
											#    publicly available, i.e. on a
											#    Joyent (Shared) SmartMachine
											#    {^.*?/web/public} or a local
											#    MAMP server {^.*?/MAMP/htdocs}
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
		$rw;


	public function __construct($appRoot){
		require_once('elves/elf.php');

		if (self::ON !== 1)
			Elf::sendExit(503, 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' cannot be accessed temporarily on this server. Please try again later.');

		self::$absolute = preg_replace(self::PUBLIC_REGEX, '', $appRoot) . '/';
		self::$root = $appRoot;
		self::$potRoot = $appRoot . '/pot';

		if (is_dir(self::$root . '/app/maat') === true)
			self::$maat = 1;

		$this->__getPerms();

		if (self::DEBUG_MODE === 1){
			require_once('elves/env.php');
			$env = new Env();
			unset($env);
		}

		$this->__checkURI();
		require_once('elves/pot.php');
		require_once('elves/pilot.php');

		# check for Maat author

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
	}


	private function __getPerms(){
		$processOwner = posix_getpwuid(posix_geteuid());
		$processOwner = $processOwner['name'];

		# Joyent SmartMachine (also applies to many other server environments)

		if (	$processOwner === 'www'
			or	strpos(self::$root, '/users/home/') !== 0
		)
			self::$perms = array(
				'app' => 0710,
				'app_parts' => 0640,
				'asset' => 0775,			# /pot must be group-writable!
				'asset_parts' => (self::$maat === 1 ? 0664 : 0644),
				'cache' => 0770
			);

		# Joyent Shared SmartMachine

		else
			self::$perms = array(
				'app' => 0700,
				'app_parts' => 0600,
				'asset' => 0755,
				'asset_parts' => 0644,
				'cache' => 0700
			);
	}


	# @param	string	initialize variable
	# @return	string	by reference or redirect

	private function __checkURI($location = null){
		$absolute = self::$absolute;
		$rw =
		self::$rw = preg_replace('@/{2,}@i', '/', trim(strip_tags(strtolower(rawurldecode($_GET['rw']))), ' /'), -1, $r);

		# a-zA-Z0-9, underscore, minus, slash are valid

		if (	$rw !== ''
			&&	preg_match('{^[\w/-]+$}i', $rw) === 0
		)
			$location = $absolute;

		# multiple slashes

		else if ($r !== 0)
			$location = '/' . $rw . '/';

		# invalid path ($rw path does not start with $absolute path)

		else if (strpos($rw === '' ? '/' : '/' . $rw . '/', $absolute) !== 0){

			# $rw path may not start with "pot", htaccess should handle this

			if (strpos($rw, 'pot') === 0)
				$rw = strpos($rw, '/') !== false
					? substr($rw, strpos($rw, '/') - 1)
					: $absolute;
			else
				$rw = $absolute;

			$location = strrchr($rw, '/') !== '/'
				? $rw . '/'
				: $rw;
		}

		if ($location !== null)
			Elf::redirect('location: http://' . $_SERVER['HTTP_HOST'] . $location);

		# make ready for routing

		self::$rw = substr(self::$rw, strlen($absolute) - 1);

		unset($absolute, $location, $rw);
	}

}
