<?xml version="1.0" encoding="iso-8859-1"?>
<xsl:stylesheet
	version="1.0"
	xmlns:pack="http://www.simplemachines.org/xml/package-info"
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
	<title>SMF package-info.xml File</title>
	
	<link rel="stylesheet" type="text/css" href="http://dev.dansoftaustralia.net/svn/modparser/trunk/xslt/style.css" />
	<script type="text/javascript" src="http://dev.dansoftaustralia.net/svn/modparser/trunk/xslt/mootools.js"></script>
	<script type="text/javascript" src="http://dev.dansoftaustralia.net/svn/modparser/trunk/xslt/package-info.js"></script>
	<script type="text/javascript">
		// Start with an empty upgrades list
		var upgrades = [];
		
		// Add an upgrade to the list of upgrades. This is defined here because
		// Opera seems to load the JavaScript files *after* the rest of the page
		// has loaded, not as soon as the tag is encountered (like other
		// browsers). Because of this, it's not in the package-info.js file, and
		// this function is not dependant on any of the mootools functions.
		
		// id = ID of the div.
		// from = Version this upgrade is coming from.
		// smf_for = What SMF version this upgrade is for (blank for none specified).
		function addUpgrade(id, from, smf_for)
		{
			// No SMF version? Set it to 0.
			if (smf_for == '')
				smf_for = 0;
				
			// Add this upgrade to the upgrades list.
			upgrades.push(
			{
				'id': id,
				'from': from,
				'smf_for': smf_for
			});	
		}
	</script>
</head>

<body>
	<div id="header">
		<a href="http://www.simplemachines.org/" target="_blank"><img src="http://www.smftools.org/images/smflogo.gif" style="width: 258px; float: right;" alt="Simple Machines" border="0" /></a>
		<div title="Daniel15 is cool! :-)">SMF package-info.xml file</div>
	</div>
	<div id="content">
		<div class="panel">
			<h1>General Package Information</h1>
			<h4>This is general information about the package, such as the package ID and name.</h4>
			<strong>Package ID: </strong> <xsl:value-of select="pack:package-info/pack:id" /><br />
			<strong>Package Name: </strong> <xsl:value-of select="pack:package-info/pack:name" /><br />
			<strong>Package Version: </strong> <xsl:value-of select="pack:package-info/pack:version" /><br />
			<strong>Package Type: </strong> <xsl:value-of select="pack:package-info/pack:type" />
		</div>

		<!-- Installation stuff -->
		<div id="install" class="panel">
			<!-- Dropdown box with supported SMF versions -->
			<xsl:call-template name="version-dropdown">
				<xsl:with-param name="type" select="'install'" />
				<xsl:with-param name="section" select="pack:package-info/pack:install" />
			</xsl:call-template>
				
			<!-- Loop through all install sections. This is done here (as opposed to a seperate template) due to bugs with mootools 1.1 (getElement() doesn't work on divs output by a template called by xsl:call-template)... FYI, it worked in mootools 1.0. -->
			<xsl:for-each select="pack:package-info/pack:install">
				<div id="install-{generate-id(@for)}">
					<h1>
						Installation Instructions for SMF version:
						<xsl:choose>
							<xsl:when test="@for">
								<xsl:value-of select="@for" />
							</xsl:when>
							<xsl:otherwise>
								Any not matched above 
							</xsl:otherwise>
						</xsl:choose>
					</h1>
					<!-- Show the actual information -->
					<xsl:call-template name="modification-section" />
				</div>
			</xsl:for-each>
		</div>
		<br />
		
		<!-- Uninstallation stuff -->
		<div id="uninstall" class="panel">
			<!-- Dropdown box with supported SMF versions -->
			<xsl:call-template name="version-dropdown">
				<xsl:with-param name="type" select="'uninstall'" />
				<xsl:with-param name="section" select="pack:package-info/pack:uninstall" />
			</xsl:call-template>
				
			<!-- Loop through all uninstall sections -->
			<xsl:for-each select="pack:package-info/pack:uninstall">
				<div id="uninstall-{generate-id(@for)}">
					<h1>
						Uninstallation Instructions for SMF version:
						<xsl:choose>
							<xsl:when test="@for">
								<xsl:value-of select="@for" />
							</xsl:when>
							<xsl:otherwise>
								Any not matched above 
							</xsl:otherwise>
						</xsl:choose>
					</h1>
					<!-- Show the actual information -->
					<xsl:call-template name="modification-section" />
				</div>
			</xsl:for-each>
		</div>
		<br />
		
		<!-- Upgrades -->
		<div id="upgrade" class="panel">
			<h1>Upgrades</h1>
			<label>
				Choose old mod version:
				<!-- Dropdown list of versions -->
				<select name="upgrade_version" id="upgrade_version" onchange="switchUpgrade(this.value);">
					<option value="-1">[Choose a version]</option>
					<!-- Will be populated by packageInfo_init() at runtime -->
				</select>
			</label><br />
			
			<label>
				Choose SMF version:
				<select name="upgrade_smfversion" id="upgrade_smfversion" onchange="switchDiv('upgrade', this.value);">
					<!-- Will be populated by switchUpgrade() at runtime -->
				</select>
			</label>
			
			<!-- Loop through all the upgrade sections -->
			<xsl:for-each select="pack:package-info/pack:upgrade">
				<!-- ID is generated from both from and for, to be unique -->
				<div id="{concat('upgrade-', generate-id(@from), '-', generate-id(@for))}">
					<h1>
						Upgrade from version <xsl:value-of select="@from" />
						<xsl:if test="@for">
							(for SMF version <xsl:value-of select="@for" />)
						</xsl:if>
					</h1>
					<!-- Template for this is below -->
					<xsl:call-template name="modification-section" />
				</div>
				<!-- Add this section to the upgrade JavaScript list -->
				<script type="text/javascript">
					addUpgrade('<xsl:value-of select="concat('upgrade-', generate-id(@from), '-', generate-id(@for))" />', '<xsl:value-of select="@from" />', '<xsl:value-of select="@for" />');
				</script>
			</xsl:for-each>
		</div>
	</div>
	
	<div class="footer">SMF package-info.xml stylesheet, by Daniel15. Last update 3rd June 2007</div>
</body>
</html>

</xsl:template>

<!-- Version dropdown box, for install/uninstall listings -->
<xsl:template name="version-dropdown">
	<xsl:param name="type" />
	<xsl:param name="section" />
	
	<label>
		Choose SMF version:
		<!-- Dropdown list of versions -->
		<select name="{$type}_version" onchange="switchDiv('{$type}', '{$type}-' + this.value);">
			<xsl:for-each select="$section">
				<option value="{generate-id(@for)}">
					<xsl:choose>
						<xsl:when test="@for">
							<xsl:value-of select="@for" />
						</xsl:when>
						<xsl:otherwise>
							Any other version 
						</xsl:otherwise>
					</xsl:choose>
				</option>
			</xsl:for-each>
		</select>
	</label>
</xsl:template>

<!-- Template for modification section (actual modification itself) -->
<xsl:template name="modification-section">
	<xsl:if test="pack:readme">
		<xsl:choose>
			<xsl:when test="pack:readme/@type = 'inline'">
				<h3>Readme</h3>
				<em><xsl:value-of select="pack:readme" /></em><br /><hr />
			</xsl:when>
			<xsl:otherwise>
				<strong>Readme file: </strong> <xsl:value-of select="pack:readme" />
			</xsl:otherwise>
		</xsl:choose>
		<br />
	</xsl:if>
	
	<xsl:if test="pack:code">
		<strong>PHP script to execute: </strong> <xsl:value-of select="pack:code" />
		<br />
	</xsl:if>
	
	<xsl:if test="pack:modification">
		<xsl:for-each select="pack:modification">
			<strong>Modification file: </strong> <xsl:value-of select="." /><br />
		</xsl:for-each>
	</xsl:if>
	<br />
	<xsl:if test="pack:require-file">
		<h2>Files Installed</h2>
		<ul>
			<xsl:for-each select="pack:require-file">
				<li><xsl:value-of select="@name" /> will be copied to <xsl:value-of select="@destination" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>

	<xsl:if test="pack:require-dir">
		<h2>Directories Installed</h2>
		<ul>
			<xsl:for-each select="pack:require-dir">
				<li><xsl:value-of select="@name" /> will be copied to <xsl:value-of select="@destination" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>
	
	<xsl:if test="pack:create-dir">
		<h2>Directories Created</h2>
		<ul>
			<xsl:for-each select="pack:create-dir">
				<li><xsl:value-of select="@name" /> will be created in <xsl:value-of select="@destination" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>
	
	<xsl:if test="pack:create-file">
		<h2>Files Created</h2>
		<ul>
			<xsl:for-each select="pack:create-file">
				<li>A blank file named <xsl:value-of select="@name" /> will be created in <xsl:value-of select="@destination" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>
	
	<xsl:if test="pack:move-dir">
		<h2>Directories moved</h2>
		<ul>
			<xsl:for-each select="pack:move-dir">
				<li><xsl:value-of select="@name" /> will be moved from <xsl:value-of select="@from" /> to <xsl:value-of select="@destination" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>
	
	<xsl:if test="pack:move-file">
		<h2>Files moved</h2>
		<ul>
			<xsl:for-each select="pack:move-file">
				<li><xsl:value-of select="@name" /> will be moved from <xsl:value-of select="@from" /> to <xsl:value-of select="@destination" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>
	
	<xsl:if test="pack:remove-dir">
		<h2>Directories deleted</h2>
		<ul>
			<xsl:for-each select="pack:remove-dir">
				<li><xsl:value-of select="@name" /> will be deleted</li>
			</xsl:for-each>
		</ul>
	</xsl:if>
	
	<xsl:if test="pack:remove-file">
		<h2>Files deleted</h2>
		<ul>
			<xsl:for-each select="pack:remove-file">
				<li><xsl:value-of select="@name" /> will be deleted</li>
			</xsl:for-each>
		</ul>
	</xsl:if>
</xsl:template>
</xsl:stylesheet>
