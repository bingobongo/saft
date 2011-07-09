<?php

namespace Saft;


Class JSON extends Permalink {

	public function __construct(){
		parent::__construct();
	}


	protected function __build($lastmod){
		parent::__prepare($entry);

		$title = Elf::escapeJSONStr(array_shift($entry));
		$descr = $entry[0] === ''
			? array_shift($entry)
			: Elf::escapeJSONStr(array_shift($entry));

		$entry = implode($entry);

		echo '{
	"title": "' , $title , '",
	"url": "' , App::$baseURL , str_replace('json', '', App::$rw) , '",
	"description": "' , $descr , '",
	"lastmod": "' , date('c', $lastmod) , '",
	"rights": "(C) 2010-' , date('Y ') , str_replace('www.', '', $_SERVER['HTTP_HOST']) , '/",
	"content": "' , Elf::escapeJSONStr($entry) , '"
}';

		unset($descr, $entry, $title);
	}

}
