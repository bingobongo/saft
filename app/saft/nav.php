<?php

namespace Saft;


Class Nav {

	const ADJACENT = 10;					# adjacent LR to current page

	public static
		$archiveStr,
		$indexStr,
		$prevStr,
		$nextStr,
		$paginatePath;


	# @param	string
	# @param	string
	# @param	string
	# @param	string

	public function __construct(
		$indexStr = 'index&nbsp;(<span>i</span>)',
		$prevStr = '«(<span>h</span>)&nbsp;prev',
		$nextStr = 'next&nbsp;(<span>l</span>)»',
		$archiveStr = 'archive&nbsp;(<span>a</span>)'
	){
		if (Pilot::$protocol !== 'html')
			return null;

		self::$archiveStr = $archiveStr;
		self::$indexStr = $indexStr;
		self::$prevStr = $prevStr;
		self::$nextStr = $nextStr;
		$this->__buildNav();
	}


	protected function __buildNav(){
		$archive = is_file(App::$propelRoot . '/archive/archive.php') === true ? 1 : 0;
		$class = Pilot::$pageType;
		$this->__getPrevNextURI($prev, $next);
		$str = '';

		# without </p> Opera 11 would mess up
		if ($class === 'index')
			echo '
	<p id=okj>
		<a tabIndex=-1 href=javascript:void(redirect(1))>open (<span>o</span>)</a><span> </span><a tabIndex=-1 href=javascript:void(shiftFocus(-1,1))>prev (<span>k</span>)</a><span> </span><a tabIndex=-1 href=javascript:void(shiftFocus(1,1))>next (<span>j</span>)</a>
	</p>';

		if (	App::PREV_NEXT === 1
			&&	$prev !== 0
		)
			$str.= '<a tabIndex=-1 id=prev href=' . $prev . ' rel=prev>' . self::$prevStr . '</a><span> </span>';

		if (	$class !== 'index'
			or	(	$class === 'index'
				&&	(	Pilot::$contentPot !== 0
					or	Pilot::$page > 1
					or	Pilot::$month !== 0
					or	Pilot::$year !== 0
					)
				)
		)
			$str.= '<a tabIndex=-1 id=home' . ' href=' . App::$absolute . ' rel=index>' . self::$indexStr . '</a><span> </span>';

		# App::ARCHIVE_STR must not comply with page-type name
		if (	$class !== 'archive'
			&&	Pilot::$archive === 1
		)
			$str.= '<a tabIndex=-1 id=pots' . ' href=' . App::$absolute . rawurlencode(App::ARCHIVE_STR) . '/ rel=archives>' . self::$archiveStr . '</a><span> </span>';

		if (	App::PREV_NEXT === 1
			&&	$next !== 0
		)
			$str.= '<a tabIndex=-1 id=next href=' . $next . ' rel=next>' . self::$nextStr . '</a><span> </span>';

		# cut off last "<span> </span>"
		if ($str !== '')
			echo '
	<nav>
		' , substr($str, 0, -14) , '
	</nav>';

		if (	App::PAGINATE === 1
			&&	$class === 'index'
		)
			$this->__paginate();
	}

	# @return	string	by reference
	# @return	string	by reference

	protected function __getPrevNextURI(&$prev, &$next){

		if (App::PREV_NEXT === 0)
			return $prev = $next = 0;

		if (Pilot::$pageType === 'permalink'){
			# must come before Pot::scan() that changes Pilot::$path again
			$key = Pilot::$path;
			# change again to comply with the assumption of the base of
			#    cache() function and the URI of content pot in class Pot
			Pilot::$path = App::$potRoot;
			# make prev/next entry navigate to ones of same content pot only
		#	Pilot::$path = App::POT_FILTER === 1
		#		? App::$potRoot . '/' . Pilot::$contentPot
		#		: App::$potRoot;
			$entries = Pot::scan();

			while (key($entries) !== $key)
				next($entries);

			if ($prev = prev($entries)){
				$prev = key($entries);
				next($entries);

			} else 
				reset($entries);

			if ($next = next($entries))
				$next = key($entries);

			# previous entry
			$prev = $prev !== false
				? '/' . Elf::entryPathToURLi($prev, true)
				: 0;
			# next entry
			$next = $next !== false
				? '/' . Elf::entryPathToURLi($next, true)
				: 0;

			unset($entries, $key);

		} else if (Pilot::$pageType === 'index'){
			$size = Pilot::$size;
			$perPage = App::PER_PAGE;

			if ($size <= $perPage)
				return $prev = $next = 0;

			$page = Pilot::$page;
			$this->__getPaginatePath($path);
			# previous entry
			$prev = $page > 1
				? $path . ($page - 1) . '/'
				: 0;
			# next entry
			$next = $size > $perPage * $page
				? $path . ($page + 1) . '/'
				: 0;

		} else
			$prev = $next = 0;
	}


	protected function __paginate(){
		$p = 1;
		$size = Pilot::$size;
		$perPage = App::PER_PAGE;

		if ($size <= $perPage)
			return null;

		$pages = intval(ceil($size / $perPage));
		$page = Pilot::$page;
		$this->__getPaginatePath($path);
		echo '
	<p id=paginate>
		';
		# few only
		if ($pages < self::ADJACENT * 2 + 2){

			for ($i = $pages + 1; --$i;){

				if ($p === $page)
					echo '<span>' , $p , '</span> ';
				else
					echo '<a href=' , $path , $p , '/>' , $p , '</a> ';
				++$p;
			}
		# hide part
		} else {
			# close to start
			if ($page < self::ADJACENT + 2){
				$i = self::ADJACENT * 2 + 2;

				while (--$i){

					if ($p === $page)
						echo '<span>' , $p , '</span> ';
					else
						echo '<a href=' , $path , $p , '/>' , $p , '</a> ';
					++$p;
				}

				echo '<a href="javascript:void(flimflam(' , $page , ', ' , $pages , ', \'' , $path , '\'));">&hellip;</a>';
			# in the middle of
			} else if (
					$page < $pages - self::ADJACENT
				&&	$page > self::ADJACENT + 1
			){
				$i = self::ADJACENT * 2 + 2;
				$p = $page - self::ADJACENT;
				echo '<a href="javascript:void(flimflam(' , $page , ', ' , $pages , ', \'' , $path , '\'));">&hellip;</a> ';

				while (--$i){

					if ($p === $page)
						echo '<span>' , $p , '</span> ';
					else
						echo '<a href=' , $path , $p , '/>' , $p , '</a> ';
					++$p;
				}

				echo '<a href="javascript:void(flimflam(' , $page , ', ' , $pages , ', \'' , $path , '\'));">&hellip;</a>';
			# close to end
			} else {
				$p = $pages - self::ADJACENT * 2;
				$i = $pages - $p + 2;
				echo '<a href="javascript:void(flimflam(' , $page , ', ' , $pages , ', \'' , $path , '\'));">&hellip;</a> ';

				while (--$i){

					if ($p === $page)
						echo '<span>' , $p , '</span> ';
					else
						echo '<a href=' , $path , $p , '/>' , $p , '</a> ';
					++$p;
				}
			}
		}
	}


	# @param	string
	# @return	string	by reference

	private function __getPaginatePath(&$path){

		if (empty(self::$paginatePath) === false)
			return $path = self::$paginatePath;

		$path = App::$author === 0
			? App::$absolute
			: App::$absolute . 'maat/' . App::$author . '/';

		if (Pilot::$contentPot !== 0)
			$path.= Pilot::$contentPot . '/';

		if (Pilot::$year !== 0)
			$path.= Pilot::$year . '/';

		if (Pilot::$month !== 0)
			$path.= is_array(Pilot::$month) === true
				? implode('-', Pilot::$month) . '/'
				: Pilot::$month . '/';

		self::$paginatePath = $path.= App::PAGE_STR . '/';
	}

}
