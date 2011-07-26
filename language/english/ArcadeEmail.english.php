<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */

$txt['emails'] = !isset($txt['emails']) ? array() : $txt['emails'];

$txt['emails']['notification_arcade_new_champion_own'] = array(
		/*
			@additional_params: notification_arcade_new_champion_own
			@description:
		*/
		'subject' => 'You are no longer champion of {GAMENAME}',
		'body' => 'You are no longer champion of {GAMENAME},

{champion.name} has beaten your score and is new champion!
To reclaim title, play game and get a score better than {champion.score}.

If you want to, You can disable this notification from
{ARCADE_SETTINGS_URL}

{REGARDS}'
);

$txt['emails']['notification_arcade_new_champion_any'] = array(
		/*
			@additional_params: notification_arcade_new_champion_any
			@description:
		*/
		'subject' => '{old_champion.name} is no longer champion of {GAMENAME}',
		'body' => '{old_champion.name} is no longer champion of {GAMENAME},
{champion.name} has beaten {old_champion.name}\'s score and is a new champion!

If you want to, You can disable this notification from
{ARCADE_SETTINGS_URL}

{REGARDS}'
);

$txt['emails']['notification_arcade_arena_invite'] = array(
		/*
			@additional_params: notification_arcade_arena_invite
			@description:
		*/
		'subject' => 'You are invited to join a match',
		'body' => 'You have been invited to join match "{MATCHNAME}" on Arcade Arena.
To accept or decline this offer, visit match\'s page in url below:
{MATCHURL}

If you want to, You can disable this notification from
{ARCADE_SETTINGS_URL}

{REGARDS}'
);

$txt['emails']['notification_arcade_arena_new_round'] = array(
		/*
			@additional_params: notification_arcade_arena_new_round
			@description:
		*/
		'subject' => '{MATCHNAME}: New Round begins',
		'body' => 'New Round has begun on match "{MATCHNAME}".
Visit following url to play:
{MATCHURL}

If you want to, You can disable this notification from
{ARCADE_SETTINGS_URL}

{REGARDS}'
);

$txt['emails']['notification_arcade_arena_match_end'] = array(
		/*
			@additional_params: notification_arcade_arena_match_end
			@description:
		*/
		'subject' => '{MATCHNAME}: Finished',
		'body' => 'Match "{MATCHNAME}" has been finished.
Visit following url to see results:
{MATCHURL}

If you want to, You can disable this notification from
{ARCADE_SETTINGS_URL}

{REGARDS}'
);

?>