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
	<div class="cat_bar">
		<h3 class="catbg">
			<img src="', $settings['images_url'], '/gold.gif" alt="" />
		', $txt['arcade_stats'], '
		</h3>
	</div>';
	
	$alternate = false;

	// Most played games
	if (!empty($context['arcade']['statistics']['play']) > 0)
	{
		echo '
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 48%;">
		<div class="cat_bar">
			<h3 class="catbg">
				<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />
				', $txt['arcade_most_played'], '
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
				<div class="clear"></div>
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
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 48%;">
		<div class="cat_bar">
			<h3 class="catbg">
				<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />
				', $txt['arcade_most_active'], '
			</h3>
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
				<div class="clear"></div>
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
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 48%;">
		<div class="cat_bar">
			<h3 class="catbg">
				<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />
				', $txt['arcade_best_games'], '
			</h3>
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
				<div class="clear"></div>
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
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 48%;">
		<div class="cat_bar">
			<h3 class="catbg">
				<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />
				', $txt['arcade_best_players'], '
			</h3>
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
				<div class="clear"></div>
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
	<div class="', !$alternate ? 'floatleft' : 'floatright', '" style="width: 48%;">
		<div class="cat_bar">
			<h3 class="catbg">
				<img src="', $settings['images_url'], '/gold.gif" class="icon" alt="" />
				', $txt['arcade_longest_champions'], '
			</h3>
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
				<div class="clear"></div>
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';
		
		$alternate = !$alternate;
		
		if (!$alternate)
			echo '
		<div class="clear"></div>';
	}
	
	if ($alternate)
			echo '
		<div class="clear"></div>';
}

?>