<?php

namespace Saft;


Class Sitemap extends Index {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(&$entries, $lastmod){
		echo '<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="' , Elf::smartAssetURL('sitemap.xsl') , '"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
		xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>' , App::$baseURL , '</loc>
		<lastmod>' , date('c', $lastmod) , '</lastmod>
		<changefreq>daily</changefreq>
		<priority>0.2</priority>
	</url>';

		if (empty($entries) === false){
			$size = sizeof($entries) + 1;				# bit lower memory peak (gains with array size) than
			$r = 0;										#    “foreach (array_keys($entries) as $entryPath){” attempt

			while (--$size){
				++$r;

				if ($r !== 1)
					next($entries);

				$entryPath = key($entries);
				echo '
	<url>
		<loc>' , Elf::entryPathToURLi($entryPath) , '</loc>
		<lastmod>' , date('c', filemtime($entryPath)) , '</lastmod>
		<changefreq>monthly</changefreq>
		<priority>0.1</priority>
	</url>';
			}

			unset($entries, $entryPath);
		}

		echo '
</urlset>';
	}

}
