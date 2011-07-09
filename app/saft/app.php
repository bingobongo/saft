<?php

namespace Saft;


Class App {												#                 0 = off, 1 = on
														# -- START CONFIG ---------------
	const ON = 1,										# app
		  CACHE = 0,									# cache
		  DEBUG_MODE = 1,								# debug mode, turn on for install
		  CHRONO = 1,									# memory, memory peak usage; page creation time and
														#    CPU time (user, system) as comment in source code
		  JSON = 1,										# JSON, i.e. http://domain.tld/json/
		  ARCHIVE = 1,									# year/season filtering, archive page
		  POT_FILTER = 1,								# content pot filtering
		  PAGINATE = 1,									# paginate
		  PREV_NEXT = 1,								# previous/next index/permalink
		  PUBLIC_REGEX = '{^.*?/web/public}',	 		# regex to match intern document root of the server till items
														#    become publicly available, highly dependent on server: i.e.
														#        “{^.*?/web/public}” on a Joyent (Shared) SmartMachine or
														#        “{^.*?/MAMP/htdocs}” on a local MAMP server
		  LANG = 'en',									# language code
		  ARCHIVE_STR = 'archive',						# i.e. “archive” for a uri like http://domain.tld/archive/
		  PAGE_STR = 'page',							# i.e. “page” for a uri like http://domain.tld/page/1/
		  PER_PAGE = 20,								# number of entries that show up on index pages and feed
		  DEPTH = 2,									# depth that sniffer follows in each content pot,
														#    0 = content pot only and n = n levels down
		  TITLE = 'Site Title',							# site title and descripton 
		  DESCR = 'Site Description',
	# ODOMETER     ..........10........20........30........40........50........60........70........80........90........100.......110.......120.......130.......140.......150 CHARACTERS
														# -- END CONFIG -----------------
														# valid content file extensions, in case of addition:
														#    align with RewriteRule (1) in htaccess
		  FILE_EXT = 'gif|jp(?:e|g|eg)|md|png|txt|text|webm|webp',
		  ARR_CACHE_SUFFIX = 'array.json';				# suffix for entry/pot list cache files

	public static $absolute,
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

		if (self::ON === 1){
			require_once('elves/y.php');
			$y = new Y($appRoot);
			unset($y);

		} else
			Elf::sendExit(503, 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' cannot be accessed temporarily on this server. Please try again later.');

		new Pilot();
	}

}
