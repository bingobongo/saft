<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0"
	xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output doctype-system="about:legacy-compat"
	encoding="utf-8"
	indent="yes"
	method="xml"
	omit-xml-declaration="yes"/>

<xsl:variable name="domainID">
	<xsl:choose>
		<xsl:when test="starts-with(substring-before(substring-after(sitemap:urlset/sitemap:url/sitemap:loc, '://'), '/'), 'www.')">
			<xsl:value-of select="translate(substring(substring-before(substring-after(sitemap:urlset/sitemap:url/sitemap:loc, '://'), '/'), 5), '.:', '--')"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="translate(substring-before(substring-after(sitemap:urlset/sitemap:url/sitemap:loc, '://'), '/'), '.:', '--')"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:variable>

<xsl:variable name="baseURL" select="sitemap:urlset/sitemap:url/sitemap:loc"/>
<xsl:variable name="itemsum" select="count(sitemap:urlset/sitemap:url)"/>

<xsl:variable name="type">
	<xsl:choose>
		<xsl:when test="$itemsum &gt; 1">
			<xsl:text> entries</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text> entry</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:variable>

<xsl:template match="/">

<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" id="{$domainID}">
<head>
	<meta charset="utf-8"/>
	<title>XML Sitemap</title>
	<link rel="shortcut icon" href="{$baseURL}favicon.ico"/>
	<link rel="apple-touch-icon" href="{$baseURL}apple-touch-icon.png"/>
	<style type="text/css">
html			{ background:#f0f2f2; }
body			{ color:#262828; font-family:"Helvetica Neue",Helvetica,"Microsoft Sans Serif",sans-serif; margin:0; }
h1,p,td,th		{ text-rendering:optimizeLegibility; }
h1				{ margin:25px 25px 20px; }
i				{ color:#b6b8b8; font-family:Georgia,serif; font-size:24px; font-weight:400; font-style:normal; }
header							{ background:#e6e8e8; clear:both; padding:14px 0 3px; margin-bottom:25px; }
header p		{ font-size:14px; margin:0px 25px 13px; }
a,
a:link			{ color:#565858; font-weight:700; text-decoration:none; }
a:visited		{ color:#767878; }
a:focus,
a:hover			{ color:#262828; }
footer a:hover	{ text-decoration:underline; }
a:active		{ color:#767878; }
table			{ border-collapse:collapse; margin-bottom:25px; width:100%; }
tbody tr		{ border-top:1px solid #d6d8d8; }
tbody tr:hover	{ background-color:#e6e8e8; }
td,
th				{ line-height:1.5; padding:5px 5px; }
td				{ font-size:16px; }
th				{ font-size:14px; text-align:left; }
.first			{ padding-left:25px; }
.last			{ padding-right:25px; }
footer			{ color:#767878; font-family:Georgia,sans-serif; margin:16px 0 16px 25px; }
	</style>
</head>
<body>
	<h1>XML Sitemap <i>(<xsl:value-of select="$itemsum"/><xsl:value-of select="$type"/>, transformed with <xsl:value-of select="system-property('xsl:vendor')"/>)</i></h1>
	<header>
		<p>This is a XML Sitemap which is supposed to be processed by search engines like <a href="http://www.ask.com/">Ask</a>, <a href="http://www.baidu.com/">Baidu</a>, <a href="http://www.bing.com/">Bing</a>, <a href="http://blekko.com/">blekko</a>, <a href="http://duckduckgo.com/">DuckDuckGo</a>, <a href="http://www.google.com/">Google</a> or <a href="http://www.yahoo.com/">Yahoo!</a></p>
		<p>You can find more information about XML sitemaps on <a href="http://sitemaps.org/">sitemaps.org</a>.</p>
	</header>
	<table>
		<thead>
			<tr>
				<th class="first">URL</th>
				<th>Priority</th>
				<th>Change Frequency</th>
				<th class="last">Last Change (GMT)</th>
			</tr>
		</thead>
		<tbody>
			<xsl:variable name="lc" select="'abcdefghijklmnopqrstuvwxyz'"/>
			<xsl:variable name="uc" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
			<xsl:for-each select="sitemap:urlset/sitemap:url">
				<tr>
					<td class="first">
						<xsl:variable name="itemURL" select="sitemap:loc"/>
						<a href="{$itemURL}"><xsl:value-of select="$itemURL"/></a>
					</td>
					<td><xsl:value-of select="concat(sitemap:priority*100, '&#160;%')"/></td>
					<td><xsl:value-of select="concat(translate(substring(sitemap:changefreq, 1, 1), concat($lc, $uc), concat($uc, $lc)), substring(sitemap:changefreq, 2))"/></td>
					<td class="last"><xsl:value-of select="concat(substring(sitemap:lastmod, 0, 11), concat('&#160;', substring(sitemap:lastmod, 12, 5)))"/></td>
				</tr>
			</xsl:for-each>
		</tbody>
	</table>
	<footer> 
		<small>© 2010-<xsl:value-of select="substring(sitemap:urlset/sitemap:url/sitemap:lastmod, 0, 5)"/>&#160;<xsl:value-of select="substring-before(substring-after(sitemap:urlset/sitemap:url/sitemap:loc, 'http://'), '/')"/>/. Content managed with <a href="http://doogvaard.net/speelplaats/2011/07/04/saft/">Saft</a>.</small> 
	</footer>
</body>
</html>

</xsl:template>

</xsl:stylesheet>
