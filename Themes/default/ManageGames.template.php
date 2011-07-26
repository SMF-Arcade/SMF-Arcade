<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function template_manage_games_list()
{
	global $context, $txt, $scripturl, $settings;

	if (!empty($context['qaction_title']))
	{
		echo '
		<div id="arcade_message">
			<div class="windowbg" style="margin: 1ex; padding: 1ex 2ex; border: 1px dashed green; color: green;">
				<div style="text-decoration: underline;" id="arcade_message_title">', $context['qaction_title'], '</div>
				<div id="arcade_message_text">', $context['qaction_text'], ':
					<ul>';

		if (!empty($context['qaction_data']))
		{
			foreach ($context['qaction_data'] as $game)
			{
				echo '
						<li>', $game['name'], !empty($game['error']) ? '<div class="smalltext" style="color: red">' . vsprintf($txt[$game['error'][0]], $game['error'][1]) . '</div>' : '', '</li>';
			}
		}

		echo '
					</ul>
				</div>
			</div>
		</div><br />';
	}

	template_show_list('games_list');
}

function template_manage_games_uninstall_confirm()
{
	global $context, $txt, $scripturl, $settings;

	echo '
	<form action="', $context['confirm_url'], ';confirm" method="post">
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />

		<div class="tborder">
			<table class="bordercolor" border="0" cellpadding="4" cellspacing="1" width="100%">
				<tr class="catbg">
					<td>', $context['confirm_title'], '</td>
				</tr>
				<tr>
				<td class="windowbg2">
					', $context['confirm_text'], '
					<br />
					<input type="checkbox" name="remove_files" value="1" class="check" /> ', $txt['uninstall_remove_files'], '<br />
					<table border="0" cellpadding="1" cellspacing="0" width="100%">
						<tr>
							<td width="10"></td>
							<td><b>', $txt['arcade_game_name'], '</b></td>
						</tr>';

	if (!empty($context['games']))
	{
		$alternate = true;

		foreach ($context['games'] as $id => $game)
		{
			echo '
						<tr class="windowbg', $alternate? '' : '2', '">
							<td><input id="game', $id, '" type="checkbox" name="game[]" value="', $id, '" checked="checked" class="check" /></td>
							<td><label for="game', $id, '">', $game['name'], '</label></td>
						</tr>';

			$alternate = !$alternate;
		}
	}

	echo '
					</table>
				</td></tr>
				<tr class="windowbg2">
					<td align="right">
						<input class="button_submit" type="submit" name="save" value="', $context['confirm_button'], '" />
					</td>
				</tr>
			</table>
		</div>
	</form>';
}

function template_manage_games_upload()
{
	global $scripturl, $context, $txt;

	echo '
	<form action="', $scripturl, '?action=admin;area=managegames;sa=upload2" method="post" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_upload'], '
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div style="padding: 0.5em;">
				<input type="file" size="48" name="attachment[]" /><br />
				<input type="file" size="48" name="attachment[]" /><br />
				<input type="file" size="48" name="attachment[]" /><br />
	
				<span class="smalltext">', $txt['post_max_size'], ' ', $context['post_max_size'], ' MB</span><br />
				<input class="button_submit" type="submit" name="upload" value="', $txt['arcade_upload_button'], '" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';
}

function template_edit_game_above()
{
	global $scripturl, $context, $txt;

	echo '
	<form action="', $scripturl, '?action=admin;area=managegames;sa=edit2" method="post">
		<input type="hidden" name="game" value="', $context['game']['id'], '" />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="edit_page" value="', $context['edit_page'], '" />

		<div class="cat_bar">
			<h3 class="catbg">
				', $context['game']['name'], '
			</h3>
		</div>
		<div class="windowbg">
			<span class="topslice"><span></span></span>';

	if (isset($context['errors']))
	{
		echo '
				<ul style="color: red">';

		foreach ($context['errors'] as $field => $error)
			echo '
						<li>', $field, ': ', $error, '</li>';

		echo '
				</ul>';
	}

	echo '
			<table class="bordercolor" border="0" cellpadding="4" cellspacing="1" width="100%">
				<tr class="windowbg">
					<td>
						<table width="100%">';
}

function template_edit_game_basic()
{
	global $scripturl, $context, $txt;

	echo '
	<tr>
		<td><label for="game_name">', $txt['arcade_game_name'], '</label></td>
		<td><input type="text" name="game_name" id="game_name"  value="', $context['game']['name'], '" style="width: 99%" /></td>
	</tr><tr>
		<td><label for="thumbnail">', $txt['arcade_thumbnail'], '</label></td>
		<td><input type="text" name="thumbnail" id="thumbnail"  value="', $context['game']['thumbnail'], '" style="width: 99%" /></td>
	</tr><tr>
		<td><label for="thumbnail_small">', $txt['arcade_thumbnail_small'], '</label></td>
		<td><input type="text" name="thumbnail_small" id="thumbnail_small"  value="', $context['game']['thumbnail_small'], '" style="width: 99%" /></td>
	</tr><tr>
		<td><label for="game_enabled">', $txt['arcade_enable_game'], '</label></td>
		<td><input type="checkbox" name="game_enabled" id="game_enabled"  value="1" ', $context['game']['enabled'] ? ' checked="checked"' : '', ' /></td>
	</tr><tr>
		<td><label for="description">', $txt['arcade_description'], '</label></td>
		<td>
			<textarea name="description" id="description"  rows="5" cols="40" style="width: 99%">', isset($context['game']['description']) ? $context['game']['description'] : '', '</textarea>
		</td>
	</tr><tr>
		<td><label for="help">', $txt['arcade_help'], '</label></td>
		<td>
			<textarea name="help" id="help"  rows="5" cols="40" style="width: 99%">', isset($context['game']['help']) ? $context['game']['help'] : '', '</textarea>
		</td>
	</tr><tr>
		<td><label for="category">', $txt['arcade_category'], '</label></td>
		<td>
			<select id="category" name="category">
				<option value="0">', $txt['arcade_no_category'], '</option>';

	foreach ($context['arcade_category'] as $cat)
		echo '
				<option value="', $cat['id'], '"', $cat['id'] == $context['game']['category'] ? ' selected="selected"' : '', '>', $cat['name'], '</option>';
	echo '
			</select>
		</td>
	</tr>';

	if (!empty($context['game_permissions']))
	{
		echo '
	<tr>
		<td>', $txt['arcade_membergroups'], '</td>
		<td>';

	foreach ($context['groups'] as $group)
		echo '
			<label for="groups_', $group['id'], '"><input type="checkbox" name="groups[]" value="', $group['id'], '" id="groups_', $group['id'], '"', $group['checked'] ? ' checked="checked"' : '', ' class="check" /><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label><br />';

	echo '
			<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" class="check" /><br />
			<br />
		</td>
	</tr>';
	}

	echo '
	<tr>
		<td align="right" colspan="2"><a href="', $scripturl, '?action=admin;area=managegames;sa=edit;game=', $context['game']['id'], ';advanced">', $txt['arcade_advanced'], '</a></td>
	</tr>';

}

function template_edit_game_advanced()
{
	global $scripturl, $context, $txt;

	echo '
	<tr>
		<td><label for="internal_name">', $txt['arcade_internal_name'], '</label></td>
		<td><input type="text" id="internal_name" name="internal_name" value="', $context['game']['internal_name'], '" style="width: 99%" /></td>
	</tr>
	<tr>
		<td><label for="game_directory">', $txt['arcade_directory'], '</label></td>
		<td><input type="text" id="game_directory" name="game_directory" value="', $context['game']['game_directory'], '" style="width: 99%" /></td>
	</tr>
	<tr>
		<td><label for="game_file">', $txt['arcade_file'], '</label></td>
		<td><input type="text" id="game_file" name="game_file" value="', $context['game']['game_file'], '" style="width: 99%" /></td>
	</tr>
	<tr>
		<td><label for="submit_system">', $txt['arcade_submit_system'], '</label></td>
		<td>
			<select id="submit_system" name="submit_system">';

	foreach ($context['submit_systems'] as $system)
		echo '
				<option value="', $system['system'], '"', $context['game']['submit_system'] == $system['system'] ? ' selected="selected"' : '', '>', $system['name'], '</option>';

	echo '
			</select>
		</td>
	</tr>
	<tr>
		<td><label for="score_type">', $txt['arcade_score_type'], '</label></td>
		<td>
			<select id="score_type" name="score_type">
				<option value="0"', $context['game']['score_type'] == 0 ? ' selected="selected"' : '', '>', $txt['arcade_score_normal'], '</option>
				<option value="1"', $context['game']['score_type'] == 1 ? ' selected="selected"' : '', '>', $txt['arcade_score_reverse'], '</option>
				<option value="2"', $context['game']['score_type'] == 2 ? ' selected="selected"' : '', '>', $txt['arcade_score_none'], '</option>
			</select>
		</td>
	</tr>';

	if (isset($context['game']['extra_data']['flash_version']) || isset($_REQUEST['flash']))
	{
		echo '
	<tr>
		<td>', $txt['arcade_extra_options_flash'], '</td>
		<td>
			<table width="100%">
				<tr>
					<td width="25%">', $txt['arcade_extra_options_width'], '</td>
					<td><input type="text" name="extra_data[width]" value="', $context['game']['extra_data']['width'], '" /></td>
				</tr>
				<tr>
					<td width="25%">', $txt['arcade_extra_options_height'], '</td>
					<td><input type="text" name="extra_data[height]" value="', $context['game']['extra_data']['height'], '" /></td>
				</tr>
				<tr>
					<td width="25%">', $txt['arcade_extra_options_version'], '</td>
					<td><input type="text" name="extra_data[flash_version]" value="', $context['game']['extra_data']['flash_version'], '" /></td>
				</tr>
				<tr>
					<td width="25%">', $txt['arcade_extra_options_backgroundcolor'], '</td>
					<td>
						<input type="text" name="extra_data[background_color][0]" value="', $context['game']['extra_data']['background_color'][0], '" size="4"/>
						<input type="text" name="extra_data[background_color][1]" value="', $context['game']['extra_data']['background_color'][1], '" size="4"/>
						<input type="text" name="extra_data[background_color][2]" value="', $context['game']['extra_data']['background_color'][2], '" size="4"/>
					</td>
				</tr>
			</table>
		</td>
	</tr>';
	}

	echo '
	<tr>
		<td align="right" colspan="2"><a href="', $scripturl, '?action=admin;area=managegames;sa=edit;game=', $context['game']['id'], '">', $txt['arcade_basic_settings'], '</a></td>
	</tr>';

}

function template_edit_game_below()
{
	global $scripturl, $context, $txt;

	echo '
							<tr>
								<td align="right" colspan="2">
									<input class="button_submit" type="submit" name="save" value="', $txt['arcade_save'], '" /><br />
									<a href="', $scripturl, '?action=admin;area=managegames;sa=export;game=', $context['game']['id'], '">', $txt['game_info_export'], '</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<span class="botslice"><span></span></span>
		</div>
	</form>';
}

?>