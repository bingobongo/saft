Saft
====

**Saft is an unobtrusive tool for rapid publishing; drop a file: Voilà!**  
It transforms image, video and plain text files into Web documents without the use of a database. The static nature of Saft combined with a sly caching makes it super fast and very secure. See the [project page](http://doogvaard.net/speelplaats/2011/07/04/saft/) for more information.

If you prefer to manage your content using a Web browser rather than through the file system, then check out [Maat](https://github.com/bingobongo/maat). It is an extension for Saft and is built for that purpose.


Package Overview
----------------

	/app/
		saft/
			app.php
			archive/
				archive.php
				html.php
				json.php
			elves/
				elf.php
				env.php
				pilot.php
				pot.php
			index/
				atom.php
				html.php
				index.php
				json.php
				sitemap.php
			nav.php
			permalink/
				html.php
				json.php
				markdown.php
				permalink.php
	/asset/
		saft/
			sitemap.xsl
			standard.css
			standard.js
	/pot/
		example-content-pot/
			20110113 example-entry 1.txt
			20110113 example-entry.txt
	/.gitignore
	/LICENSE
	/README.md
	/VERSION
	/apple-touch-icon.png
	/favicon.ico
	/htaccess
	/index.php
	/robots.txt


Installation
------------

Saft requires a web server running a Unix-like operating system, Apache 2 with mod_rewrite enabled and htaccess file support, and PHP 5.3 or higher with JSON, MBSTRING and POSIX extensions installed.

1. Configure the files `/app/saft/app.php` and `/htaccess`. Instructions on configuration are found inside those.
2. Replace “domain.tld” with the corresponding domain name in `/robots.txt`.
3. Move all directories and files to beloved server space. Thereby it does not matter if this is the domain root, a subdomain or a subdirectory.
4. Create at least one content pot, which act as bin where to drop content files. Therefore, navigate to directory `/pot` and create as many subdirectories as liked in there. Only use names without special characters and spaces.
5. Rename `htaccess` to `.htaccess` and make sure that the file permissions of all items are set properly. Once the site is successfully deployed, turn off the debug mode and enable the built-in caching, if not already done so.


Read More
---------

See [doogvaard.net/speelplaats/2011/07/04/saft/](http://doogvaard.net/speelplaats/2011/07/04/saft/) for detailed information about **writing entries, caching, output filtering, customization, templates and other features.**


License
-------

Read the `LICENSE` for license and copyright details.
