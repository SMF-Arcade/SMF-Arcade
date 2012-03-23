<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/* Contains information about database tables

	void doTables()
		- ???

	void doSettings()
		- ???

	void doPermission()
		- ???

	void installDefaultData()
		- ???
*/

global $addSettings, $tables, $permissions, $columnRename, $boarddir, $boardurl, $smcFunc;

$arcade_version = '2.6';
$arcade_lang_version = '2.6';

// Settings array
$addSettings = array(
	'gamesPerPage' => array(25, false),
	'matchesPerPage' => array(25, false),
	'scoresPerPage' => array(50, false),
	'gamesDirectory' => array(str_replace('\\', '/', $boarddir . '/Games'), false),
    'arcadeDBUpdate' => array(0, true),
	'gamesUrl' => array($boardurl . '/Games', false),
	'arcadeEnabled' => array(true, false),
	'arcadeArenaEnabled' => array(false, false),
	'arcadeCheckLevel' => array(1, false),
	'arcadeGamecacheUpdate' => array(1, false),
	'arcadeMaxScores' => array(0, false),
	'arcadePermissionMode' => array(1, false),
	'arcadePostPermission' => array(0, false),
	'arcadePostsPlay' => array(0, false),
	'arcadePostsPlayPerDay' => array(0, false),
	'arcadePostsPlayAverage' => array(0, false),
	'arcadeEnableFavorites' => array(1, false),
	'arcadeEnableRatings' => array(1, false),
	'arcadeShowInfoCenter' => array(1, false),
	'arcadeCommentLen' => array(75, false),
);

// Permissions array
$permissions = array(
	'arcade_view' => array(-1, 0, 2), // Everyone
	'arcade_play' => array(-1, 0, 2), // Everyone
	'arcade_submit' => array(0, 2), // Regular members
	'arcade_admin' => array(), // Only admins will get this
	'arcade_comment_own' => array(0, 2), // Regular members
	'arcade_comment_any' => array(), // Only admins
	'arcade_user_stats_own' => array(0, 2),
	'arcade_user_stats_any' => array(0, 2),
	'arcade_view_arena' => array(-1, 0, 2),
	'arcade_create_match' => array(0, 2),
	'arcade_join_match' => array(0, 2),
	'arcade_join_invite_match' => array(0, 2),
	'arcade_edit_settings_own' => array(0, 2),
	'arcade_edit_settings_any' => array(),
);

// SMF 2.0 Uses lowercase names because of other database systems,
// so we change them
$columnRename = array(
	'ID_GAME' => 'id_game',
	'internalName' => 'internal_name',
	'gameName' => 'game_name',
	'thumbnailSmall' => 'thumbnail_small',
	'ID_CAT' => 'id_cat',
	'gameWidth' => 'game_width',
	'gameHeight' => 'game_height',
	'gameFile' => 'game_file',
	'gameDirectory' => 'game_directory',
	'gameType' => 'submit_system',
	'game_type' => 'submit_system',
	'scoreType' => 'score_type',
	'memberGroups' => 'member_groups',
	'gameBackgroundColor' => 'background_color',
    'game_backgroundcolor' => 'background_color',
	'gameRating' => 'game_rating',
	'ID_MEMBER_CHAMPION' => 'id_champion',
	'ID_CHAMPION_SCORE' => 'id_champion_score',
	'gameExtraData' => 'extra_data',
    'game_extra_data' => 'extra_data',
	// 'extra_data' => 'game_settings',
	'numPlays' => 'num_plays',
	'numRates' => 'num_rates',
	'maxScores' => 'max_scores',
	'ID_SCORE' => 'id_score',
	'ID_MEMBER'  => 'id_member',
	'endTime'  => 'end_time',
	'duration'  => 'duration',
	'championFrom'  => 'champion_from',
	'championTo'  => 'champion_to',
	'personalBest'  => 'personal_best',
	'memberIp'  => 'member_ip',
	'playerName' => 'player_name',
	'scoreStatus'  => 'score_status',
	'catName' => 'cat_name',
	'numGames' => 'num_games',
	'catOrder' => 'cat_order',
	'ID_FAVORITE' => 'id_favorite',
	'rateTime' => 'rate_time',
	'ID_RATE' => 'id_rate'
);

// Games table
$tables = array(
	'arcade_games' => array(
		'name' => 'arcade_games',
		'columns' => array(
			array(
				'name' => 'id_game',
				'type' => 'int',
				'unsigned' => true,
				'auto' => true
			),
			array(
				'name' => 'internal_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'game_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'class',
				'type' => 'varchar',
				'default' => '',
				'size' => 50,
			),			
			array(
				'name' => 'game_settings',
				'type' => 'text',
			),
			array(
				'name' => 'game_file',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'game_directory',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'description',
				'type' => 'text',
			),
			array(
				'name' => 'help',
				'type' => 'text',
			),
			array(
				'name' => 'thumbnail',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'thumbnail_small',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'id_cat',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'enabled',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'local_permissions',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'score_type',
				'type' => 'tinyint',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'member_groups',
				'type' => 'varchar',
				'default' => '-2,-1,0,2',
				'size' => 255,
			),
			array(
				'name' => 'game_rating',
				'type' => 'float',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'id_champion',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'id_champion_score',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'num_plays',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'num_rates',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'num_favorites',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_game')
			),
			array(
				'name' => 'internal_name',
				'type' => 'unique',
				'columns' => array('internal_name')
			),
		),
		// Data for upgrade to drop extra columns/indexes
		'upgrade' => array(
			'indexes' => array(
				array(
					'name' => 'game_file',
					'type' => 'unique',
					'columns' => array('game_directory', 'game_file')
				),
				array(
					'name' => 'game_file',
					'type' => 'unique',
					'columns' => array('game_file')
				),
			),
		),
	),
	'arcade_scores' => array(
		'name' => 'arcade_scores',
		'columns' => array(
			array(
				'name' => 'id_score',
				'type' => 'int',
				'unsigned' => true,
				'auto' => true
			),
			array(
				'name' => 'id_game',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'score',
				'type' => 'float',
			),
			array(
				'name' => 'duration',
				'type' => 'float',
				'unsigned' => true,
			),
			array(
				'name' => 'end_time',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'champion_from',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'champion_to',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'position',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'personal_best',
				'type' => 'tinyint',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'score_status',
				'type' => 'varchar',
				'default' => '',
				'size' => 30,
			),
			array(
				'name' => 'member_ip',
				'type' => 'varchar',
				'default' => '',
				'size' => 15,
			),
			array(
				'name' => 'player_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'comment',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'validate_hash',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_score')
			),
			array(
				'name' => 'id_game',
				'type' => 'index',
				'columns' => array('id_game')
			),
			array(
				'name' => 'personal_best',
				'type' => 'index',
				'columns' => array('id_member', 'personal_best')
			),
		)
	),
	// Categories
	'arcade_categories' => array(
		'name' => 'arcade_categories',
		'columns' => array(
			array(
				'name' => 'id_cat',
				'type' => 'int',
				'unsigned' => true,
				'auto' => true
			),
			array(
				'name' => 'cat_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 20,
			),
			array(
				'name' => 'num_games',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'cat_order',
				'type' => 'int',
				'default' => 1,
				'unsigned' => true,
			),
			array(
				'name' => 'special',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'member_groups',
				'type' => 'varchar',
				'default' => '-2,-1,0,2',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_cat')
			)
		)
	),
	// Favorites
	'arcade_favorite' => array(
		'name' => 'arcade_favorite',
		'columns' => array(
			array(
				'name' => 'id_favorite',
				'type' => 'int',
				'unsigned' => true,
				'auto' => true
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_game',
				'type' => 'int',
				'unsigned' => true,
			)
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_favorite')
			),
			array(
				'name' => 'id_game',
				'type' => 'index',
				'columns' => array('id_game', 'id_member')
			)
		)
	),
	// Matchers
	'arcade_matches' => array(
		'name' => 'arcade_matches',
		'columns' => array(
			array(
				'name' => 'id_match',
				'type' => 'int',
				'unsigned' => true,
				'auto' => true,
			),
			array(
				'name' => 'name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'private_game',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'created',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'updated',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'num_players',
				'type' => 'int',
				'default' => 2,
				'unsigned' => true,
			),
			array(
				'name' => 'current_players',
				'type' => 'int',
				'default' => 1,
				'unsigned' => true,
			),
			array(
				'name' => 'num_rounds',
				'type' => 'int',
				'default' => 1,
				'unsigned' => true,
			),
			array(
				'name' => 'current_round',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'match_data',
				'type' => 'text',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_match')
			),
		)
	),
	// Match players
	'arcade_matches_players' => array(
		'name' => 'arcade_matches_players',
		'columns' => array(
			array(
				'name' => 'id_match',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'score',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'player_data',
				'type' => 'text',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_match', 'id_member')
			),
		)
	),
	// Match Rounds
	'arcade_matches_rounds' => array(
		'name' => 'arcade_matches_rounds',
		'columns' => array(
			array(
				'name' => 'id_match',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'round',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'id_game',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_match', 'round')
			),
		)
	),
	// Match results
	'arcade_matches_results' => array(
		'name' => 'arcade_matches_results',
		'columns' => array(
			array(
				'name' => 'id_match',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'round',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'score',
				'type' => 'float',
				'default' => 0,
			),
			array(
				'name' => 'duration',
				'type' => 'float',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'end_time',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'score_status',
				'type' => 'varchar',
				'default' => '',
				'size' => 30,
			),
			array(
				'name' => 'validate_hash',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_match', 'id_member', 'round')
			),
		)
	),
	// Rates
	'arcade_rates' => array(
		'name' => 'arcade_rates',
		'columns' => array(
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_game',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'rating',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'rate_time',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			)
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_game', 'id_member')
			),
			array(
				'name' => 'id_game',
				'type' => 'index',
				'columns' => array('id_game')
			)
		),
		'upgrade' => array(
			'columns' => array(
				'id_rate' => 'drop',
				'ID_RATE' => 'drop',
			),
		),
	),
	// Settings
	'arcade_settings' => array(
		'name' => 'arcade_settings',
		'columns' => array(
			array(
				'name' => 'id_member',
				'type' => 'int',
			),
			array(
				'name' => 'variable',
				'type' => 'varchar',
				'size' => 30,
			),
			array(
				'name' => 'value',
				'type' => 'text',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_member', 'variable')
			),
			array(
				'name' => 'id_member',
				'type' => 'index',
				'columns' => array('id_member')
			),
		)
	),
	// Game info database
	'arcade_game_info' => array(
		'name' => 'arcade_game_info',
		'columns' => array(
			array(
				'name' => 'internal_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'game_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'description',
				'type' => 'text',
			),
			array(
				'name' => 'info_url',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'download_url',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('internal_name')
			)
		),
		'upgrade' => array(
			'columns' => array(
				'submit_system' => 'drop',
			),
		)
	),
	// File cache
	'arcade_files' => array(
		'name' => 'arcade_files',
		'columns' => array(
			array(
				'name' => 'id_file',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_game',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'file_type',
				'type' => 'varchar',
				'default' => 'game',
				'size' => 30,
			),
			array(
				'name' => 'game_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'game_file',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'game_directory',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_file')
			),
		),
		'upgrade' => array(
			'columns' => array(
				'internal_name' => 'drop',
			),
		),
	)
);

?>