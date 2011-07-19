<?php

namespace Saft;


Class JSON extends Index {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(&$entries, $lastmod){

		if (Pilot::$contentPot !== 0){
			$title = ucfirst(Pilot::$contentPot);
			$descr = '';

		} else {
			$title = Elf::escapeJSONStr(htmlspecialchars(App::TITLE, ENT_QUOTES, 'utf-8', false));
			$descr = Elf::escapeJSONStr(htmlspecialchars(App::DESCR, ENT_QUOTES, 'utf-8', false));
		}

		echo '{
	"title": "' , $title , '",
	"url": "' , App::$baseURL , str_replace('json', '', App::$rw) , '",
	"description": "' , $descr , '",
	"lastmod": "' , date('c', $lastmod) , '",
	"rights": "(C) 2010-' , date('Y ') , str_replace('www.', '', $_SERVER['HTTP_HOST']) , '/",
	"entries": [';

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
		{
			"title": "' , Elf::escapeJSONStr(Elf::avoidWidow(Elf::getEntryTitle($entryPath))) , '",
			"url": "' , $entryURL , '",
			"lastmod": "' , date('c', filemtime($entryPath)) , '"
		}' , $size > 1 ? ',' : '';
			}

			unset($entryPath, $entryURI);

		} else
			echo '
		"☺"';

		echo "\n\t]\n}";
		unset($entries, $lastmod);
	}

}
