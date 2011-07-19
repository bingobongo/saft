<?php

namespace Saft;


Class Atom extends Index {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(&$entries, $lastmod){
		$selfURL = App::$baseURL;

		if (Pilot::$contentPot !== 0){
			$title = ucfirst(Pilot::$contentPot);
			$descr = '';
			$selfURL.= Pilot::$contentPot . '/';

		} else {
			$title = htmlspecialchars(App::TITLE, ENT_QUOTES, 'utf-8', false);
			$descr = "\n\t<subtitle>" . htmlspecialchars(App::DESCR, ENT_QUOTES, 'utf-8', false) . '</subtitle>';
		}

		echo '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="' , App::LANG , '">
	<title>' , $title , '</title>' , $descr , '
	<link href="' , $selfURL , 'atom/" rel="self" type="application/atom+xml" />
	<link href="' , $selfURL , '" rel="alternate" type="text/html" />
	<rights>(C) 2010-' , date('Y ') , str_replace('www.', '', $_SERVER['HTTP_HOST']) , '/</rights>
	<author>
		<name>' , $title , '</name>
		<uri>' , $selfURL , '</uri>
	</author>
	<id>' , $selfURL , 'atom/</id>
	<updated>' , date('c', $lastmod) , '</updated>';

		# bit lower memory peak than "foreach (array_keys() as $path){"
		#    (gains with array size)
		if (empty($entries) === false){
			$size = sizeof($entries) + 1;
			$r = 0;

			while (--$size){
				++$r;

				if ($r !== 1)
					next($entries);

				$entryPath = key($entries);
				$entryURL = Elf::entryPathToURLi($entryPath);
				echo '
	<entry>
		<title>' , Elf::getEntryTitle($entryPath) , '</title>
		<link href="' , $entryURL , '" rel="alternate" type="text/html" />
		<id>' , $entryURL , '</id>
		<updated>' , date('c', filemtime($entryPath)) , '</updated>
	</entry>';
			}

			unset($entryPath, $entryURL);
		}

		echo '
</feed>';
		unset($descr, $entries, $selfURL, $title);
	}

}
