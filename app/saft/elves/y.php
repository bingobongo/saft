<?php

namespace Saft;


Class Y {


	// @param	string

	public function __construct($appRoot){
		App::$absolute = preg_replace(App::PUBLIC_REGEX, '', $appRoot) . '/';
		App::$root = $appRoot;
		App::$potRoot = $appRoot . '/pot';
		App::$assetRoot = $appRoot . '/asset';
		App::$cacheRoot = $appRoot . '/cache';

		if (is_dir(App::$root . '/app/maat') === true)
			App::$maat = 1;

		App::$perms = Elf::getPerms();

		if (App::DEBUG_MODE === 1){
			require_once('machine.php');
			$machine = new Machine();
			unset($machine);
		}

		$this->__checkURI();
		require_once('pot.php');
		require_once('pilot.php');

		if (	strpos(App::$rw, 'maat/') === 0			# “/” because the author name must be in the URI, too
			&&	App::$maat === 1
			&&	$this->__extend() !== 0
		){
			require_once($appRoot . '/app/maat/app.php');
			new Maat($appRoot);
			exit;

		} else {
			App::$assetRoot.= '/saft';
			App::$cacheRoot.= '/saft';
			App::$baseURI = ltrim(App::$absolute, '/');
			App::$baseURL = 'http://' . $_SERVER['HTTP_HOST'] . App::$absolute;
			App::$today = Elf::getCurrentDate();
		}
	}


	# @param	string	initialize variable
	# @return	string	by reference or redirect

	private function __checkURI($location = null){
		$rw =
		App::$rw = preg_replace('@/{2,}@i', '/', trim(strip_tags(strtolower(rawurldecode($_GET['rw']))), ' /'), -1, $r);

		if (	$rw !== ''
			&&	preg_match('{^[\w/-]+$}i', $rw) === 0
		)												# a-zA-Z0-9, underscore, minus and slash are valid only
			$location = 'location: http://' . $_SERVER['HTTP_HOST'] . App::$absolute;

		else if ($r !== 0)								# found multiple slashes
			$location = 'location: http://' . $_SERVER['HTTP_HOST'] . '/' . $rw . '/';

		else if (Elf::startsWith($rw === '' ? '/' : '/' . $rw . '/', App::$absolute) === false){

			if (Elf::startsWith($rw, 'pot'))			# normally htaccess should handle this
				$rw = strpos($rw, '/') !== false
					? substr($rw, strpos($rw, '/') - 1)
					: App::$absolute;
			else
				$rw = App::$absolute;

			$location = 'location: http://' . $_SERVER['HTTP_HOST'] . $rw;

			if (strrchr($location, '/') !== '/')
				$location.= '/';
		}

		if ($location !== null)
			Elf::redirect($location);

		else {											# make ready for routing
			App::$rw = substr(App::$rw, strlen(App::$absolute) - 1);
			unset($location, $rw);
		}
	}


	# @return	string

	private function __extend(){
		$rw = substr(App::$rw, 5) . '/';				# get rid of “maat/”; make ready for author name check; 
														#    get rid of it, too

		if ((App::$author = $this->__isAuthor(strtolower(Elf::strShiftFirst($rw, '/')))) !== 0)
			App::$rw = $rw === false
				? $rw
				: trim($rw, ' /');						# trim slashes and spaces; make ready for routing

		return App::$author;
	}


	# @param	string
	# @return	string

	private function __isAuthor($name){
		return (empty($name) === true
			or	preg_match('{^[\w-]+$}i', $name) === 0
			or	is_readable(App::$root . '/app/maat/authors/' . $name . '.json') === false)
			? 0											# invalid author name
			: $name;
	}

}
