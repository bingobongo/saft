<?php

namespace Saft;


Class JSON extends Archive {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(&$entries, $lastmod){
		echo '{
	"title": "' , Elf::escapeJSONStr(htmlspecialchars(App::TITLE, ENT_QUOTES, 'utf-8', false) . ' ' . ucfirst(strtolower(App::ARCHIVE_STR))) , '",
	"url": "' , App::$baseURL , str_replace('json', '', App::$rw) , '",
	"description": "",
	"lastmod": "' , date('c', $lastmod) , '",
	"rights": "(C) 2010-' , date('Y ') , str_replace('www.', '', $_SERVER['HTTP_HOST']) , '/",
	"archive": [';

		if ($entries !== 0){
			$seasonStr = array(
				array('01-03/', 'Spring'),
				array('04-06/', 'Summer'),
				array('07-09/', 'Autumn'),
				array('10-12/', 'Winter')
			);

			$size = sizeof($entries) + 1;
			$r = 0;
			# uber, content pots
			while (--$size){
				++$r;

				if ($r !== 1){
					$arr = next($entries);
					$name = key($entries);
					$baseURL = App::$baseURL . $name . '/';

				} else {
					$arr = current($entries);
					$baseURL = App::$baseURL;
					$name = '↩';
				}
				# abandone
				if ($arr === 0)
					continue;
				# season alone (without year),  remove from array
				$a = array_shift($arr);
				$q = 0;
				$sizeArr = sizeof($arr);
				echo '
		{
			"name": "' , ucfirst($name) , '",
			"url": "' , $baseURL , '",
			"year": [
				{
					"name": "",
					"url": "",
					"season": [';
				# season alone (without year)
				foreach ($a as $seasonArr){
					echo '
						{
							"name": "' , $seasonStr[$q][1] , '",
							"url": "' , $seasonArr !== 0 ? $baseURL . $seasonStr[$q][0] : '' , '"
						}' , $q < 3 ? ',' : '';

					++$q;
				}

				echo '
					]
				}' , $sizeArr > 0 ? ',' : '';
				# year
				foreach (array_keys($arr) as $year){
					$a = $arr[$year];
					$q = 0;
					$year = trim($year);
					--$sizeArr;
					echo '
				{
					"name": "' , $year , '",
					"url": "' , $baseURL , $year , '/",
					"season": [';
					# season
					foreach ($a as $seasonArr){
						echo '
						{
							"name": "' , $seasonStr[$q][1] , '",
							"url": "' , $seasonArr !== 0 ? $baseURL . $year . '/' . $seasonStr[$q][0] : '' , '"
						}' , $q < 3 ? ',' : '';

						++$q;
					}

					echo '
					]
				}' , $sizeArr > 0 ? ',' : '';
				}

				echo '
			]
		}' , $size > 1 ? ',' : '';
			}

		} else
			echo '
		"☺"';

		echo "\n\t]\n}";
		unset($entries, $lastmod, $seasonStr, $seasonArr, $arr, $a, $baseURI, $name, $year);
	}

}
