<?php

namespace Saft;


Class Index {

	public function __construct(){
		$this->__index();
	}


	protected function __index(){
		# make sure that utf-8 is set for multibyte content string-fu
		mb_internal_encoding('utf-8');
		Pilot::getContentType($contentType, $cachename);

		if (Pilot::$path === 0)
			Pilot::$path = App::$potRoot;
		# i.a. checks for outdated list cache, etagExpired check depends on that
		$entries = Pilot::scan();

		if (App::CACHE === 1){
			# $arrCache = cache path of entry list,
			#    for hash creation and to verify cache relevance
			$arrCache = Pilot::$contentPot === 0
				? App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX
				: App::$cacheRoot . '/' . Pilot::$contentPot . '.' . App::ARR_CACHE_SUFFIX;

			Elf::etagExpired($arrCache);
			Elf::sendStandardHeader($contentType);

			if ($cachename !== 'sitemap.xml')
				$this->__adapt($entries, $cachename);

			# cache exists, up-to-date, ready for output
			if (Elf::cached($arrCache, $cachename) === 1){
				# if short form of PHP open tag (<?) is enabled, include of
				#    XML cache file (feed, sitemap) would throw Parse error
				#    due to its XML declaration(s)
				if (Elf::endsWith($cachename, '.xml') === true)
					echo file_get_contents(App::$cacheRoot . '/' . $cachename);
				else
					include(App::$cacheRoot . '/' . $cachename);

			} else {
				# start output buffering
				ob_start();
				$this->__build($entries, filemtime($arrCache));
				Elf::writeToFile(App::$cacheRoot . '/' . $cachename, ob_get_contents(), 'wb');
				# stop output buffering
				ob_end_flush();
			}

		} else {
			Elf::sendStandardHeader($contentType);

			if ($cachename !== 'sitemap.xml')
				$this->__adapt($entries, $cachename);
			# key() = latest entry by name only, may be inaccurate in some cases
			$this->__build($entries, empty($entries) === false
				? filemtime(key($entries))
				: filemtime(Pilot::$path)
			);
		}

		unset($arrCache, $cachename, $contentType);

		if (	App::CHRONO === 1
			&&	Pilot::$protocol !== 'json'
		)
			echo "\n" . Elf::getSqueezedStr();

		exit;
	}


	# @param	array
	# @param 	string
	# @param 	string	initialize variable
	# @return	array	by reference
	# @return	string	by reference

	protected function __adapt(&$entries, &$cachename, $namePart = ''){
		$month = Pilot::$month;
		$page = Pilot::$page;
		$size = Pilot::$size;
		$year = Pilot::$year;

		if (	$year !== 0
			or	$month !== 0
		){
			$patternA = $patternB = '';

			if ($year !== 0){
				$patternA.= $year;
				$namePart.= $patternA;
			}

			if ($month !== 0){

				if (is_array($month) === true){
					sort($month);
					# allow e.g. 12-10, too
					$namePart.= '.' . implode('-', $month);
					$patternB.= $patternA . $month[1];
					$patternA.= $month[0];

				} else {
					$namePart.= '.' . $month;
					$patternA.= $month;
				}
			}

			$this->__filter($entries, $patternA, $patternB);
		}

		$size = Pilot::$size = sizeof($entries);
		# 404 not found, beyond available pages
		if ($page > ceil($size / App::PER_PAGE))
			throw new Fruit('', 404);

		if ($page < 2){
			$page = Pilot::$page = 1;
			$entries = array_slice($entries, 0, App::PER_PAGE, true);

		} else
			$entries = array_slice($entries, App::PER_PAGE * $page - App::PER_PAGE, App::PER_PAGE, true);

		if ($cachename !== 'atom.xml'){
			$cachename = empty($namePart) === true
				? strval($page) . '.' . $cachename
				: trim($namePart, '.') . '_' . strval($page) . '.' . $cachename;
		}

		if (Pilot::$contentPot !== 0)
			$cachename = Pilot::$contentPot . '.' . $cachename;

		unset($month, $namePart, $page, $size, $year);
	}


	# @param	array
	# @param	string
	# @param	string
	# @return	array	by reference

	protected function __filter(&$entries, $patternA, $patternB = ''){
		# year and/or month
		if ($patternB === ''){
			$vars = array(strlen($patternA));
			$vars[] = $vars[0] === 2
				? 4
				: 0;
			$vars[] = intval($patternA);
			$entries = array_filter($entries, function($name) use(&$vars){
				list($length, $offset, $patternA) = $vars;
				$name = intval(substr($name, $offset, $length));
				return $name === $patternA;
			});
		# year and/or season
		} else {
			$vars = array(strlen($patternA));
			$vars[] = $vars[0] === 2
				? 4
				: 0;
			$vars[] = intval($patternA);
			$vars[] = intval($patternB);
			$entries = array_filter($entries, function($name) use(&$vars){
				list($length, $offset, $patternA, $patternB) = $vars;
				$name = intval(substr($name, $offset, $length));
				return ($name >= $patternA
					&&	$name <= $patternB
				);
			});
		}
	}

}
