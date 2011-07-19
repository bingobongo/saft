<?php

namespace Saft;


Class Fruit extends \Exception {

	public function squeeze(){
		$msg = $this->getMessage();
		$code = $this->getCode();
		switch ($code){
			case 301:
			case 307:
				header('HTTP/1.1 ' . Elf::$status[$code]);
				exit(header('location: ' . $msg));
			case 404:
				if (empty($msg) === true)
					$msg = 'The requested URL <code>' . $_SERVER['REQUEST_URI'] . '</code> was not found on this server. You may go back to <a href=' . App::$absolute . '>main page</a>.';
				break;
			case 500:
				if (empty($msg) === true)
					$msg = 'The server encountered an internal error or misconfiguration and was unable to complete the request.';
				break;
			case 503:
				if (empty($msg) === true)
					$msg = 'The requested URL <code>' . $_SERVER['REQUEST_URI'] . '</code> cannot be accessed temporarily on this server. Please try again later.';
				break;
			default:
				if (empty($msg) === true)
					$msg = 'Don&rsquo;t spiel!';
				break;
		}

		# allow only white-listed IP addresses to view details
		if (	App::DEBUG_MODE === 1
			&&	preg_match('{
			^
			(?:' . str_replace(array('.', ':'), array('\.', '\:'), App::DEBUG_IP) . ')
			$
			}x', $_SERVER['REMOTE_ADDR']) === 1
		)
			$msg.= '
	<p><a class=trace href="javascript:void(toggle(\'trace\'))"><span>▸</span> Stacktrace (<span>click</span>)</a>
	<pre id=trace>' . $this->getTraceAsString() . "\n   thrown in <b>" . $this->getFile() . '</b> on line <b>' . $this->getLine() . "</b>\n   <small>" . substr(Elf::getSqueezedStr(), 5, -4) . '</small></pre>';

		else if ($code === 500){
			$pre = '<br>
		<pre id=info>Get detailed information about what is exactly wrong by <b>adding your
IP address to the list of allowed remote addresses</b> inside <i>app.php</i>.
<small>You may use <a href=http://doogvaard.net/speelplaats/2011/07/19/ip/>IP</a> to cut and paste your current IP address.</small></pre>';

			if (strpos($msg, 'Aut') === 0)
				$msg = 'Automatically shut down due to a security precaution.' . $pre;

			else if (strpos($msg, '<a') === 0)
				$msg = 'Some required PHP extensions could not be loaded on this server, or some application parts have mad permissions or are missing at all.' . $pre;
		}

		Elf::sendExitHeader($code);
		$title = $msg === 'Don&rsquo;t spiel!'
			? $msg
			: Elf::$status[$code];
		echo '<!doctype html>
<html dir=ltr lang=en id=' , Elf::getDomainID() , '>
<head>
	<meta charset=utf-8>
	<title>' , $title , '</title>
	<meta name=robots content=noarchive>
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>
	<style type=text/css>
html			{ background:#f0f2f2; }
body			{ color:#262828; font-family:"Helvetica Neue",Helvetica,"Microsoft Sans Serif",sans-serif; margin:0; }
h1,p			{ text-rendering:optimizeLegibility; }
h1				{ margin:25px 25px -5px; }
body > p		{ background:#e6e8e8; clear:both; font-size:14px; padding:14px 25px 16px; margin:25px 0 0; }
a,
a:link			{ color:#565858; font-weight:700; text-decoration:none; }
#footer a		{ font-weight:400; }
a:visited		{ color:#767878; }
a:focus,
a:hover			{ color:#262828; }
#footer a:hover	{ text-decoration:underline; }
a:active		{ color:#767878; }
i				{ font-style:italic; letter-spacing:1px; }
i:before		{ content:\'\'; margin-left:1px; }
i:after			{ content:\'\'; margin-right:1px; }
pre,
code			{ font-family:Monaco,\'Andale Mono\',\'AndaleMono\',monospace,Verdana,sans-serif; }
pre				{ background:#d6d8d8; border-left:6px solid #d6d8d8; box-sizing:border-box; color:#868888; display:none; margin:0; min-width:100%; padding:20px; width:auto; -moz-box-sizing:border-box; -webkit-box-sizing:border-box; }
#info			{ display:block; }
code			{ color:#868888; }
#footer			{ background:none; color:#767878; font-family:Georgia,sans-serif; font-size:16px; }
	</style>
<body>
	<h1>' , substr($title, $title === "\n\t<p>Don&rsquo;t spiel!" ? 0 : 4) , '</h1>
	<p>' , $msg , '
	<p id=footer>
		<small>© 2010-' , date('Y ') , str_replace('www.', '', $_SERVER['HTTP_HOST']) , '/. Content managed with <a href=http://doogvaard.net/speelplaats/2011/07/04/saft/>Saft</a>.</small>
	<script>
var toggle = function(id){
	var	span = document.getElementsByClassName(id)[0].childNodes[0],
		html = span.innerHTML;

	if (html === \'▾\'){
		span.innerHTML = \'▸\';
		document.getElementById(id).style.display = \'none\';

	} else {
		span.innerHTML = \'▾\';
		document.getElementById(id).style.display = \'block\';
	}
};
	</script>';
		exit;
	}

}


Class Elf {


	# @return	string

	public static function URIpartToFilenamePart(){
		return preg_replace('{
			^				# lazy, simplified pattern
			[\w-]+			# content pot
			/(\d{4})		# slash yyyy
			/(\d{2})		# slash mm
			/(\d{2})		# slash dd
			/([\w-]+)		# slash permalink
			(?:/json)?		# slash json
			$
			}ix', '$1$2$3 $4', App::$rw
		);
	}


	# @param	string
	# @param	integer
	# @return	string

	public static function entryPathToURLi($path, $i=0){
		$name = basename($path);
		$contentPot = substr($path, strlen(App::$potRoot) + 1);
		$contentPot = substr($contentPot, 0, strpos($contentPot, '/')) . preg_replace('{
			^				# lazy, simplified pattern
			(\d{4})			# yyyy
			(\d{2})			# mm
			(\d{2})			# dd
			\s				# space
			([\w-]+)		# permalink
			\.[a-z]+		# .file extension
			$
			}ix', '/$1/$2/$3/$4/', $name);
		return $i === 0
			? App::$baseURL . $contentPot
			: App::$baseURI . $contentPot;
	}


	# @param	string
	# @return	string

	public static function entryPathToTitle($path){
		# preg_replace with simplified pattern is faster than substr
		return ucwords(
			str_replace('-', ' ', preg_replace('{
				^			# lazy, simplified pattern
				\d{8}		# yyyymmdd
				\s			# space
				([\w-]+)	# permalink
				(?:\s\d)?	# space asset number
				\.[a-z]+	# .file extension
				$
				}ix', '$1', strtolower(basename($path))))
		);
	}


	# @param	string
	# @param	string
	# @return	string

	public static function getEntryTitle($path){

		if (preg_match('{
			^
			.+\.
			(?:
				md|txt|text
			)$
			}ix', basename($path)) === 1
		){
			$file = fopen($path, 'r');
			$str = fgets($file);
			$str = trim(mb_convert_encoding($str, 'utf-8', mb_detect_encoding($str)));#'auto'));
			fclose($file);

			if (($str = self::getContext($str, 'title:')) !== '')
				return $str;
		}

		return self::entryPathToTitle($path);
	}


	# @param	string
	# @param	string
	# @return	string	if $str starts with pattern, return $str minus pattern

	public static function getContext($str, $pattern){
		return strpos($str, $pattern) === 0
			? htmlspecialchars(trim(substr($str, strlen($pattern))), ENT_QUOTES, 'utf-8', false)
			: '';
	}


	# @param	string
	# @return	string	if %2F is in src attribute of image/video element,
	#					browser does not respect absoluteness of a URI

	public static function toEntryAssetURI($path){
		return App::$absolute . str_replace('%2F', '/', rawurlencode(substr($path, strlen(App::$potRoot . '/'))));
	}


	# @param	string
	# @param	integer	in octal (null in front), e.g. 0640

	public static function makeDirOnDemand($path, $perm){
		# make pot on demand + deletable via SFTP in non-shared environments
		if (is_dir($path) === false){
			mkdir($path, $perm);
			chmod($path, $perm);
		}
	}


	// @param	string
	// @param	string
	// @param	string
	// @param	integer

	public static function writeToFile($path, $content, $mode = 'a+b', $rewind = 0){
		$path = fopen($path, $mode);

		if ($rewind === 1)
			rewind($path);

		fwrite($path, $content);
		fclose($path);

		unset($content);
	}


	# @return	string

	public static function getDomainID(){
		return str_replace(':', '-', str_replace('.', '-', stripslashes(str_replace('www.', '', $_SERVER['HTTP_HOST']))));
	}


	# @param	string
	# @param	string
	# @return	string

	public static function smartAssetURI($filename, $pathPart = 'saft'){
		return App::$absolute . 'asset.' . filemtime(App::$assetRoot . '/' . $filename) . '/' . $pathPart . '/' . $filename;
	}


	# @param	string
	# @param	string
	# @return	string

	public static function smartAssetURL($filename, $pathPart = 'saft'){
		return App::$baseURL . 'asset.' . filemtime(App::$assetRoot . '/' . $filename) . '/' . $pathPart . '/' . $filename;
	}


	# @param	string
	# @param	string
	# @return	string

	public static function deSpaceClean($str, $de){
		return str_replace(array('  ' . $de, $de . '  ', ' ' . $de, $de . ' ', $de . $de), $de, trim($str, $de . ' '));
	}


	# @param	string
	# @return	string
	#
	# a widow is a single word on a line by itself at the end of a paragraph

	public static function avoidWidow($str){
		$s = strrpos($str, ' ');

		if ($s !== false){
			$widow = substr($str, $s + 1);

			if (	strlen($widow) < 4
				or	substr_count($str, ' ') > 3)
				return substr($str, 0, $s) . '&nbsp;' . $widow;
		}

		return $str;
	}


	# @param	string
	# @return	string

	public static function escapeJSONStr($str){
		return str_replace(	array('\\', '"', "\b", "\f", "\n", "\r", "\t", "\u"),
							array('\\\\', '\"', "\\b", "\\f", "\\n", "\\r", "\\t", "\\u"), $str);
	}


	# @param	string
	# @param	string
	# @return	integer

	public static function endsWith($space, $life){
		return substr($space, - strlen($life)) === $life;
	}


	# @param	string
	# @param	string
	# @return	string	return first part of string based on delimiter,
	#					remove that part from original string by reference

	public static function strShiftFirst(&$str, $de){
		# do not touch original if delimiter does not match
		if (($pos = strpos($str, $de)) === false)
			return '';
		# overwrite string by reference and return cut off part
		$first = substr($str, 0, $pos);
		$str = substr($str, $pos + strlen($de));
		return $first;
	}


	# @param	string
	# @return	string	cut file extension inclusive dot off

	public static function cutFileExt($name){
		return substr($name, 0, strrpos($name, '.'));
	}


	# @param	string
	# @return	string	get file extension or at least the digits behind dot

	public static function getFileExt($name){
		return strtolower(substr($name, strrpos($name, '.') + 1));
	}


	# @param	string
	# @return	string

	public static function getFiletype($name){
		$types = array(
			'text'  => 'md|txt|text',
			'image' => 'gif|jp(e|g|eg)|png|webp',
			'video' => 'ogg|ogv|webm'
		);

		foreach (array_keys($types) as $type){

			if (preg_match('{
				^
				.+\.(?:'
				. $types[$type] . '
				)$
				}ix', $name) === 1
			)
				return $type;
		}

		return '';
	}


	# @return	integer	yyyymmdd

	public static function getCurrentDate(){
		return intval(date('Ymd', time() + (60 * 60)));
	}


	# @return	string

	public static function getSqueezedStr(){
		return '<!-- '
			. round(memory_get_usage()/1024/1024, 4)
			. '(' . round(memory_get_peak_usage()/1024/1024, 4) . ') MiB, '
			. self::stopMicrochronometer()
			. 's(' . self::getCPUconsumptionTimes() . ') -->';
	}


	# @return	string

	public static function stopMicrochronometer(){
		return number_format(microtime(true) - RENDER_START, 4);
	}


	# @return	string

	public static function getCPUconsumptionTimes(){
		$data = getrusage();
		return	number_format($data['ru_utime.tv_sec'] + $data['ru_utime.tv_usec']/1000000, 4) . ')('
			  . number_format($data['ru_stime.tv_sec'] + $data['ru_stime.tv_usec']/1000000, 4);
	}


	# @param	string
	# @param	string
	# @return	integer

	public static function cached($source, $cachename = ''){
		$cache = App::$cacheRoot . '/' . $cachename;
		return ($cachename === ''
			or	is_readable($cache) === false
			or	is_readable($source) === false
			or	filemtime($source) > filemtime($cache))
			? 0
			: 1;
	}


	# @param	string
	# @return	string or integer

	public static function getHash($path){

		if (	App::PREV_NEXT === 1
			&&	class_exists('Permalink') === true
		)
			# permalink with up-to-date adjacent entries as needed
			return file_exists(App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX) === true
				# take cache file of entry list into account
				? hash('ripemd160', $path
					. filemtime($path)
					. filemtime(App::$root . '/.htaccess')
					. filemtime(App::$root . '/app/saft/app.php')
					. filemtime(App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX))
				# or neutralize cache/Etag
				: hash('ripemd160', $path . time());
		else
			return hash('ripemd160', $path
				. filemtime($path)
				. filemtime(App::$root . '/.htaccess')
				. filemtime(App::$root . '/app/saft/app.php'));
	}


	# @param	string

	public static function etagExpired($path){
		$hash = self::getHash($path);
		header('Etag: "' . $hash . '"');

		if (	isset($_SERVER['HTTP_IF_NONE_MATCH']) === true
			&&	stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) === '"' . $hash . '"'
		){
	#		header('Status: ' . self::$status[304]);
			header('HTTP/1.1 ' . self::$status[304]);
			header('Content-Length: 0');
			exit;
		}
	}


	# @param	string
	# @param	integer

	public static function sendStandardHeader($type = 'text/html', $code = 200){
		self::sendHttpHeader($code);
		header('Content-Type: ' . $type . '; charset=utf-8');

		if (App::CACHE === 0){
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
		
		} else	# s-maxage=,	#, proxy-revalidate
			header('Cache-Control: public, max-age=3153600');
	}


	# @param	integer
	# @param	string

	public static function sendExitHeader($code = 200, $type = 'text/html'){
		self::sendHttpHeader($code);
		header('Content-Type: ' . $type . '; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');

		if ($code === 503)
			header('Retry-After: 3600');
	}


	# @param	integer
	#
	# ApacheBench slows down tremendously if HTTP/1.1 headers are sent;
	#    therefore, send in some cases HTTP/1.0 instead; and/or use the
	#    -k option when running ab to enable the HTTP KeepAlive feature!

	public static function sendHttpHeader($code = 200){
	#	header('Status: ' . self::$status[$code]);
		header('HTTP/1.' . (($code > 400 && $code < 405) ? '0 ' : '1 ') . self::$status[$code]);
	}


	public static function getPerms(){
		$processOwner = posix_getpwuid(posix_geteuid());
		$processOwner = $processOwner['name'];

		# Joyent SmartMachine, also applies to many other server environments
		if (	$processOwner === 'www'
			or	strpos(App::$root, '/users/home/') !== 0
		)
			App::$perms = array(
				'app' => 0710,
				'app_parts' => 0640,
				# /pot must be group-writable
				'asset' => 0775,
				'asset_parts' => (App::$maat === 1 ? 0664 : 0644),
				'cache' => 0770
			);

		# Joyent Shared SmartMachine
		else
			App::$perms = array(
				'app' => 0700,
				'app_parts' => 0600,
				'asset' => 0755,
				'asset_parts' => 0644,
				'cache' => 0700
			);
	}


	public static $status = array(
		200 => '200 OK',
		201 => '201 Created',
		301 => '301 Moved Permanently',
		304 => '304 Not Modified',
		307 => '307 Temporary Redirect',
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		500 => '500 Internal Server Error',
		# temporarily, best in combination with retry-after header
		503 => '503 Service Unavailable'
	);

}
