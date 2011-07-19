<?php

namespace Saft;


Class Pilot extends Pot {

	public static
		$archive = 0,						# for navigate
		$contentPot = 0,
		$month = 0,
		$page = 0,							# for paginate
		$pageType = 0,
		$protocol = 0,
		$size = 0,							# for paginate
		$year = 0;


	public function __construct(){
		parent::__construct();
		$this->__route();
	}


	protected function __route(){
		$params = $page = $size = $rw = App::$rw;

		if ($rw !== null){
			$params = explode('/', $rw);
			$page = self::$protocol = end($params);
			$size = sizeof($params);
		}

		if (	App::DATE_FILTER === 1
			&&	is_file(App::$propelRoot . '/archive/archive.php') === true
		)
			self::$archive = 1;

		$this->__setRoute($params, $page, $rw, $size);
	}


	# @param	array, integer or string
	# @param	integer or string
	# @param	integer or string
	# @param	integer

	protected function __setRoute(&$params, &$page, &$rw, &$size){

		if ($page === 'json'){
			# html, json root (page 1)
			if ($size === 1)
				$params = $page = $size = null;
			# prepare for further routing (shift json; update $size, $page)
			else {
				array_pop($params);
				$page = end($params);
				$rw = substr($rw, 0, strrpos($rw, '/'));
				--$size;
			}
		}

		switch ($page){
			case null:
				$this->__initialize('index');
				break;

			case 'sitemap':
				if ($size === 1){
					self::$pageType = 'index';
					self::$protocol = $page;
					$this->__load();
					new Sitemap();
					break;
				}
				continue;

			case 'atom':
				# root, altogether
				if ($size === 1){
					self::$pageType = 'index';
					self::$protocol = $page;
					$this->__load();
					new Atom();
					break;
				}

				# content pot, filtered
				if (	$size === 2
					&&	parent::$path = $this->__isPot($params[0])
				){
					if (App::POT_FILTER === 1){
						self::$pageType = 'index';
						self::$contentPot = basename(parent::$path);
						self::$protocol = $page;
						$this->__load();
						new Atom();
					# redirect to root in case of disabled pot filter
					} else
						throw new Fruit(App::$baseURL . $page . '/', 301);

					break;
				}
				continue;

			case strtolower(App::ARCHIVE_STR):
				# archive, there can only be one;
				#    App::ARCHIVE_STR must not comply with page-type name
				if (	$size === 1
					&&	self::$archive === 1
				){
					$this->__initialize('archive');
					break;
				}
				continue;

			default:

				if ($this->__filter($params, $page, $rw, $size) === 0)
					break;

				unset($page, $params, $rw, $size);
				$this->__initialize('index');
				break;
		}
		# 404 not found
		throw new Fruit('', 404);
	}


	# @param	array, integer or string
	# @param	integer or string
	# @param	integer or string
	# @param	integer
	# @integer	integer

	protected function __filter(&$params, &$page, &$rw, &$size){

		if (parent::$path = $this->__isPot($params[0])){
			self::$contentPot = basename(parent::$path);

			# html, json content pot (page 1)
			if ($size === 1)
				return App::POT_FILTER === 1
					? 1
					: 0;

			# permalink
			if (	preg_match('{
					^
					[\w-]+						# content pot
					/\d{4}						# slash yyyy
					/(?:0[1-9]|1[012])			# slash mm
					/(?:0[1-9]|[12][0-9]|3[01])	# slash dd
					/[\w-]+						# slash permalink
					$
					}ix', $rw) === 1
				&&	(parent::$path = $this->__isEntry())
			)
				$this->__initialize('permalink');

			# shift content pot, update size
			array_shift($params);
			$rw = substr($rw, strpos($rw, '/') + 1);
			--$size;
		}

		if (	self::$contentPot !== 0
			&&	(	App::POT_FILTER === 0
				or	App::DATE_FILTER === 0
				)
		)
			return 0;

		# html, json root/content pot (page 1)
		if (preg_match('{
			^
			(?:
				(?:
					\d{4}					# yyyy
					(?:
						/						# slash
						(0[1-9]|1[012])			# mm
						(?:-(0[1-9]|1[012]))?	# -mm
					)?
				)
			|							# or
				(?:
					(0[1-9]|1[012])			# mm
					(?:-(0[1-9]|1[012]))?	# -mm
				)
			)
			$
			}ix', $rw) === 1
		){
			# 2 indicates yyyy/mm[-mm] or mm[-mm] or yyy
			$rw = 2;

		# html, json root/content pot (page 1+)
		} else if (preg_match('{
			^
			(?:
				(?:
					\d{4}/					# yyyy
					(
						(0[1-9]|1[012])			# mm
						(?:-(0[1-9]|1[012]))?	# -mm
						/						# slash
					)?
				)
			|							# or
				(?:
					(0[1-9]|1[012])			# mm
					(?:-(0[1-9]|1[012]))?	# -mm
					/						# slash
				)
			)?
			' . preg_quote(App::PAGE_STR) . '
			/[1-9](?:\d+)?			# page slash n
			$
			}ix', $rw) === 1
		){
			# 4 indicates yyyy/mm[-mm]/page/n or mm[-mm]/page/n or yyyy/page/n
			$rw = 4;
			self::$page = intval($page);

		} else
			return 0;

		if ($params[0] !== strtolower(App::PAGE_STR)){

			if (App::DATE_FILTER === 0)
				return 0;

			$year = $month = $params[0];
			# year, month
			if ($size === $rw){
				$month = $params[1];
				# season
				if (strpos($month, '-') !== false)
					$month = array(
						substr($month, 0, 2),
						substr($month, 3)
					);
			# year | month
			} else {
				# month
				if (intval($month) <= 12){
					$year = 0;
					$month = $params[0];
					# season
					if (strpos($month, '-') !== false)
						$month = array(
							substr($month, 0, 2),
							substr($month, 3)
						);
				# year
				} else
					$month = 0;
			}

			self::$year = $year;
			self::$month = $month;
			unset($month, $year);
		}

		return 1;
	}


	# @return	string or integer

	protected function __isEntry(){
		$entry = preg_grep('{^' . preg_quote(Elf::URIpartToFilenamePart()) . '\.(?:' . App::FILE_EXT . ')$}i', parent::scan());
		return empty($entry) === false
			? key($entry)
			: 0;
	}


	# @param	string
	# @return	string or integer

	protected function __isPot($name){
		# strtolower() permits uppercase content pot names
		foreach (App::$pots as $path){
			if (Elf::endsWith(strtolower($path), '/' . $name))
				return $path;
		}
		return 0;
	}


	# @param	string
	# @param	string

	protected function __initialize($pageType, $part = 'app/saft/'){
		self::$pageType = $pageType;

		if (self::$protocol === 'json'){
			$this->__load($part);
			new JSON();

		} else {
			self::$protocol = 'html';
			# chooses from app or extension through App::$propelRoot
			require_once(App::$propelRoot . '/nav.php');
			$this->__load($part);
			new Html();
		}
	}


	# @param	string

	protected function __load($part = 'app/saft/'){
		# 404 not found
		if (	is_file(App::$propelRoot . '/' . self::$pageType . '/' . self::$protocol . '.php') === false
			or	is_file($part . self::$pageType . '/' . self::$pageType . '.php') === false
		)
			throw new Fruit('', 404);

		require_once($part . self::$pageType . '/' . self::$pageType . '.php');
		# chooses from app or extension through App::$propelRoot
		require_once(App::$propelRoot . '/' . self::$pageType . '/' . self::$protocol . '.php');
	}


	# @param	string
	# @param	string
	# @return	string	by reference
	# @return	string	by reference

	public static function getContentType(&$contentType, &$cachename = ''){
		$cachename.= empty($cachename) === false
			# permalink
			? '.' . self::$protocol
			: self::$protocol;

		switch (self::$protocol){
			case 'sitemap':
				$cachename.= '.xml';
				$contentType = 'text/xml';
				break;

			case 'atom':
				$cachename.= '.xml';
				$contentType = 'application/atom+xml';
				break;
			# json must come before html to get html as needed
			case 'json':
				$contentType = 'application/json';
				break;
			# html
			default:
				$contentType = 'text/html';
				break;
		}
	}

}
