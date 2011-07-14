<?php

namespace Saft;


Class Html extends Index {

	public function __construct(){
		parent::__construct();
	}

											# $lastmod superfluous for indexes
	protected function __build(&$entries, $lastmod){
		$alternate = '';

		if (Pilot::$contentPot !== 0){
			$title = ucfirst(Pilot::$contentPot);
			$descr = '';

		} else {
			$title = htmlspecialchars(App::TITLE, ENT_QUOTES, 'utf-8', false);
			$descr = "\n\t<meta content='" . htmlspecialchars(App::DESCR, ENT_QUOTES, 'utf-8', false) . '\' name=description>';
		}

		if (	Pilot::$page === 1
			&&	Pilot::$month === 0
			&&	Pilot::$year === 0
		){
			$alternate.= App::$baseURL;

			if (Pilot::$contentPot !== 0)
				$alternate.= Pilot::$contentPot . '/';

			$alternate = "\n\t" . '<link href=' . $alternate . 'atom/ rel=alternate title=\'' . $title . ' Feed\' type=application/atom+xml>';
		}

		echo '<!doctype html>
<html dir=ltr lang=' , App::LANG , ' id=' , Elf::getDomainID() , '>
<head>
	<meta charset=utf-8>
	<title>' , $title , '</title>' , $descr , '
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>' , $alternate , '
	<link href=' , Elf::smartAssetURI('standard.css') , ' rel=stylesheet>

<body class=index data-mod=' , $lastmod , '>
	<section id=index' , Pilot::$page === 1 ? ' class=first' : '' , '>';

		# bit lower memory peak than "foreach (array_keys() as $path){"
		#    (gains with array size)

		if (empty($entries) === false){
			$size = sizeof($entries);
			$r = 0;

			if ($size > 0)
				++$size;

			while (--$size){
				++$r;

				if ($r !== 1)
					next($entries);

				$entryPath = key($entries);
				$entryURI = '/' . Elf::entryPathToURLi($entryPath, true);
				echo '
		<a href=' , $entryURI , '>' , Elf::avoidWidow(Elf::getEntryTitle($entryPath)) , '</a> ';
			}

			unset($entryPath, $entryURI);

		} else
			echo '
		<a href>☺</a>';

		echo '
		<hr>
	</section>';

		unset($alternate, $descr, $entries, $title);

		$nav = new Nav();
		unset($nav);

		echo '
	<!--[if IE]><p id=blues><s>Internet Explorer</s><![endif]-->
	<!--[if !IE]>-->
	<script src=' , Elf::smartAssetURI('standard.js') , '></script>
	<!--<![endif]-->';
	}

}
