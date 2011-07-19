<?php

namespace Saft;


Class Pot {

	# BEWARE: the timestamp of a directory does not reflect
	#    the changes that are made inside a subdirectory of it

	public static $path = 0;


	public function __construct(){

		if (App::CACHE === 1){
			$arrCachename = 'pot.' . App::ARR_CACHE_SUFFIX;

			if (Elf::cached(App::$potRoot, $arrCachename) === 0){
				$this->__globPot();

				$permsCache = App::$perms['cache'];
				Elf::makeDirOnDemand(App::$root . '/cache', $permsCache);
				Elf::makeDirOnDemand(App::$cacheRoot, $permsCache);

				foreach (App::$pots as $path){
					$path = App::$cacheRoot . '/' . basename($path);
					Elf::makeDirOnDemand($path, $permsCache);
				}

				Elf::writeToFile(App::$cacheRoot . '/' . $arrCachename, json_encode(App::$pots), 'wb');
				unset($arrCachename, $path, $permsCache);

			} else
				App::$pots = $this->__cacheDecoded($arrCachename);

		} else
			$this->__globPot();
	}


	protected function __globPot(){
		App::$pots = glob(preg_quote(App::$potRoot) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
		App::$pots = array_filter(App::$pots, function($path){
			return preg_match('{^[\w-]+$}i', basename($path)) === 1;
		});
		unset($path);
	}


	# @return	array	path => name

	public static function scan(){
		return App::CACHE === 1
			? self::__cache()
			: self::getEntries(self::$path);
	}


	# @param	string
	# @return	array	path => name

	protected function __cache(){
		$entries = array();
		$changed =
		$r = 0;
		$pots =	self::$path === App::$potRoot
			? App::$pots
			: array(self::$path);
		$size = sizeof($pots);

		foreach ($pots as $path){
			$arrCachename = basename($path) . '.' . App::ARR_CACHE_SUFFIX;

			# no Maat author => yes, scheduled entries
			if (App::$author === 0){
				$note = App::$cacheRoot . '/' . basename($path) . '.note.txt';

				if (is_readable($note) === false)
					$note = 0;
			} else
				$note = 0;

			if (	file_exists($path . '/0_REMOVE-TO-FORCE-CACHE-UPDATE.txt') === false
				or	Elf::cached($path, $arrCachename) === 0
				or	(	$note !== 0
					&&	intval(file_get_contents($note)) <= App::$today
					)
			){
				$parts = self::getEntries($path);
				Elf::writeToFile(App::$cacheRoot . '/' . $arrCachename, json_encode($parts), 'wb');
				# mark as cached
				$path = fopen($path . '/0_REMOVE-TO-FORCE-CACHE-UPDATE.txt', 'wb');
				fclose($path);

				# reset memory of scheduled entries
				if ($note !== 0)
					unlink($note);

				# += is possible as long as array key = path of entry (= unique)
				if (empty($parts) === false)
					$entries+= $parts;

				unset($pots[$r]);
				$changed = 1;

			} else
				$pots[$r] = $arrCachename;

			++$r;
		}

		if ($changed === 0){

			if (self::$path === App::$potRoot){
				# check for root cache file and for removed content pots
				if (Elf::cached(App::$potRoot, App::ARR_CACHE_SUFFIX) === 1){
					unset($arrCachename, $note, $path, $pots);
					return self::__cacheDecoded(App::ARR_CACHE_SUFFIX);
				}

			# assumes that base and content pot URI are valid
			#    (no further subdirectories nor combined or permalink URI)
			} else {
				unset($note, $path, $pots);
				return self::__cacheDecoded($arrCachename);
			}
		}

		foreach ($pots as $arrCachename)
			$entries+= self::__cacheDecoded($arrCachename);

		if ($size > 1){
			natcasesort($entries);
			$entries = array_reverse($entries);
		}

		reset($entries);

		if (self::$path === App::$potRoot)
			Elf::writeToFile(App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX, json_encode($entries), 'wb');

		unset($arrCachename, $note, $parts, $path, $pots, $size);
		return $entries;
	}


	# @param	string
	# @return	string

	protected function __cacheDecoded($name){
		return json_decode(file_get_contents(App::$cacheRoot . '/' . $name), true);
	}


	# @param	string or array
	# @param	string
	# @return	array	path => name

	public static function getEntries($potRoot, $regex = ''){

		if ($regex === '')
			$regex = '{
				^
				\d{4}						# yyyy
				(?:0[1-9]|1[012])			# mm
				(?:0[1-9]|[12][0-9]|3[01])	# dd
				\s							# space
				[\w-]+						# permalink
				\.(?:' . App::FILE_EXT . ')	# .file extension
				$
				}ix';

		if (is_array($potRoot) === true)
			$pots = $potRoot;

		else if (Elf::endsWith($potRoot, '/pot'))
			$pots = App::$pots;
		else
			$pots = array($potRoot);

		if (empty($pots) === true)
			return 0;

		$level = 0;
		$depth = App::DEPTH;
		$today = App::$today;
		$subpots =
		$entries =
		$note = array();

		while ($level <= $depth){

			foreach ($pots as $path){
				$dir = scandir($path);

				foreach ($dir as $item){

					if (strpos($item, '.') === 0)
						continue;

					$itemPath = $path . '/' . $item;

					if (is_readable($itemPath) === false)
						continue;

					if (is_dir($itemPath) === true){

						if (	$level < $depth
							&&	preg_match('{^[\w-]+$}i', $item) === 1
						)
							$subpots[] = $itemPath;
						else
							continue;

					} else if (preg_match($regex, $item) === 1){

						if (intval($item) <= $today)
							$entries[$itemPath] = basename($itemPath);
						else
							$note[preg_replace('{^/([\w-]+).*$}i', '$1', str_replace(App::$potRoot, '', $itemPath))] = basename($itemPath);

					} else
						continue;

					reset($entries);
					reset($subpots);
				}
			}

			clearstatcache();
			# update pots array
			$pots = $subpots;
			# empty subpots array
			$subpots = array();
			++$level;
		}

		# write memory of scheduled entries
		if (	App::CACHE === 1
			&&	empty($note) === false
		)
			self::__remember($note);

		unset($note, $potRoot, $pots, $subpots, $today);
		natcasesort($entries);
		return array_reverse($entries);
	}


	# @param	array	potname => filename entry

	protected function __remember(&$note){
		natcasesort($note);
		$name = key($note);
		# yyyymmdd = 8
		$date = substr(array_shift($note), 0, 8);
		Elf::writeToFile(App::$cacheRoot . '/' . $name . '.note.txt', $date, 'wb');
		unset($date, $name);
	}

}
