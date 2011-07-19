<?php

namespace Saft;


Class Env {

	public static $arr;


	function __construct(){
		$this->__checkEnvironment();
	}

	protected function __checkEnvironment(){

		# make sure that register_globals is turned off; depreciated >= 5.3
		if (ini_get('register_globals'))
			throw new Fruit("Automatically shut down due to the ability to insert data directly into the global namespace.<br>\n\t\tDisable <code>register_globals</code> to proceed. <strong>This is a serious security precaution.</strong>", 500);

		if (isset($_GET['rw']) !== true)
			throw new Fruit('Proper rewrite could not be performed on this server. Enabled <code>mod_rewrite</code> and htaccess file support is required.', 500);

		$this->__checkExts();
		self::$arr = array(
			'directory' => array(),
			'file' => array(),
			'non' => array()
		);
		$this->__checkPerms();
		$this->__buildPermsMsg();

		# try to prevent from remote file inclusion (allow_url_include
		#    requires allow_url_fopen; fopen check covers both);
		#    info about why this is not 100 % secure:
		#       http://blog.php-security.org/archives/45-PHP-5.2.0-and-allow_url_include.html
		if (ini_get('allow_url_fopen'))
			throw new Fruit("Automatically shut down due to allowed remote file inclusion. If this should be intended, ignore it.<br>\n\t\t<strong>But note that you better make sure you use <a href=http://www.hardened-php.net/suhosin/>Suhosin</a> in this case.</strong><br>\n\t\tThe debug mode may now be turned off.", 500);
	}


	# @param	array	initialize variable

	protected function __checkExts($arr = array()){

		if (extension_loaded('json') === false)
			$arr[] = "JSON Extension\n<small>for application cache and configuration tasks</small>";

		if (extension_loaded('mbstring') === false)
			$arr[] = "MBSTRING Extension\n<small>for multibyte strings (content type conversion)</small>";

		if (extension_loaded('posix') === false)
			$arr[] = "POSIX Extension\n<small>for permissions check</small>";

		if (isset($arr[0]) === true){
			$msg = "<a class=ext href=\"javascript:void(toggle('ext'))\"><span>▸</span> The following required PHP extension(s) could not be loaded on this server (<span>click</span>).</a>\n\t<pre id=ext>";

			foreach ($arr as $str)
				$msg.= "\n" . $str . "\n";

			$msg.= '</pre>';
			throw new Fruit($msg, 500);
		}
	}


	# @return	string

	protected function __checkPerms(){
		$root = App::$root;
		$perms = App::$perms;
		$permsAsset = $perms['asset'];
		$permsAssetParts = $perms['asset_parts'];
		$permsCache = $perms['cache'];

		# root
		if (App::$absolute !== '/')
			$this->__isSecure($root, $permsAsset);

		$this->__isSecure($root . '/index.php');
		$this->__isSecure($root . '/.htaccess', 0640);
		$arr = array(
			'/apple-touch-icon.png',
			'/favicon.ico',
			'/LICENSE',
			'/README.md',
			'/robots.txt',
			'/VERSION'
		);

		foreach ($arr as $part)
			$this->__isSecure($root . $part, $permsAssetParts, 0644);

		# app
		$this->__isSecure($root . '/app');
		$arr = $this->__rglob($root . '/app/saft', '/*', '/{*.json,*.php,*.txt}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path);

		# asset
		$arr = array(
			'/asset',
			'/asset/saft'
		);

		foreach ($arr as $part)
			$this->__isSecure($root . $part, $permsAsset, 0755);

		$arr = $this->__rglob($root . '/asset/saft', '/*', '/{*.css,*.js,*.manifest,*.woff,*.xsl}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path, $permsAsset, 0755);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path, $permsAssetParts, 0644);

		# cache
		$arr = array(
			'/cache',
			'/cache/saft'
		);

		foreach ($arr as $part){
			Elf::makeDirOnDemand($root . $part, $permsCache);
			$this->__isSecure($root . $part, $permsCache);
		}

		$arr = $this->__rglob($root . '/cache/saft', '/*', '/{*.html,*.json,*.xml}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path, $permsCache);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path, $permsAssetParts, 0644);

		# pot
		$this->__isSecure($root . '/pot', $permsAsset);
		$arr = $this->__rglob($root . '/pot', '/*', '/{*.gif,*.jpe,*.jpeg,*.jpg,*.md,*.png,*.txt,*.text,*.webm,*.webp}');
		$arr['files'] = array_filter($arr['files'], function ($path){
			return basename($path) !== '0_REMOVE-TO-FORCE-CACHE-UPDATE.txt';
		});

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path, $permsAsset);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path, $permsAssetParts);
	}


	# @return	string

	protected function __buildPermsMsg(){
		$absolute = rtrim(App::$absolute, '/');
		$arr = self::$arr;
		$msg = '';

		# non-existent directories, files
		if (isset($arr['non'][0]) === true){
			$msg.= '<a class=non href="javascript:void(toggle(\'non\'))"><span>▸</span> The following part';
			$msg.= isset($arr['non'][1]) === true
				? 's of the application are'
				: ' of the application is';
			$msg.= " non-existent on this server (<span>click</span>).</a>\n\t<pre id=non>";

			foreach ($arr['non'] as $str)
				$msg.= "\n" . $absolute . $str . "\n";

			$msg.= '</pre>';
		}

		# directories that have mad permissions
		if (isset($arr['directory'][0]) === true){
			$msg.= $msg === ''
				? ''
				: "\n\t<p>";
			$msg.= '<a class=dir href="javascript:void(toggle(\'dir\'))"><span>▸</span> The following director';
			$msg.= isset($arr['directory'][1]) === true
				? 'ies of the application have'
				: 'y of the application has';
			$msg.= " mad permissions (<span>click</span>).</a>\n\t<pre id=dir>";

			foreach ($arr['directory'] as $array)
				$msg.= "\n" . $absolute . $array[0] . "\n<small>currently " . $array[1] . ' — expected ' . $array[2] . "</small>\n";

			$msg.= '</pre>';
		}

		# files that have mad permissions
		if (isset($arr['file'][0]) === true){
			$msg.= $msg === ''
				? ''
				: "\n\t<p>";
			$msg.= '<a class=fil href="javascript:void(toggle(\'fil\'))"><span>▸</span> The following file';
			$msg.= isset($arr['file'][1]) === true
				? 's of the application have'
				: ' of the application has';
			$msg.= " mad permissions (<span>click</span>).</a>\n\t<pre id=fil>";

			foreach ($arr['file'] as $array)
				$msg.= "\n" . $absolute . $array[0] . "\n<small>currently " . $array[1] . ' — expected ' . $array[2] . "</small>\n";

			$msg.= '</pre>';
		}

		if ($msg !== '')
			throw new Fruit($msg, 500);
	}


	# @param	string
	# @param	string
	# @param	string
	# @param	integer	2 = dirs, 1 = files, 0 = both as arrays in array
	# @return	array

	protected function __rglob($path, $patternD, $patternF, $mode = 0){
		$dirs = glob($path . $patternD, GLOB_ONLYDIR | GLOB_NOSORT);

		if ($mode !== 2)
			$files = glob($path . $patternF, GLOB_BRACE | GLOB_NOSORT);

		foreach ($dirs as $path){
			$dirs = array_merge($dirs, $this->__rglob($path, $patternD, $patternF, 2));

			if ($mode !== 2)
				$files = array_merge($files, $this->__rglob($path, $patternD, $patternF, 1));
		}

		switch ($mode){
			case 1:
				return $files;

			case 2:
				return $dirs;

			default:
				return array(
					'dirs' => $dirs,
					'files' => $files
				);
		}
	}


	# @param	string
	# @param	integer	in octal (null in front), e.g. 0664
	# @param	integer in octal (null in front), e.g. 0644
	# @return	integer

	protected function __isSecure($path, $perm = null, $soft = null){

		if (file_exists($path) === false){
			self::$arr['non'][] = str_replace(App::$root, '', $path);
			return null;
		}

		$isStr = is_dir($path) === true
			? 'directory'
			: 'file';
		$perms = substr(decoct(fileperms($path)), $isStr === 'directory' ? 2 : 3);

		if ($perm === null)
			$perm = $isStr === 'directory'
				? App::$perms['app']
				: App::$perms['app_parts'];

		if ($soft === null){
			$ok = intval($perms) === intval(decoct($perm))
				? 1
				: 0;
			$soft = '';

		} else {
			$ok = (	intval($perms) === intval(decoct($perm))
				or	intval($perms) === intval(decoct($soft)))
				? 1
				: 0;
			$soft = ' or ' . strval(decoct($soft));
		}

		if ($ok === 0)
			self::$arr[$isStr][] = array(
				str_replace(App::$root, '', $path),
				strval($perms),
				strval(decoct($perm)) . $soft
			);
	}

}
