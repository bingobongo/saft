<?php

namespace Saft;


Class Html extends Archive {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(&$entries, $lastmod){	# $lastmod is superfluous for archive
		echo '<!doctype html>
<html dir=ltr lang=' , App::LANG , ' id=' , Elf::getDomainID() , '>
<head>
	<meta charset=utf-8>
	<title>' , htmlspecialchars(App::TITLE, ENT_QUOTES, 'utf-8', false) . ' ' . ucfirst(strtolower(App::ARCHIVE_STR)) , '</title>
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>
	<link href=' , Elf::smartAssetURI('standard.css') , ' rel=stylesheet>

<body class=archive data-mod=' , $lastmod , '>
	<section id=archive>';

		if ($entries !== 0){
			$seasonStr = array(
				array('01-03/', 'Spring'),
				array('04-06/', 'Summer'),
				array('07-09/', 'Autumn'),
				array('10-12/', 'Winter')
			);

			echo '
		<table>
			<thead>
				<tr>
					<th>
						Author
					<th>
						Year
					<th colspan=4>
						Season
			</thead>
			<tbody>';

			$size = sizeof($entries) + 1;
			$r = 0;

			while (--$size){							# uber, content pots
				++$r;

				if ($r !== 1){
					$arr = next($entries);
					$name = key($entries);
					$baseURI = '/' . App::$baseURI . $name . '/';
					$name = '<a href=' . $baseURI . '>' . ucfirst($name) . '</a>';

				} else {
					$arr = current($entries);
					$baseURI = '/' . App::$baseURI;
					$name = '<a href=' . $baseURI . '>↩</a>';
				}

				$a = array_shift($arr);					# season alone (without year), remove from array
				$q = 0;
				echo '
				<tr>
					<td>
						' , ucfirst($name) , '
					<td>';

				foreach ($a as $seasonArr){				# season alone (without year)
					echo '
					<td>';

					if ($seasonArr !== 0)
						echo '
						<a href=' , $baseURI , $seasonStr[$q][0] , '>' , $seasonStr[$q][1] , '</a>';

					else if ($r === 1)					# first of all (uber-season)
						echo '
						' , $seasonStr[$q][1];

					++$q;
				}

				foreach (array_keys($arr) as $year){	# year
					$a = $arr[$year];
					$q = 0;
					$year = trim($year);

					echo '
				<tr>
					<td>
					<td>
						<a href=' , $baseURI , $year , '/>' , $year , '</a>';

					foreach ($a as $seasonArr){			# season
						echo '
					<td>';

						if ($seasonArr !== 0)
							echo '
						<a href=' , $baseURI , $year , '/' , $seasonStr[$q][0] , '>' , $seasonStr[$q][1] , '</a>';

						++$q;
					}
				}
			}

			echo'
			</tbody>
		</table>';

		} else
			echo '
		<p>
			☺';

		unset($entries, $lastmod, $seasonStr, $seasonArr, $arr, $a, $baseURI, $name, $year);

		echo '
		<hr>
	</section>';

		$nav = new Nav();
		unset($nav);

		echo '
	<!--[if IE]><p id=blues><s>Internet Explorer</s><![endif]-->
	<!--[if !IE]>-->
	<script src=' , Elf::smartAssetURI('standard.js') , '></script>
	<!--<![endif]-->';
	}

}
