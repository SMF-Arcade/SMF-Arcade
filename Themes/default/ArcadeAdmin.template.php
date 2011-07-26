<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function template_arcade_admin_main()
{
	global $context, $settings, $options, $txt, $modSettings, $arcade_version;

	echo '
	<div style="width: 49%" class="floatleft">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_latest_news'], '
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div id="arcade_news" style="overflow: auto; height: 18ex; padding: 0.5em;">', sprintf($txt['arcade_unable_to_connect'], 'SMFArcade.info'), '</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>
	<div style="width: 49%" class="floatright">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_status'], '
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div style="overflow: auto; height: 18ex; padding: 0.5em;">
				', $txt['arcade_installed_version'], ': <span id="arcade_installed_version">', $arcade_version, '</span><br />
				', $txt['arcade_latest_version'], ': <span id="arcade_latest_version">???</span>
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>
	<br style="clear: both" />
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		function setArcadeNews()
		{
			if (typeof(window.arcadeNews) == "undefined" || typeof(window.arcadeNews.length) == "undefined")
					return;

				var str = "<div style=\"margin: 4px; font-size: 0.85em;\">";

				for (var i = 0; i < window.arcadeNews.length; i++)
				{
					str += "\n	<div style=\"padding-bottom: 2px;\"><a href=\"" + window.arcadeNews[i].url + "\">" + window.arcadeNews[i].subject + "</a> on " + window.arcadeNews[i].time + "</div>";
					str += "\n	<div style=\"padding-left: 2ex; margin-bottom: 1.5ex; border-top: 1px dashed;\">"
					str += "\n		" + window.arcadeNews[i].message;
					str += "\n	</div>";
				}

				setInnerHTML(document.getElementById("arcade_news"), str + "</div>");
		}

		function setArcadeVersion()
		{
			if (typeof(window.arcadeCurrentVersion) == "undefined")
				return;

			setInnerHTML(document.getElementById("arcade_latest_version"), window.arcadeCurrentVersion);
		}
	// ]]></script>
	<script language="JavaScript" type="text/javascript" src="http://service.smfarcade.info/arcade/news.js?v=', urlencode($arcade_version), '" defer="defer"></script>';
}

function template_arcade_admin_maintenance()
{
	global $scripturl, $txt, $context, $settings;

	if ($context['maintenance_finished'])
		echo '
	<div class="windowbg" style="margin: 1ex; padding: 1ex 2ex; border: 1px dashed green; color: green;">
		', $txt['arcade_maintain_done'], '
	</div>';

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['arcade_maintenance'], '
		</h3>
	</div>
	<div class="windowbg">
		<span class="topslice"><span></span></span>
			<div style="padding: 0.5em;">
				<ul>
					<li><a href="', $scripturl , '?action=admin;area=arcademaintenance;maintenance=fixScores;' . $context['session_var'] . '=', $context['session_id'], '">', $txt['arcade_maintenance_fixScores'], '</a></li>
					<li><a href="', $scripturl , '?action=admin;area=arcademaintenance;maintenance=updateGamecache;' . $context['session_var'] . '=', $context['session_id'], '">', $txt['arcade_maintenance_updateGamecache'], '</a></li>
				</ul>
			</div>
		<span class="botslice"><span></span></span>
	</div>';

}

function template_arcade_admin_maintenance_highscore()
{
	global $scripturl, $txt, $context, $settings;


	echo '
	<form name="category" action="', $scripturl, '?action=admin;area=arcademaintenance;sa=highscore" method="post">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_maintenance'], ' - ', $txt['arcade_maintenance_highscore'], '
			</h3>
		</div>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
				<div style="padding: 0.5em;">
					<div>
						<input type="radio" name="score_action" value="older" />', $txt['arcade_remove_scores_older_than'], ' <input name="age" value="30" /> ', $txt['arcade_remove_scores_days'], '
					</div>
					<div>
						<input type="radio" name="score_action" value="all" />', $txt['arcade_remove_all_scores'], '
					</div>
					<div style="margin: 1ex;" align="right">
						<input class="button_submit" type="submit" name="clear_score" value="', $txt['arcade_remove_now'], '" />
					</div>
				</div>
			<span class="botslice"><span></span></span>
		</div>
		
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';
}

function template_arcade_admin_category_list()
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<form name="category" action="', $scripturl, '?action=admin;area=arcadecategory;save" method="post">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_categories'], '
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div style="padding: 0.5em;">
				<table border="0" cellspacing="0" cellpadding="4" width="100%" align="center">';

	foreach ($context['arcade_category'] as $category)
	{
		echo '
					<tr>
						<td width="20" align="center" valign="top" style="margin-top: 5px;">
							<input id="cat', $category['id'], '" type="checkbox" name="category[', $category['id'], ']" value="', $category['id'], '" style="check" />
						</td>
						<td width="50" align="left" valign="top" style="margin-top: 5px;">
							<input type="text" name="category_order[', $category['id'], ']" value="', $category['order'], '" style="width: 100%;" />
						</td>
						<td valign="top">
							<a href="', $category['href'], '">', $category['name'], '</a>
						</td>
					</tr>';
	}

	echo '
				</table>
				<input class="button_submit" type="submit" name="save_settings" value="', $txt['arcade_save_category'], '" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
		
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';
}

function template_arcade_admin_category_edit()
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<form name="category" action="', $scripturl, '?action=admin;area=arcadecategory;sa=save" method="post">
		<input type="hidden" name="category" value="', $context['category']['id'], '" />
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_categories'], '
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div style="padding: 0.5em;">
				<table border="0" cellspacing="0" cellpadding="4" width="100%" align="center">		
					<tr class="windowbg2">
						<td>', $txt['category_name'], '</td>
						<td width="50%"><input type="text" name="category_name" value="', $context['category']['name'], '" /></td>
					</tr>
					<tr class="windowbg2">
						<td>', $txt['arcade_category_permission_allowed'], '</td>
						<td width="50%">';

	foreach ($context['groups'] as $group)
		echo '
							<label for="groups_', $group['id'], '"><input type="checkbox" name="groups[]" value="', $group['id'], '" id="groups_', $group['id'], '"', $group['checked'] ? ' checked="checked"' : '', ' class="check" /><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label><br />';

	echo '
							<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" class="check" /><br />
							<br />
						</td>
					</tr>
				</table>
				<input class="button_submit" type="submit" name="save_settings" value="', $txt['arcade_save_category'], '" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
		
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';
}

function template_arcadeadmin_above()
{
	global $scripturl, $txt, $modSettings, $context, $settings, $arcade_version;

}

function template_arcadeadmin_below()
{
	global $arcade_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="arcade_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfarcade.info/" target="_blank">SMF Arcade ', $arcade_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2004-2009
	</div>';

}

?>
