<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function template_arcade_game_above()
{
	global $scripturl, $txt, $context, $settings;

	// Play link
	$context['arcade']['buttons']['play'] =  array(
		'text' => 'arcade_play',
		'image' => 'arcade_play.gif', // Theres no image for this included (yet)
		'url' => !empty($context['arcade']['play']) ? $scripturl . '?action=arcade;sa=play;game=' . $context['game']['id'] . '" onclick="arcadeRestart(); return false;' : $scripturl . '?action=arcade;sa=play;game=' . $context['game']['id'],
		'lang' => true
	);

	// Highscores link if it is supported
	if ($context['game']['highscore_support'])
		$context['arcade']['buttons']['score'] =  array(
			'text' => 'arcade_viewscore',
			'image' => 'arcade_viewscore.gif', // Theres no image for this included (yet)
			'url' => $scripturl . '?action=arcade;sa=highscore;game=' . $context['game']['id'],
			'lang' => true
		);

	// Random game
	$context['arcade']['buttons']['random'] =  array(
		'text' => 'arcade_random_game',
		'image' => 'arcade_random.gif', // Theres no image for this included (yet)
		'url' => $scripturl . '?action=arcade;sa=play;random',
		'lang' => true
	);

	if ($context['arcade']['can_admin_arcade'])
		$context['arcade']['buttons']['edit'] =  array(
			'text' => 'arcade_edit_game',
			'image' => 'arcade_edit_game.gif', // Theres no image for this included (yet)
			'url' => $scripturl . '?action=admin;area=managegames;sa=edit;game=' . $context['game']['id'],
			'lang' => true
		);

	$ratecode = '';
	$rating = $context['game']['rating'];


	if ($context['arcade']['can_rate'])
	{
		// Can rate

		for ($i = 1; $i <= 5; $i++)
		{
			if ($i <= $rating)
				$ratecode .= '<a href="' . $scripturl . '?action=arcade;sa=rate;game=' . $context['game']['id'] . ';rate=' . $i . ';' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="arcade_rate(' . $i . ', ' . $context['game']['id'] . '); return false;"><img id="imgrate' . $i . '" src="' . $settings['images_url'] . '/arcade_star.gif" alt="*" /></a>';

			else
				$ratecode .= '<a href="' . $scripturl . '?action=arcade;sa=rate;game=' . $context['game']['id'] . ';rate=' . $i . ';' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="arcade_rate(' . $i . ', ' . $context['game']['id'] . '); return false;"><img id="imgrate' . $i . '" src="' . $settings['images_url'] . '/arcade_star2.gif" alt="*" /></a>';
		}
	}
	else
	{
		// Can't rate
		$ratecode = str_repeat('<img src="' . $settings['images_url'] . '/arcade_star.gif" alt="*" />' , $rating);
		$ratecode .= str_repeat('<img src="' . $settings['images_url'] . '/arcade_star2.gif" alt="" />' , 5 - $rating);
	}

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span class="floatleft">', $context['game']['name'], '</span>
			<img id="game_toggle" class="floatright" src="', $settings['images_url'], '/collapse.gif', '" alt="*" title="', $txt['upshrink_description'], '" align="bottom" style="margin: 0 1ex; display: none;" />
		</h3>
	</div>
	<div id="game_panel" class="windowbg2" style="', empty($options['game_panel_collapse']) ? '' : ' display: none;', '">
		<span class="topslice"><span></span></span>
		', !empty($context['game']['thumbnail']) ? '<img class="floatleft thumb" src="' . $context['game']['thumbnail'] . '" alt="" />' : '', '
		<div class="floatleft scores">';

	if ($context['game']['is_champion'])
		echo '
				<strong>', $txt['arcade_champion'], ':</strong> ', $context['game']['champion']['link'], ' - ', $context['game']['champion']['score'], '<br />';
	if ($context['game']['is_personal_best'])
		echo '
				<strong>', $txt['arcade_personal_best'], ':</strong> ', $context['game']['personal_best'], '<br />';

	echo '
		</div>
		<div class="floatright rating" style="text-align: right">';

	if ($context['arcade']['can_favorite'])
		echo '
			<a href="', $context['game']['url']['favorite'], '" onclick="arcade_favorite(', $context['game']['id'], '); return false;">', !$context['game']['is_favorite'] ?  '<img id="favgame' . $context['game']['id'] . '" src="' . $settings['images_url'] . '/favorite.gif" alt="' . $txt['arcade_add_favorites'] . '" />' : '<img id="favgame' . $context['game']['id'] . '" src="' . $settings['images_url'] . '/favorite2.gif" alt="' . $txt['arcade_remove_favorite'] . '" />', '</a><br />';

	if ($context['arcade']['can_rate'])
		echo '
			', $ratecode, '<br />';

	echo '
		</div>
		<br class="clear" />
		<span class="botslice"><span></span></span>
	</div>
	<script type="text/javascript"><!-- // --><![CDATA[
		var oGameHeaderToggle = new smc_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: ', empty($options['game_panel_collapse']) ? 'false' : 'true', ',
			aSwappableContainers: [
				\'game_panel\'
			],
			aSwapImages: [
				{
					sId: \'game_toggle\',
					srcExpanded: smf_images_url + \'/collapse.gif\',
					altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ',
					srcCollapsed: smf_images_url + \'/expand.gif\',
					altCollapsed: ', JavaScriptEscape($txt['upshrink_description']), '
				}
			],
			oThemeOptions: {
				bUseThemeSettings: ', $context['user']['is_guest'] ? 'false' : 'true', ',
				sOptionName: \'game_panel_collapse\',
				sSessionVar: ', JavaScriptEscape($context['session_var']), ',
				sSessionId: ', JavaScriptEscape($context['session_id']), '
			},
			oCookieOptions: {
				bUseCookie: ', $context['user']['is_guest'] ? 'true' : 'false', ',
				sCookieName: \'arcadegameupshrink\'
			}
		});
	// ]]></script>';
}

// Play screen
function template_arcade_game_play()
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<div class="pagesection">
		<div class="align_left">', !empty($modSettings['topbottomEnable']) ? '<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
		', template_button_strip($context['arcade']['buttons'], 'right'), '
	</div>';
	
	$context['game_class']->Render();
	
	/*
			', $context['game']['html']($context['game'], true), '
			', !$context['arcade']['can_submit'] ? '<br /><strong>' . $txt['arcade_cannot_save'] . '</strong>' : '', '
		';*/

}

// Highscore
function template_arcade_game_highscore()
{
	global $scripturl, $txt, $context, $settings;

	if (isset($context['arcade']['submit']))
	{
		if ($context['arcade']['submit'] == 'newscore') // Was score submitted
		{
			$score = &$context['arcade']['new_score'];

			echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['arcade_submit_score'], '
		</h3>
	</div>
	<div class="windowbg2">
		<span class="topslice"><span></span></span>
		<div style="padding: 0 0.5em">';

			// No permission to save
			if (!$score['saved'])
				echo '
			<div>
				', $txt[$score['error']], '<br />
				<strong>', $txt['arcade_score'], ':</strong> ', $score['score'], '
			</div>';

			else
			{
				echo '
			<div>
				', $txt['arcade_score_saved'], '<br />
				<strong>', $txt['arcade_score'], ':</strong> ', $score['score'], '<br />';

				if ($score['is_new_champion'])
					echo '
				', $txt['arcade_you_are_now_champion'], '<br />';

				elseif ($score['is_personal_best'])
					echo '
				', $txt['arcade_this_is_your_best'], '<br />';

				if ($score['can_comment'])
					echo '
			</div>
			<div>
				<form action="', $scripturl, '?action=arcade;sa=highscore;game=', $context['game']['id'], ';score=',  $score['id'], '" method="post">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input type="text" id="new_comment" name="new_comment" style="width: 95%;" />
					<input class="button_submit" type="submit" name="csave" value="', $txt['arcade_save'], '" />
				</form>
			</div>';
			}

			echo '
		</div>
		<span class="botslice"><span></span></span>
	</div>
	<br />';
		}
		elseif ($context['arcade']['submit'] == 'askname')
		{
			echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['arcade_submit_score'], '
		</h3>
	</div>
	<div class="windowbg2">
		<span class="topslice"><span></span></span>
		<div style="padding: 0 0.5em">
			<form action="', $scripturl, '?action=arcade;sa=save" method="post">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				<input type="text" name="name" style="width: 95%;" />
				<input class="button_submit" type="submit" value="', $txt['arcade_save'], '" />
			</form>
		</div>
	</div><br />';
		}
	}

	echo '
	<div class="pagesection">
		<div class="align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
		', template_button_strip($context['arcade']['buttons'], 'right'), '
	</div>
	<form name="score" action="', $scripturl, '?action=arcade;sa=highscore" method="post">
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="game" value="', $context['game']['id'], '" />
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['arcade_highscores'], '
			</h3>
		</div>
		<div class="score_table">
			<table cellspacing="0" class="table_grid">
				<thead>
					<tr class="catbg">';

	// Is there games?
	if (!empty($context['arcade']['scores']))
	{
			echo '
						<th scope="col" class="first_th" width="5">', $txt['arcade_position'], '</th>
						<th scope="col">', $txt['arcade_member'], '</th>
						<th scope="col"> ', $txt['arcade_comment'], '</th>
						<th scope="col" class="', !$context['arcade']['can_admin_arcade'] ? ' last_th' : '', '">', $txt['arcade_score'], '</th>';

		if ($context['arcade']['can_admin_arcade'])
			echo '
						<th scope="col" class="last_th" align="center" width="15"><input type="checkbox" onclick="invertAll(this, this.form, \'scores[]\');" class="check" /></th>';
	}
	else
	{
		echo '
						<th scope="col" class="first_th" width="8%">&nbsp;</th>
						<th class="smalltext" colspan="', !$context['arcade']['can_admin_arcade'] ? '2' : '3', '"><strong>', $txt['arcade_no_scores'], '</strong></th>
						<th scope="col" class="last_th" width="8%">&nbsp;</th>';
	}

	echo '
					</tr>
				</thead>
				<tbody>';

	$edit_button = create_button('modify.gif', 'arcade_edit', '', 'title="' . $txt['arcade_edit'] . '"');

	foreach ($context['arcade']['scores'] as $score)
	{
		$div_con = addslashes(sprintf($txt['arcade_when'], $score['time'], duration_format($score['duration'])));

		echo '
					<tr class="', $score['own'] ? 'windowbg3' : 'windowbg', '"', !empty($score['highlight']) ? ' style="font-weight: bold;"' : '', ' onmouseover="arcadeBox(\'', $div_con, '\')" onmousemove="arcadeBoxMove(event)" onmouseout="arcadeBox(\'\')">
						<td class="windowbg2" align="center">', $score['position'], '</td>
						<td>', $score['member']['link'], '</td>
						<td width="300" class="windowbg2">';

		if ($score['can_edit'] && empty($score['edit']))
			echo '
							<div id="comment', $score['id'], '" class="floatleft">
								', $score['comment'], '
							</div>
							<div id="edit', $score['id'], '" class="floatleft" style="display: none;">
								<input type="text" id="c', $score['id'], '" value="', $score['raw_comment'], '" style="width: 95%;"  />
								<input type="button" onclick="arcadeCommentEdit(', $score['id'], ', ', $context['game']['id'], ', 1); return false;" name="csave" value="', $txt['arcade_save'], '" />
							</div>
							<a id="editlink', $score['id'], '" onclick="arcadeCommentEdit(', $score['id'], ', ', $context['game']['id'], ', 0); return false;" href="', $scripturl, '?action=arcade;sa=highscore;game=', $context['game']['id'], ';edit;score=', $score['id'], '" class="floatright">', $edit_button, '</a>';
		elseif ($score['can_edit'] && !empty($score['edit']))
		{
			echo '
							<input type="hidden" name="score" value="', $score['id'], '" />
							<input type="text" name="new_comment" id="c', $score['id'], '" value="', $score['raw_comment'], '" style="width: 95%;" />
							<input class="button_submit" type="submit" name="csave" value="', $txt['arcade_save'], '" />';
		}
		else
			echo $score['comment'];

		echo '
						</td>
						<td align="center">', $score['score'], '</td>';


		if ($context['arcade']['can_admin_arcade'])
			echo '
						<td class="windowbg2" align="center"><input type="checkbox" name="scores[]" value="', $score['id'], '" class="check" /></td>';

		echo '
					</tr>';
	}
	
	echo '
			</tbody>';

	if ($context['arcade']['can_admin_arcade'])
	{
		echo '
			<tfoot>
				<tr>
					<td colspan="', $context['arcade']['can_admin_arcade'] ? '6' : '5', '" align="right">
						<select name="qaction">
							<option value="">--------</option>
							<option value="delete">', $txt['arcade_delete_selected'], '</option>
						</select>
						<input value="', $txt['go'], '" onclick="return document.forms.score.qaction.value != \'\' && confirm(\'', $txt['arcade_are_you_sure'], '\');" class="button_submit" type="submit" />
					</td>
				</tr>
			</tfoot>';
	}

	echo '
			</table>
		</div>
	</form>';
}

// Below game
function template_arcade_game_below()
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<div class="pagesection">
		<div class="align_left">';
		
	if (isset($context['page_index']))
		echo $txt['pages'], ': ', $context['page_index'];
	
	if (!empty($modSettings['topbottomEnable']))
		echo isset($context['page_index']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '';
	
	echo '</div>
		', template_button_strip($context['arcade']['buttons'], 'right'), '
	</div>
	<div class="plainbox" id="arcadebox" style="display: none; position: fixed; left: 0px; top: 0px; width: 33%;">
		<div id="arcadebox_html" style=""></div>
	</div>';
}

?>