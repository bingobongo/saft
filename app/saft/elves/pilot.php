<?php

namespace Saft;


Class Pilot extends Pot {

	public static
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
		$rw = App::$rw;

		if ($rw === false){
			$params =
			$page =
			$size = null;

		} else {
			$params = explode('/', $rw);
			$page =							# normally htaccess handles feed and
			self::$protocol = end($params);	#    sitemap URI to match syntax, it
			$size = sizeof($params);		#    cuts the thang behind slash off
		}

		$this->__setRoute($params, $page, $rw, $size);
	}


	# @param	array, integer or string
	# @param	integer or string
	# @param	integer or string
	# @param	integer

	protected function __setRoute(&$params, &$page, &$rw, &$size){

		if (	App::JSON === 1
			&&	$page === 'json'
		){

			if ($size === 1)
				$params =
				$page =
				$size = null;

			else {							# shift json, update size and $page
				array_pop($params);
				$rw = substr($rw, 0, strrpos($rw, '/'));
				--$size;
				$page = end($params);
			}
		}

		switch ($page){
			case null:

				# html, json root (page 1)

				$this->__initialize('index');
				break;

			case 'sitemap':

				# sitemap altogether

				if ($size === 1){
					self::$pageType = 'index';
					self::$protocol = $page;
					$this->__load();
					new Sitemap();
					break;
				}

				continue;

			case 'atom':

				# feed root

				if ($size === 1){
					self::$pageType = 'index';
					self::$protocol = $page;
					$this->__load();
					new Atom();
					break;
				}

				# feed content pot

				if (	$size === 2
					&&	parent::$path = $this->__isPot($params[0])
				){
					if (App::POT_FILTER === 1){
						self::$pageType = 'index';
						self::$contentPot = basename(parent::$path);
						self::$protocol = $page;
						$this->__load();
						new Atom();

					} else
						Elf::redirect('location: ' . App::$baseURL . $page . '/');

					break;
				}

				continue;

			case strtolower(App::ARCHIVE_STR):

				if (App::ARCHIVE === 1){

					# archive root

					if ($size === 1){
						$this->__initialize('archive');
						break;				# because App::ARCHIVE_STR must not
					}						#    comply with page-type name

					# archive content pot

					if (	$size === 2
						&&	parent::$path = $this->__isPot($params[0])
					){
						Elf::redirect('location: ' . App::$baseURL . $page . '/');
						break;
					}
				}

				continue;

			default:

				if ($this->__filterRoute($params, $page, $rw, $size) === 0)
					break;

				unset($page, $params, $rw, $size);
				$this->__initialize('index');
				break;
		}

		# 404 not found

		Elf::sendExit(404, 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found on this server.');
	}


	# @param	array, integer or string
	# @param	integer or string
	# @param	integer or string
	# @param	integer
	# @integer	integer

	protected function __filterRoute(&$params, &$page, &$rw, &$size){

		if (parent::$path = $this->__isPot($params[0])){
			self::$contentPot = basename(parent::$path);

			# html, json content pot (page 1)

			if ($size === 1){

				if (App::POT_FILTER === 1)
					$this->__initialize('index');

				return 0;
			}

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
			){
				$this->__initialize('permalink');
				return 0;
			}

			array_shift($params);			# shift content pot, update size
			$rw = substr($rw, strpos($rw, '/') + 1);
			--$size;
		}

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
		){									# indicate yyyy/mm[-mm]
			$rw = 2;						#    or mm[-mm] or yyyy

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
			self::$page = intval($page);	# indicate yyyy/mm[-mm]/page/n or
			$rw = 4;						#    mm[-mm]/page/n or yyyy/page/n

		# 404 not found

		} else
			return 0;

		if (	self::$contentPot !== 0
			&&	(	App::ARCHIVE === 0
				or	App::POT_FILTER === 0
				)
		)
			return 0;

		if ($params[0] !== strtolower(App::PAGE_STR)){

			if (App::ARCHIVE === 0)
				return 0;

			$year =
			$month = $params[0];

			if ($size === $rw){				# year, month
				$month = $params[1];			# season
				if (strpos($month, '-') !== false)
					$month = array(
						substr($month, 0, 2),
						substr($month, 3)
					);

			} else {

				if (intval($month) > 12)	# year
					$month = 0;

				else {						# month
					$year = 0;
					$month = $params[0];		# season
					if (strpos($month, '-') !== false)
						$month = array(
							substr($month, 0, 2),
							substr($month, 3)
						);
				}
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

		return empty($entry) === true
			? 0
			: key($entry);
	}


	# @param	string
	# @return	string or integer

	protected function __isPot($name){		# strtolower($path) permits
											#    uppercase characters for
		foreach (App::$pots as $path){		#    pot names as well
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
			require_once(App::$propelRoot . '/nav.php');
			$this->__load($part);			# chooses from app or its extension
			new Html();						#    through App::$propelRoot
		}
	}


	# @param	string						# chooses from app or its extension
											#    through App::$propelRoot
	protected function __load($part = 'app/saft/'){
		require_once($part . self::$pageType . '/' . self::$pageType . '.php');
		require_once(App::$propelRoot . '/' . self::$pageType . '/' . self::$protocol . '.php');
	}


	# @param	string
	# @param	string
	# @return	string	by reference
	# @return	string	by reference

	public static function getContentType(&$contentType, &$cachename = ''){
		$cachename.= empty($cachename) === false
			? '.' . self::$protocol			# permalink
			: self::$protocol;

		switch (self::$protocol){			# json must come before html in
			case 'sitemap':					#    order to get html as needed
				$cachename.= '.xml';
				$contentType = 'text/xml';
				break;

			case 'atom':
				$cachename.= '.xml';
				$contentType = 'application/atom+xml';
				break;

			case 'json':
				$contentType = 'application/json';
				break;

			default:						# html
				$contentType = 'text/html';
				break;
		}
	}

}
