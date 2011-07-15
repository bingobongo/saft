<?php

namespace Saft;


Class Html extends Permalink {

	public function __construct(){
		parent::__construct();
	}


	protected function __build($lastmod){	# $lastmod superfluous for permalink
		parent::__prepare($entry);

		$title = array_shift($entry);
		$descr = $entry[0] === ''
			? array_shift($entry)
			: "\n\t<meta content='" . array_shift($entry) . '\' name=description>';

		$entry = implode($entry);

		echo '<!doctype html>
<html dir=ltr lang=' , App::LANG , ' id=' , Elf::getDomainID() , '>
<head>
	<meta charset=utf-8>
	<title>' , $title , '</title>' , $descr , '
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>
	<link href=' , Elf::smartAssetURI('standard.css') , ' rel=stylesheet>

<body class=permalink data-mod=' , $lastmod , '>
	<section id=permalink>
		<article>
			' , $entry , '
		</article>
		<hr>
	</section>';

		$nav = new Nav();
		unset($nav);

		echo '
	<footer>
		<p id=footer>
			<small>© 2010-' , date('Y ') , str_replace('www.', '', $_SERVER['HTTP_HOST']) , '/. Content managed with <a href=http://doogvaard.net/speelplaats/2011/07/04/saft/>Saft</a>.</small>
	</footer>
	<!--[if IE]><p id=blues><s>Internet Explorer</s><![endif]-->
	<!--[if !IE]>-->
	<script src=' , Elf::smartAssetURI('standard.js') , '></script>
	<!--<![endif]-->';

		unset($descr, $entry, $title);
	}

}
