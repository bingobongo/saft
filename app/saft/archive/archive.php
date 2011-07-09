<?php

namespace Saft;


Class Archive {

	private static $seasonPattern = array(
		'0[1-3]*',										# spring
		'0[4-6]*',										# summer
		'0[7-9]*',										# autumn
		'1[0-2]*'										# winter
	);


	public function __construct(){
		$this->__archive();
	}


	protected function __archive(){
		Pilot::getContentType($contentType, $cachename);
		Pilot::$path = App::$potRoot;
														# i.a. checks for outdated entry list cache,
		$entries = Pilot::scan();						#    “etagExpired” check depends on that

		if (App::CACHE === 1){							# … cache path of entry list for hash creation and to verify cache relevance
			$arrCache = App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX;
			Elf::etagExpired($arrCache);
			Elf::sendStandardHeader($contentType);		# “App::ARCHIVE_STR” (not “Pilot::$pageType”, won’t comply with URI)
			$cachename = App::ARCHIVE_STR . '.' . $cachename;

														# cache exists, up-to-date and ready for output
			if (Elf::cached($arrCache, $cachename) === 1)
				include(App::$cacheRoot . '/' . $cachename);

			else {
				ob_start();								# start output buffering

				$this->__prepare($entries, $arrCache);	# $arrCache used for and becomes $lastmod
				$this->__build($entries, $arrCache);
				Elf::writeToFile(App::$cacheRoot . '/' . $cachename, ob_get_contents(), 'wb');
				ob_end_flush();							# stop output buffering
			}

		} else {
			Elf::sendStandardHeader($contentType);

			$this->__prepare($entries, $lastmod);
			$this->__build($entries, $lastmod);
		}

		unset($cachename, $contentType);

		if (	App::CHRONO === 1
			&&	Pilot::$protocol !== 'json'
		)
			echo "\n" . Elf::getSqueezedStr();

		exit;
	}


	# @param	array
	# @return	array	by reference
	# @return	integer	by reference

	protected function __prepare(&$entries, &$lastmod){

		if (empty($entries) === false){
			$lastmod = empty($lastmod) === false		# bit faster than “App::CACHE === 1”
				? filemtime($lastmod)
				: filemtime(key($entries));				# “key(…)” = latest entry by name only, may be inaccurate in some cases

			$this->__getPrepared($entries);

			if (App::POT_FILTER === 1){

				foreach (App::$pots as $path){
					Pilot::$path = $path;				# change path to comply with the assumption of base of “cache()”
					$contentPotEntries = Pilot::scan();	#    function and the URIs of content pots in Pot class

					if (empty($contentPotEntries) === false){
						$this->__getPrepared($contentPotEntries);
						$entries[basename($path)] = $contentPotEntries[0];
					}
				}

				unset($contentPotEntries, $path);
			}

		} else {
			$entries = 0;
			$lastmod = empty($lastmod) === false
				? filemtime($lastmod)
				: filemtime(Pilot::$path);
		}
	}


	# @param	array
	# @return	array	by reference

	protected function __getPrepared(&$entries){
		$till = intval(substr(current($entries), 0, 4)) + 1;
		$from = intval(substr(end($entries), 0, 4));

		array_walk($entries, function(&$name){
			$name = intval(substr($name, 0, 6));
		});

		$str = implode('-', array_flip(array_flip($entries)));
		$entries = array();

		foreach (self::$seasonPattern as $pattern){		# YYYY
			$pattern = '*[1-2][0-9][0-9][0-9]' . $pattern;

			if (fnmatch($pattern, $str) === true)
				$entries[0][0][] = 1;
			else
				$entries[0][0][] = 0;
		}

		$null;

		while (--$till >= $from){
			$null = 0;

			foreach (self::$seasonPattern as $pattern){
				$pattern = '*' . strval($till) . $pattern;

				if (fnmatch($pattern, $str) === true){
					$entries[0][$till . ' '][] = 1;		# “' '” lets keep keys after “array_shift”
					$null = 1;

				} else
					$entries[0][$till . ' '][] = 0;
			}

			if ($null === 0)							# omit years without activity
				unset($entries[0][$till . ' ']);
		}

		unset($from, $name, $pattern, $str, $till);
	}

}
