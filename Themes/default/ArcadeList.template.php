<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function template_arcade_list()
{
	global $scripturl, $txt, $context, $settings, $user_info, $modSettings;

	$arcade_buttons = array(
		'random' => array(
			'text' => 'arcade_random_game',
			'image' => 'arcade_random.gif', // Theres no image for this included (yet)
			'url' => $scripturl . '?action=arcade;sa=play;random',
			'lang' => true
		),
		'favorites' => array(
			'text' => 'arcade_favorites_only',
			'image' => 'arcade_favorites.gif',
			'url' => $scripturl . '?action=arcade;favorites',
			'lang' => true
		),
	);

	if (isset($context['arcade']['search']) && $context['arcade']['search'])
		$arcade_buttons['search'] = array(
			'text' => 'arcade_show_all',
			'image' => 'arcade_search.gif',
			'url' => $scripturl . '?action=arcade'
		);


	// Header for Game listing
	echo '
		<a id="top"></a>
		<div class="pagesection">
			<div class="pagelinks floatleft">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
			', template_button_strip($arcade_buttons, 'right'), '
		</div>
		<div class="game_table">
			<table cellspacing="0" class="table_grid">
				<thead>
					<tr  class="catbg">';

	// Is there games?
	if (!empty($context['arcade']['games']))
	{
		echo '

						<th scope="col" class="first_th"></th>
						<th scope="col"><a href="', $scripturl, '?action=arcade;sort=name', $context['sort_by'] == 'name' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['arcade_game_name'], $context['sort_by'] == 'name' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a></th>', !$user_info['is_guest'] ? '
						<th scope="col"><a href="' . $scripturl . '?action=arcade;sort=myscore' . ($context['sort_by'] == 'myscore' && $context['sort_direction'] == 'up' ? ';desc' : '') . '">' . $txt['arcade_personal_best'] . ($context['sort_by'] == 'myscore' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '') . '</a></th>' : '', '
						<th scope="col" class="last_th"><a href="', $scripturl, '?action=arcade;sort=champion', $context['sort_by'] == 'champion' && $context['sort_direction'] == 'up' ? ';desc' : '', '">', $txt['arcade_champion'], $context['sort_by'] == 'champion' ? ' <img src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.gif" alt="" />' : '', '</a></th>';
	}
	else
	{
		echo '
						<th scope="col" class="first_th" width="8%">&nbsp;</th>
						<th class="smalltext" colspan="2"><strong>', $txt['arcade_no_games'], '</strong></th>
						<th scope="col" class="last_th" width="8%">&nbsp;</th>';
	}

	echo '
					</tr>
				</thead>
				<tbody>';

	foreach ($context['arcade']['games'] as $game)
	{
		echo '
					<tr>
						<td class="icon windowbg" align="center">', $game['thumbnail'] != '' ? '
							<a href="' . $game['url']['play'] . '"><img src="' . $game['thumbnail'] . '" alt="" /></a>' : '', '
						</td>
						<td class="info windowbg2">
							<span><a href="', $game['url']['play'], '">', $game['name'], '</a></span>';

		// Favorite link (if can favorite)
		if ($context['arcade']['can_favorite'])
			echo '
							<span class="floatright"><a href="', $game['url']['favorite'], '" onclick="arcade_favorite(', $game['id'] , '); return false;">
								', !$game['is_favorite'] ?
								'<img id="favgame' . $game['id'] . '" src="' . $settings['images_url'] . '/favorite.gif" alt="' . $txt['arcade_add_favorites'] . '" />' :
								'<img id="favgame' . $game['id'] . '" src="' . $settings['images_url'] . '/favorite2.gif" alt="' . $txt['arcade_remove_favorite'] .'" />', '
								</a>
							</span>';
		echo '
							<p class="smalltext">
								<span class="game_left">';

		// Is there description?
		if (!empty($game['description']))
			echo '
									', $game['description'], '<br />';

		// Does this game support highscores?
		if ($game['highscore_support'])
			echo '
									<a href="' . $game['url']['highscore'] . '">' . $txt['arcade_viewscore'] . '</a>';

		echo '
								</span>
								<span class="game_right">';

		// Rating
		if ($game['rating2'] > 0)
			echo str_repeat('<img src="' . $settings['images_url'] . '/arcade_star.gif" alt="*" />' , $game['rating2']), str_repeat('<img src="' . $settings['images_url'] . '/arcade_star2.gif" alt="" />' , 5 - $game['rating2']), '<br />';

		// Category
		if (!empty($game['category']['name']))
			echo '
									<a href="', $game['category']['link'], '">', $game['category']['name'], '</a><br />';

		echo '
								</span>
							</p>
						</td>';

		// Show personal best and champion only if game doest support highscores
		if ($game['is_champion'] && !$user_info['is_guest'])
			echo '
						<td class="score windowbg">
							', $game['is_personal_best'] ? $game['personal_best'] :  $txt['arcade_no_scores'], '
						</td>';

		if ($game['is_champion'])
			echo '
						<td class="score windowbg">
							', $game['champion']['member_link'], '<br />', $game['champion']['score'], '
						</td>';

		elseif (!$game['highscore_support'])
			echo '
						<td class="score2 windowbg" colspan="', $user_info['is_guest'] ? '1' : '2', '">', $txt['arcade_no_highscore'], '</td>';
		else
			echo '
						<td class="score2 windowbg" colspan="', $user_info['is_guest'] ? '1' : '2', '">', $txt['arcade_no_scores'], '</td>';

		echo '
					</tr>';
		}


	echo '
				</tbody>
			</table>
		</div>
		<div class="pagesection" style="margin-bottom: 1em">
			<div class="pagelinks">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($arcade_buttons, 'right'), '
		</div>';


	if (!empty($modSettings['arcadeShowInfoCenter']))
	{
		echo '
		<span class="clear upperframe"><span></span></span>
		<div class="roundframe"><div class="innerframe">
			<div class="cat_bar">
				<h3 class="catbg">
					<img class="icon" id="upshrink_arcade_ic" src="', $settings['images_url'], '/collapse.gif" alt="*" title="', $txt['upshrink_description'], '" style="display: none;" />
					', $txt['arcade_info_center'], '
				</h3>
			</div>
			<div id="upshrinkHeaderArcadeIC"', empty($options['collapse_header_arcade_ic']) ? '' : ' style="display: none;"', '>
				<div class="title_barIC">
					<h4 class="titlebg">
						<span class="ie6_header floatleft"> 
						', $txt['arcade_latest_scores'], '
						</span>
					</h4>
				</div>';

		if (!empty($context['arcade']['latest_scores']))
		{
			echo '
				<dl id="ic_recentposts">';

			foreach ($context['arcade']['latest_scores'] as $score)
				echo '
					<dt>', sprintf($txt['arcade_latest_score_item'], $scripturl . '?action=arcade;sa=play;game=' . $score['game_id'], $score['name'], $score['score'], $score['memberLink']), '</dt>
					<dd>',  $score['time'], '</dd>';

			echo '
				</dl>';
		}
		else
			echo '
				<p>', $txt['arcade_no_scores'], '</p>';

		echo '
				<div class="title_barIC">
					<h4 class="titlebg">
						<span class="ie6_header floatleft">
						', $txt['arcade_game_highlights'], '
						</span>
					</h4>
				</div>
				<p>
		';

		if ($context['arcade']['stats']['longest_champion'] !== false)
			echo sprintf($txt['arcade_game_with_longest_champion'], $context['arcade']['stats']['longest_champion']['member_link'], $context['arcade']['stats']['longest_champion']['game_link']), '<br />';

		if ($context['arcade']['stats']['most_played'] !== false)
			echo sprintf($txt['arcade_game_most_played'], $context['arcade']['stats']['most_played']['link']), '<br />';

		if ($context['arcade']['stats']['best_player'] !== false)
			echo sprintf($txt['arcade_game_best_player'], $context['arcade']['stats']['best_player']['link']), '<br />';

		if ($context['arcade']['stats']['games'] != 0)
			echo sprintf($txt['arcade_game_we_have_games'], $context['arcade']['stats']['games']), '<br />';

		echo '
				</p>
				<div class="title_barIC">
					<h4 class="titlebg">
						<span class="ie6_header floatleft">
						', $txt['arcade_users'], '
						</span>
					</h4>
				</div>
				<p class="inline" style="padding: 0.5em">
					', implode(', ', $context['arcade_viewing']), '
				</p>
			</div>
		</div></div>
		<span class="lowerframe"><span></span></span>
		<a id="bot"></a>';
	}
}

?>