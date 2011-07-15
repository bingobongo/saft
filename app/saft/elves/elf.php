<?php

namespace Saft;


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
											# preg_replace with simplified
		return ucwords(						#    pattern is faster than substr
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
	# @return	string	if %2F is in src attribute of image/video element,
	#					browser does not respect absoluteness of a URI

	public static function toEntryAssetURI($path){
		return App::$absolute . str_replace('%2F', '/', rawurlencode(substr($path, strlen(App::$potRoot . '/'))));
	}


	# @param	string
	# @param	integer	in octal (null in front), e.g. 0640
	#
	#			make pot on demand + deletable via SFTP in non-shared envir.

	public static function makeDirOnDemand($path, $perm){

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
		)									# permalink with up-to-date adjacent
											#    entries as needed
			return file_exists(App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX) === true
				? hash('ripemd160', $path	# take cache file of entry list
					. filemtime($path)		#    into account
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
			header('HTTP/1.1 ' . self::$status[304]);
			header('Status: ' . self::$status[304]);
			header('Content-Length: 0');
			exit;

		}
	}


	# @param	string
	# @param	string
	# @return	string	if $str starts with pattern, return $str minus pattern

	public static function getContext($str, $pattern){
		return strpos($str, $pattern) === 0
			? htmlspecialchars(trim(substr($str, strlen($pattern))), ENT_QUOTES, 'utf-8', false)
			: '';
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

	public static function avoidWidow($str){# = a single word on a line by
		$s = strrpos($str, ' ');			#   itself at the end of a paragraph

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

		if (($pos = strpos($str, $de)) === false)
			return '';	# do not touch original if delimiter does not match

		$first = substr($str, 0, $pos);
		$str = substr($str, $pos + strlen($de));
		return $first;	# overwrite string by reference and return cut off part
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
	# @param	integer

	public static function redirect($location, $code = 301){
		self::sendHttpHeader($code);
		exit(header($location));
	}


	# @param	string
	# @param	integer

	public static function sendStandardHeader($type = 'text/html', $code = 200){
		self::sendHttpHeader($code);
		header('Content-Type: ' . $type . '; charset=utf-8');

		if (App::CACHE === 0){
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');

		} else
			header('Cache-Control: public, max-age=3153600');
	}										# s-maxage=,	#, proxy-revalidate


	# @param	integer

	public static function sendHttpHeader($code = 200){

		# slows down ApacheBench test tremendously,
		#    except in case of 400, 500, 503 ..!?

		if ($code === 400 or $code === 500 or $code === 503)
			header('HTTP/1.1 ' . self::$status[$code]);

		header('Status: ' . self::$status[$code]);
	}


	# @param	integer
	# @param	string

	public static function sendExitHeader($code = 200, $type = 'text/html'){
		self::sendHttpHeader($code);

		if ($code === 503)
			header('Retry-After: 3600');

		header('Content-Type: ' . $type . '; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
	}


	# @param	integer
	# @param	string

	public static function sendExit($code = 200, $msg = ''){

		if ($msg !== '')
			$msg = "\n\t<p>" . $msg;

		$title =	$code === 200
				&&	$msg === ''
			? 'Don&rsquo;t spiel!'
			: self::$status[$code];

		self::sendExitHeader($code);

		exit(
		'<!doctype html>
<html dir=ltr lang=en id=' . self::getDomainID() . '>
<head>
	<meta charset=utf-8>
	<title>' . $title . '</title>
<body>
	<h1>' . substr($title, $title === 'Don&rsquo;t spiel!' ? 0 : 4) . '</h1>' . $msg
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
		503 => '503 Service Unavailable'	# temporarily, best in combination
	);										#    with retry-after header

}
