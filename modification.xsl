<?xml version="1.0" encoding="iso-8859-1"?>
<xsl:stylesheet
	version="1.0"
	xmlns:mod="http://www.simplemachines.org/xml/modification"
	xmlns:smf="http://www.simplemachines.org/"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output
	method="html"
	encoding="UTF-8"
	doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" />
	
<!-- $Revision$ - $Date$ -->
	
<xsl:template match="/">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>SMF Modification XML File</title>
	
	<link rel="stylesheet" type="text/css" href="http://dev.dansoftaustralia.net/svn/modparser/trunk/xslt/style.css" />
	<script type="text/javascript" src="http://dev.dansoftaustralia.net/svn/modparser/trunk/xslt/mootools.js"></script>
	<script type="text/javascript" src="http://dev.dansoftaustralia.net/svn/modparser/trunk/xslt/modification.js"></script>
</head>

<body>
	<div id="header">
		<a href="http://www.simplemachines.org/" target="_blank"><img src="http://www.smftools.org/images/smflogo.gif" style="width: 258px; float: right;" alt="Simple Machines" border="0" /></a>
		<div title="What? No hidden message?">SMF Modification XML File</div>
	</div>
	<div id="content">
		<div class="panel">
			<h1>General Package Information</h1>
			<h4>This is general information about the package, such as the package ID and name.</h4>
			<strong>Package ID: </strong> <xsl:value-of select="mod:modification/mod:id" /><br />
			<strong>Package Version: </strong> <xsl:value-of select="mod:modification/mod:version" /><br /><br />

			<xsl:for-each select="mod:modification/mod:file">
				<h2>In file <xsl:value-of select="@name" /></h2>
				<xsl:for-each select="mod:operation">
					<xsl:if test="mod:search/@position != 'end'">
						Find:<br /> 
						<xsl:call-template name="code">
							<xsl:with-param name="code" select="mod:search" />
						</xsl:call-template>
					</xsl:if>
					
					<xsl:choose>
						<xsl:when test="mod:search/@position = 'before'">
							Add after:
						</xsl:when>
						<xsl:when test="mod:search/@position = 'after'">
							Add before:
						</xsl:when>
						<xsl:when test="mod:search/@position = 'replace'">
							Replace with:
						</xsl:when>
						<xsl:when test="mod:search/@position = 'end'">
							Add at end of file:
						</xsl:when>
						<xsl:otherwise>
							Do something with this: (position not specified)
						</xsl:otherwise>
					</xsl:choose>
					<br />
											
					<xsl:call-template name="code">
						<xsl:with-param name="code" select="mod:add" />
					</xsl:call-template>
				</xsl:for-each>
				<br /><br />
			</xsl:for-each>
		</div>
	</div>
	<div class="footer">SMF modification stylesheet, by Daniel15. Last update 3rd June 2007 </div>
</body>
</html>

</xsl:template>

<!-- Code snipplets -->
<xsl:template name="code">
	<xsl:param name="code" />
	<pre>
		<!-- Replace < with &lt;. This looks rather weird, but works... -->
		<!-- Looks like the &lt; passed as "substringOut" is treated literally. -->
		<!-- Probably should be replaced with a more sophisticated algorithm. -->
		<xsl:variable name="code2">
			<xsl:call-template name="SubstringReplace">
				<xsl:with-param name="stringIn" select="$code"/>
				<xsl:with-param name="substringIn" select="'&lt;'"/>
				<xsl:with-param name="substringOut" select="'&lt;'"/>
			</xsl:call-template>
		</xsl:variable>
		
		<!-- Same thing for &gt;-->
		<xsl:call-template name="SubstringReplace">
			<xsl:with-param name="stringIn" select="$code2"/>
			<xsl:with-param name="substringIn" select="'&gt;'"/>
			<xsl:with-param name="substringOut" select="'&gt;'"/>
		</xsl:call-template>
	</pre>
</xsl:template>

<!--
	This template is from http://skew.org/xml/, by Mike J. Brown <mike@skew.org>.
	Its license allows free distribution. 
-->
<xsl:template name="SubstringReplace">
	<xsl:param name="stringIn"/>
	<xsl:param name="substringIn"/>
	<xsl:param name="substringOut"/>
	<xsl:choose>
		<xsl:when test="contains($stringIn,$substringIn)">
			<xsl:value-of select="concat(substring-before($stringIn,$substringIn),$substringOut)"/>
			<xsl:call-template name="SubstringReplace">
				<xsl:with-param name="stringIn" select="substring-after($stringIn,$substringIn)"/>
				<xsl:with-param name="substringIn" select="$substringIn"/>
				<xsl:with-param name="substringOut" select="$substringOut"/>
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$stringIn"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>
</xsl:stylesheet>
