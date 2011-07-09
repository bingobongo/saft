<?php

namespace Saft;


Class Machine {

	public static $parts;


	function __construct(){
		$this->__checkEnvironment();
	}
														# try to prevent remote file inclusion (“allow_url_include”
	private function __checkEnvironment(){				#    requires “allow_url_fopen” to be on, fopen check covers both)
														# make sure that “register_globals” is turned off in “php.ini”
		if (	ini_get('allow_url_fopen')				#    file (“register_globals = Off”)
			or	ini_get('register_globals')				# info about why this is not 100 % secure:
		)	#    http://blog.php-security.org/archives/45-PHP-5.2.0-and-allow_url_include.html
			Elf::sendExit(500, 'Automatically shut down due to a security precaution.');

		else if (isset($_GET['rw']) !== true)			# check for enabled mod_rewrite
			Elf::sendExit(500, 'Proper rewrite could not be performed on this server.');

		else if (extension_loaded('json') === false)	# for application cache, Maat’s configuration
			Elf::sendExit(500, 'JSON extension for application cache and configuration tasks could not be loaded on this server.');

		else if (extension_loaded('mbstring') === false)# for content type conversion
			Elf::sendExit(500, 'MBSTRING extension for multibyte strings could not be loaded on this server.');

		else if (extension_loaded('posix') === false)	# for permissions check
			Elf::sendExit(500, 'POSIX extension for permissions check could not be loaded on this server.');

		else if ($this->__checkPerms() === 0)
			Elf::sendExit(500, 'Part(s) of the application have mad permissions.');
	}


	# @return	integer

	private function __checkPerms(){
		$root = App::$root;
		$propelRoot = $root . '/app';
		$secure = 1;

		Elf::makeDirOnDemand(App::$cacheRoot, App::$perms['cache']);
		Elf::makeDirOnDemand(App::$cacheRoot . '/saft', App::$perms['cache']);

		if (App::$maat === 1){
			$propelMaat = $propelRoot . '/maat';

			Elf::makeDirOnDemand(App::$cacheRoot . '/maat', App::$perms['cache']);
			Elf::makeDirOnDemand($root . '/log', App::$perms['cache']);
			Elf::makeDirOnDemand($root . '/log/maat', App::$perms['cache']);
			Elf::makeDirOnDemand($root . '/log/maat/history', App::$perms['cache']);
		}

		if (	$this->__isSecure($root . '/.htaccess', 0640) === 1
			&&	$this->__isSecure($root . '/apple-touch-icon.png', App::$perms['asset_parts']) === 1
			&&	$this->__isSecure($root . '/favicon.ico', App::$perms['asset_parts']) === 1
			&&	$this->__isSecure($root . '/LICENSE', App::$perms['asset_parts']) === 1
			&&	$this->__isSecure($root . '/README.md', App::$perms['asset_parts']) === 1
			&&	$this->__isSecure($root . '/robots.txt', App::$perms['asset_parts']) === 1
			&&	$this->__isSecure(App::$assetRoot, App::$perms['asset']) === 1
			&&	$this->__isSecure(App::$cacheRoot, App::$perms['cache']) === 1
			&&	$this->__isSecure(App::$assetRoot . '/saft', App::$perms['asset']) === 1
			&&	$this->__isSecure(App::$cacheRoot . '/saft', App::$perms['cache']) === 1
			&&	(	App::$maat === 0
				or	(	$this->__isSecure(App::$cacheRoot . '/maat', App::$perms['cache']) === 1
					&&	$this->__isSecure(App::$assetRoot . '/maat', App::$perms['asset']) === 1
					&&	$this->__isSecure($root . '/log', App::$perms['cache']) === 1
					&&	$this->__isSecure($root . '/log/maat', App::$perms['cache']) === 1
					&&	$this->__isSecure($root . '/log/maat/history', App::$perms['cache']) === 1
					)
				)
			&&	$this->__isSecure(App::$potRoot, App::$perms['asset']) === 1
			&&	$this->__isSecure($propelRoot) === 1
		){												# a) check root PHP parts,
														# b) check propel parts (3 levels down),
			$path = preg_quote($root);					# c) content pots and d) asset parts
			self::$parts = glob($path . '/*.php', GLOB_NOSORT);
			$path = preg_quote($propelRoot);
			self::$parts = array_merge(self::$parts, glob($path . '/*.php', GLOB_NOSORT));
			$potParts = glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
			$potParts2 = array();

			foreach ($potParts as $path)				# 2nd level
				$potParts2 = array_merge($potParts2, glob(preg_quote($path) . '/*', GLOB_ONLYDIR | GLOB_NOSORT));

			foreach ($potParts2 as $path)				# 3rd level
				$potParts = array_merge($potParts, glob(preg_quote($path) . '/*', GLOB_ONLYDIR | GLOB_NOSORT));

			$potParts = array_merge($potParts, $potParts2);

			foreach ($potParts as $path)
				self::$parts = array_merge(self::$parts, glob(preg_quote($path) . '/{*.json,*.php,*.txt}', GLOB_BRACE | GLOB_NOSORT));

			self::$parts = array_merge(self::$parts, $potParts);

			if (($secure = $this->__checkPartsPerms()) === 1){
				self::$parts = glob(preg_quote(App::$potRoot) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
				self::$parts = array_filter(self::$parts, function ($path){
					return preg_match('{[^\w-]+}i', basename($path)) !== 1;
				});

				if (($secure = $this->__checkPartsPerms(App::$perms['asset'])) === 1){
					self::$parts = glob(preg_quote(App::$assetRoot . '/saft') . '/{*.css,*.js,*.manifest,*.woff,*.xsl}', GLOB_BRACE | GLOB_NOSORT);
					$secure = $this->__checkPartsPerms(App::$perms['asset_parts']);
				}

				if (	App::$maat === 1
					&&	$secure === 1
				){
					self::$parts = glob(preg_quote(App::$assetRoot . '/maat') . '/{*.css,*.js,*.manifest,*.woff,*.xsl}', GLOB_BRACE | GLOB_NOSORT);
					$secure = $this->__checkPartsPerms(App::$perms['asset_parts']);
				}
			}

			unset($path, $potParts, $potParts2, $propelRoot, $root);

		} else
			$secure = 0;

		self::$parts = null;							# unset static property
		return $secure;
	}


	# @param	integer	“$perm” must be passed in octal (null in front), e.g. 0640
	# @return	integer

	private function __checkPartsPerms($perm = null){

		foreach (self::$parts as $path){

			if ($this->__isSecure($path, $perm) === 0)
				return 0;
		}

		return 1;
	}


	# @param	string
	# @param	integer	“$perm” must be passed in octal (null in front), e.g. 0640
	# @return	integer

	private function __isSecure($path, $perm = null){
		if (file_exists($path) === false)
			Elf::sendExit(500, 'Part(s) of the application are non-existent on this server.');

		$isDir = is_dir($path);
		$perms = fileperms($path);

		if ($perm === null)
			$perm = $isDir === true
				? App::$perms['app']
				: App::$perms['app_parts'];

		return intval(substr(decoct($perms), $isDir === true ? 2 : 3)) <= decoct($perm)
			? 1
			: 0;
	}

}
