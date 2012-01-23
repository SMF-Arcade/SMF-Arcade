<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

function template_arcade_statistics()
{
	global $scripturl, $txt, $context, $settings;

	echo '
	<div id="statistics" class="main_section">
		<div class="cat_bar">
			<h3 class="catbg">', $txt['arcade_stats'], '</h3>
		</div>
	</div>';
	
	$alternate = false;

	// Most played games
	if (!empty($context['arcade']['statistics']['play']) > 0)
	{
		echo '
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 49.5%;">
		<div class="title_bar">
			<h3 class="titlebg">
				<span class="ie6_header floatleft">
					<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />', $txt['arcade_most_played'], '
				</span>
			</h3>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="stats">';

		foreach ($context['arcade']['statistics']['play'] as $game)
		{					
			echo '
					<dt>
						', $game['link'], '
					</dt>
					<dd class="statsbar">';

			if (!empty($game['precent']))
				echo '
						<span class="left"></span>
							<div style="width: ', $game['precent'], 'px;" class="stats_bar"></div>
						<span class="right"></span>';

			echo '
						<span class="righttext">' . $game['plays'] . '</span>
					</dd>';
		}


		echo '
				</dl>
				<br class="clear" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';
		
		$alternate = !$alternate;
		
		if (!$alternate)
			echo '
		<div class="clear"></div>';
	}

	// Most active in arcade
	if (!empty($context['arcade']['statistics']['active']))
	{
		echo '
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 49.5%;">
		<div class="title_bar">
			<h4 class="titlebg">
				<span class="ie6_header floatleft">
					<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />', $txt['arcade_most_active'], '
				</span>
			</h4>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="stats">';
				
	
		foreach ($context['arcade']['statistics']['active'] as $game)
		{
			echo '
					<dt>
						', $game['link'], '
					</dt>
					<dd class="statsbar">';

			if (!empty($game['precent']))
				echo '
						<span class="left"></span>
							<div style="width: ', $game['precent'], 'px;" class="stats_bar"></div>
						<span class="right"></span>';

			echo '
						<span class="righttext">' . $game['scores'] . '</span>
					</dd>';
		}

		echo '
				</dl>
				<br class="clear" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';

		
		$alternate = !$alternate;
		
		if (!$alternate)
			echo '
		<div class="clear"></div>';
	}

	// Top rated games
	if (!empty($context['arcade']['statistics']['rating']))
	{
		echo '
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 49.5%;">
		<div class="title_bar">
			<h4 class="titlebg">
				<span class="ie6_header floatleft">
					<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />', $txt['arcade_best_games'], '
				</span>
			</h4>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="stats">';

		foreach ($context['arcade']['statistics']['rating'] as $game)
		{				
			echo '
					<dt>
						', $game['link'], '
					</dt>
					<dd class="statsbar">';

			if (!empty($game['precent']))
				echo '
						<span class="left"></span>
							<div style="width: ', $game['precent'], 'px;" class="stats_bar"></div>
						<span class="right"></span>';

			echo '
						<span class="righttext">' . $game['rating'] . '</span>
					</dd>';
		}

		echo '
				</dl>
				<br class="clear" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';
		
		$alternate = !$alternate;
		
		if (!$alternate)
			echo '
		<div class="clear"></div>';
	}

	// Best players by champions
	if (!empty($context['arcade']['statistics']['champions']))
	{
		echo '
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 49.5%;">
		<div class="title_bar">
			<h4 class="titlebg">
				<span class="ie6_header floatleft">
					<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />', $txt['arcade_best_players'], '
				</span>
			</h4>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="stats">';

		foreach ($context['arcade']['statistics']['champions'] as $member)
		{
			echo '
					<dt>
						', $member['link'], '
					</dt>
					<dd class="statsbar">';

			if (!empty($member['precent']))
				echo '
						<span class="left"></span>
							<div style="width: ', $member['precent'], 'px;" class="stats_bar"></div>
						<span class="right"></span>';

			echo '
						<span class="righttext">' . $member['champions'] . '</span>
					</dd>';
		}

		echo '
				</dl>
				<br class="clear" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';

		$alternate = !$alternate;
		
		if (!$alternate)
			echo '
		<div class="clear"></div>';
	}

	if (!empty($context['arcade']['statistics']['longest']))
	{
		echo '
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 49.5%;">
		<div class="title_bar">
			<h4 class="titlebg">
				<span class="ie6_header floatleft">
					<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />', $txt['arcade_longest_champions'], '
				</span>
			</h4>
		</div>
		<div class="windowbg2">
			<span class="topslice"><span></span></span>
			<div class="content">
				<dl class="stats">';

		foreach ($context['arcade']['statistics']['longest'] as $game)
		{
			echo '
					<dt>
						', $game['member_link'], ' (', $game['game_link'], ')
					</dt>
					<dd class="statsbar">';

			if (!empty($game['precent']))
				echo '
						<span class="left"></span>
							<div style="width: ', $game['precent'], 'px;" class="stats_bar"></div>
						<span class="right"></span>';

			echo '
						<span class="righttext">', $game['current'] ? '<strong>' . $game['duration'] . '</strong>' : $game['duration'], '</span>
					</dd>';
		}

		echo '
				</dl>
				<br class="clear" />
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';
		
		$alternate = !$alternate;
		
		if (!$alternate)
			echo '
		<br class="clear" />';
	}
	
	if ($alternate)
			echo '
		<br class="clear" />';
}

?>