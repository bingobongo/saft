<?php

namespace Saft;


Class Permalink {

	public function __construct(){
		$this->__permalink();
	}


	protected function __permalink(){		# make sure that utf-8 is set for
		mb_internal_encoding('utf-8');		#     multibyte content string-fu

		$cachename = Pilot::$contentPot . '/' . Elf::cutFileExt(basename(Pilot::$path));
		Pilot::getContentType($contentType, $cachename);

		if (App::CACHE === 1){
			Elf::etagExpired(Pilot::$path);
			Elf::sendStandardHeader($contentType);

			# cache exists, up-to-date, ready for output,
			#    with up-to-date adjacent entries as needed

			if (	Elf::cached(Pilot::$path, $cachename) === 1
				&&	(	App::PREV_NEXT === 0
					or	Elf::cached(App::$cacheRoot . '/' . App::ARR_CACHE_SUFFIX, $cachename) === 1)
			)
				include(App::$cacheRoot . '/' . $cachename);

			else {
				ob_start();					# start output buffering
				$this->__build(filemtime(Pilot::$path));
				Elf::writeToFile(App::$cacheRoot . '/' . $cachename, ob_get_contents(), 'wb');
				ob_end_flush();				# stop output buffering

				# remove sitemap cache file for proper last modified

				if (file_exists(App::$cacheRoot . '/sitemap.xml') === true)
					unlink(App::$cacheRoot . '/sitemap.xml');
			}

		} else {
			Elf::sendStandardHeader($contentType);
			$this->__build(filemtime(Pilot::$path));
		}

		unset($cachename, $contentType);

		if (	App::CHRONO === 1
			&&	Pilot::$protocol !== 'json'
		)
			echo "\n" . Elf::getSqueezedStr();

		exit;
	}


	# @return	array	by reference (title, description, content)

	protected function __prepare(&$entry){

		if (preg_match('{
			^
			.+\.
			(?:
				md|txt|text
			)$
			}ix', basename(Pilot::$path)) === 1
		){
			$entry = file_get_contents(Pilot::$path);
			$entry = trim(mb_convert_encoding($entry, 'utf-8', mb_detect_encoding($entry)));

			$head = explode("\n", ($head = Elf::strShiftFirst($entry, "\n\n")));

			# prevent from throwing PHP Notice in some rare case

			if (sizeof($head) < 2)
				$head = array_pad($head, 2, '');

			if (($title = Elf::getContext($head[0], 'title:')) === ''){
				$title = Elf::entryPathToTitle(Pilot::$path);

				if (($descr = Elf::getContext($head[0], 'description:')) === '')
					$entry = implode("\n", $head) . "\n" . $entry;

			} else
				$descr = Elf::getContext($head[1], 'description:');

			require_once('markdown.php');
			$converter = new Markdown_Parser();
			$entry = $converter->transform($entry);
			unset($converter);

			$this->__addAssets($entry);

			$entry = array (				# title, description, content
				$title,
				$descr,
				$entry
			);

		} else {
			$entry = $this->__buildElement(Pilot::$path);
			$this->__addAssets($entry);

			$entry = array (				# title, description, content
				Elf::getEntryTitle(Pilot::$path),
				'',
				$entry
			);
		}
	}


	# @param	string
	# @return	string	by reference
	#
	#			to scan content pot + its subdirectories for potential assets
	#			and not only single one where entry itself resides in, replace
	#			"(substr(Pilot::$path, 0, strrpos(Pilot::$path, '/')), " with
	#			"(App::$potRoot . '/' . Pilot::$contentPot, "

	protected function __addAssets(&$entry){
		$assets = Pilot::getEntries(substr(Pilot::$path, 0, strrpos(Pilot::$path, '/')), $regexB = '{
			^'
			. str_replace(' ', '\s', preg_quote(Elf::cutFileExt(basename(Pilot::$path))))
			. '
			\s\d+
			\.(?:' . App::FILE_EXT . ')
			$
			}ix'
		);
		$assets = array_reverse($assets);

		if (empty($assets) === false){
			preg_match_all('{
				\[
				@(\d)		# backreference number only
				\]
				}ix', $entry, $numbers
			);

			$numbers = '|' . implode('|', $numbers[1]) . '|';

			# bit higher memory peak than sizeof-while-round-next-key
			#    (grows with array size, negligible here)

			foreach (array_keys($assets) as $path){
				$n = strrpos($path, ' ') + 1;
				$n = substr($path, $n, strrpos($path, '.') - $n);

				if (strpos($numbers, '|' . $n . '|') !== false)
					$entry = preg_replace('{\[@' . $n . '\]}i', $this->__buildElement($path), $entry);

				# replace anchor with asset element or append at the end
				else
					$entry.= "\n" . $this->__buildElement($path);
			}

			unset($numbers, $path);

			$this->__elseAssets($assets);
		}
	}


	# @param	string
	# @param	string
	# @return	string

	protected function __buildElement($path){
		$filename = basename($path);
		$filetype = Elf::getFiletype($filename);

		if ($filetype === 'image')
			return '<img alt=\'' . $filename . '\' src=' . Elf::toEntryAssetURI($path) . '>';

		else if ($filetype === 'text'){
			$str = file_get_contents($path);
			$str = trim(mb_convert_encoding($str, 'utf-8', mb_detect_encoding($str)));
			return "<pre>\n" . $str . "\n</pre>";

		} else if ($filetype === 'video')
			return '<video src=' . Elf::toEntryAssetURI($path) . ' controls>This Browser does not support the video element.</video>';

		return '';
	}


	# @param	array

	protected function __elseAssets(&$assets){
		unset($assets);
	}

}
