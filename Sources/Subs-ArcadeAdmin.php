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

/*
	int createGame()
		- ???

	boolean deleteGame()
		- ???

	array installGames()
		- ???

	array uninstallGames()
		- ???

	string getGameName()
		- ???

	string getInternalName()
		- ???

	array isGame()
		- ???

	void moveGames()
		- ???

	array readGameInfo()
		- ???

	boolean updateCategoryStats()
		- ???

	void updateGameCache()
		- ???

	array arcadeGetGroups()
		- ???
		
	void copy_arcade_directory()
		- ???	
	
	void return_bytes()
		- ???
	
	void deleteArcadeArchives()
		- ???
		
	void deleteArcadeFile()
		- ???
		
	void similar_arcade_file_exists()
		- ???						
*/

function list_getNumGamesInstalled($filter)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}arcade_files AS f
			INNER JOIN {db_prefix}arcade_games AS g ON (g.id_game = f.id_game)
		WHERE (status = 1 OR status = 2)' . ($filter == 'disabled' || $filter == 'enabled' ? '
			AND g.enabled = {int:enabled}' : ''),
		array(
			'enabled' => $filter == 'disabled' ? 0 : 1,
		)
	);

	list ($count) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $count;
}

function list_getGamesInstalled($start, $items_per_page, $sort, $filter)
{
	global $smcFunc, $scripturl, $context, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT f.id_file, g.game_name, g.internal_name, f.status, g.id_game, cat.id_cat, cat.cat_name
		FROM {db_prefix}arcade_files AS f
			INNER JOIN {db_prefix}arcade_games AS g ON (g.id_game = f.id_game)
			LEFT JOIN {db_prefix}arcade_categories AS cat ON (cat.id_cat = g.id_cat)
		WHERE (f.status = 1 OR f.status = 2)' . ($filter == 'disabled' || $filter == 'enabled' ? '
			AND g.enabled = {int:enabled}' : '') . '
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:games_per_page}',
		array(
			'start' => $start,
			'games_per_page' => $items_per_page,
			'sort' => $sort,
			'enabled' => $filter == 'disabled' ? 0 : 1,
		)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$return[] = array(
			'id' => $row['id_game'],
			'id_file' => $row['id_file'],
			'name' => $row['game_name'],
			'href' => $scripturl . '?action=admin;area=managegames;sa=edit;game=' . $row['id_game'],
			'category' => array(
				'id' => $row['id_cat'],
				'name' => $row['cat_name'],
			),
			'error' => $row['status'] != 1 ? $txt['arcade_missing_files'] : false,
		);
	$smcFunc['db_free_result']($request);

	return $return;
}

function list_getNumGamesInstall()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}arcade_files AS f
		WHERE status = 10',
		array(
		)
	);

	list ($count) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $count;
}

function list_getGamesInstall($start, $items_per_page, $sort)
{
	global $smcFunc, $scripturl, $context, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT f.id_file, f.game_name, f.status
		FROM {db_prefix}arcade_files AS f
		WHERE status = 10
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:games_per_page}',
		array(
			'start' => $start,
			'games_per_page' => $items_per_page,
			'sort' => $sort,
		)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$return[] = array(
			'id_file' => $row['id_file'],
			'name' => $row['game_name'],
			'href' => $scripturl . '?action=admin;area=managegames;sa=install2;file=' . $row['id_file'],
		);
	$smcFunc['db_free_result']($request);

	return $return;
}

// Creates new game. Returns false on error and id of game on success
function createGame($game)
{
	global $scripturl, $txt, $db_prefix, $user_info, $smcFunc, $modSettings;

	$smcFunc['db_insert']('ignore',
		'{db_prefix}arcade_games',
		array(
			'id_cat' => 'int',
			'internal_name' => 'string',
			'game_name' => 'string',
			'class' => 'string',
			'description' => 'string',
			'help' => 'string',
			'enabled' => 'int',
			'num_rates' => 'int',
			'num_plays' => 'int',
			'game_file' => 'string',
			'game_directory' => 'string',
			'game_settings' => 'string',
		),
		array(
			0,
			$game['internal_name'],
			$game['name'],
			$game['class'],
			'',
			'',
			1,
			0,
			0,
			$game['game_file'],
			$game['game_directory'],
			'',
		),
		array()
	);

	$id_game = $smcFunc['db_insert_id']('{db_prefix}arcade_games', 'id_game');

	if (empty($id_game))
		return false;
		
	unset($game['internal_name'], $game['name'], $game['class'], $game['game_file'], $game['game_directory']);

	// Update does rest
	updateGame($id_game, $game);

	logAction('arcade_install_game', array('game' => $id_game));

	return $id_game;
}

function deleteGame($id, $remove_files)
{
	global $scripturl, $txt, $db_prefix, $user_info, $smcFunc, $modSettings;

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_scores
		WHERE id_game = {int:game}',
		array(
			'game' => $id,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_favorite
		WHERE id_game = {int:game}',
		array(
			'game' => $id,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_rates
		WHERE id_game = {int:game}',
		array(
			'game' => $id,
		)
	);

	if ($remove_files)
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}arcade_files
			WHERE id_game = {int:game}',
			array(
				'game' => $id,
			)
		);
	else
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_files
			SET id_game = 0, status = 10
			WHERE id_game = {int:game}',
			array(
				'game' => $id,
			)
		);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}arcade_games
		WHERE id_game = {int:game}',
		array(
			'game' => $id,
		)
	);

	logAction('arcade_delete_game', array('game' => $id));

	return true;
}

// Install games by game cache ids
function installGames($games, $move_games = false)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir, $boardurl;

	loadClassFile('Class-Package.php');

	// SWF Reader will be needed
	require_once($sourcedir . '/SWFReader.php');
	$swf = new SWFReader();

	$status = array();	
	$trigger = array();	
	
	$acc = 0;
	$request_file = $smcFunc['db_query']('', '
		SELECT game_file, status
		FROM {db_prefix}arcade_files 
		WHERE status != 10');

	while ($row = $smcFunc['db_fetch_assoc']($request_file))
	{
		$trigger[$acc] = $row['game_file'];	
		$acc++;
	}	
	$smcFunc['db_free_result']($request_file);
	
	$request = $smcFunc['db_query']('', '
		SELECT f.id_file, f.game_name, f.status, f.game_file, f.game_directory
		FROM {db_prefix}arcade_files AS f
		WHERE id_file IN ({array_int:games})
			AND f.status = 10',
		array(
			'games' => $games,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$errors = array();
		$failed = true;
		$moveFail = false;

		$directory = $modSettings['gamesDirectory'] . (!empty($row['game_directory']) ? '/' . $row['game_directory'] : '');	
		$internal_name = getInternalName($row['game_file'], $directory);		
			
		foreach ($trigger as $trig)
		{
			$trig2 = strtolower($row['game_file']);	
			$fileSuffix = array('.gif', '1.gif', '2.gif', '.php', '.ini', '.xap');	
			if ($trig == $row['game_file'] || $trig == $trig2)
			{				
				deleteArcadeArchives($modSettings['gamesDirectory'].'/gamedata/'.$internal_name);
				deleteArcadeArchives($modSettings['gamesDirectory'].'/gamedata/'.substr($row['game_file'], 0 , -4));												
				if ($directory != $modSettings['gamesDirectory'])  				
				{								
					foreach ($fileSuffix as $suffix)
					{															
						deleteArcadeFile($directory.'/'.$internal_name . $suffix);
						$similar = similar_arcade_file_exists($directory.'/'.$internal_name . $suffix);
						if ($similar != ($directory.'/'.$internal_name . $suffix))
							deleteArcadeFile($directory.'/'.$similar);	
					}								
					deleteArcadeFile($directory .'/'. $row['game_file']);	
					$similar = similar_arcade_file_exists($directory.'/'.$row['game_file']);								
					if ($similar != ($directory.'/'.$row['game_file']))
						deleteArcadeFile($directory.'/'.$similar);							
				}
				else
				{																													
					deleteArcadeFile($directory.'/'.$row['game_file']);	
					$similar = similar_arcade_file_exists($directory.'/'.$row['game_file']);								
					if ($similar != ($directory . '/'.$row['game_file']))
						deleteArcadeFile($directory.'/'.$similar);
																						
					foreach ($fileSuffix as $suffix)
					{															
						deleteArcadeFile($directory.'/'.$internal_name.$suffix);
						$similar = similar_arcade_file_exists($directory.'/'.$internal_name.$suffix);
						if ($similar != ($directory.'/'.$internal_name.$suffix))
							deleteArcadeFile($directory.'/'.$similar);	
							
					}								
				}	
				updateGameCache();	
				if (@is_dir($directory) && ($directory != $modSettings['gamesDirectory']))
				{
					$countfile = 0;
					if ($handle = @opendir($directory)) 
					{
						while (false !== ($file = @readdir($handle)))
						{
							if ($file != "." && $file != "..")
      							$countfile++;											
						}
						@closedir($handle);
					}					
 					if ($countfile == 0)
						deleteArcadeArchives($directory);															
				}
				if (empty($status))
					$status = array();
					
				$file_num = (int)$row['id_file'];									
				$status[] = array(
						'id' => $file_num,
						'name' => $txt['directory_make_exists'] . $internal_name,
						'error' => false,
						);
				continue 2; 
			} 						
		}
					
		// Search for thumbnails
		chdir($directory);

		if (file_exists($directory . '/game-info.xml'))
		{
			$gameinfo = readGameInfo($directory . '/game-info.xml');

			if (!isset($gameinfo['id']))
				unset($gameinfo);
		}

		$thumbnail = glob($internal_name . '1.{png,gif,jpg}', GLOB_BRACE);
		if (empty($thumbnail))
			$thumbnail = glob($internal_name . '.{png,gif,jpg}', GLOB_BRACE);
		$thumbnailSmall = glob($internal_name . '2.{png,gif,jpg}', GLOB_BRACE);

		$game = array(
			'id_file' => $row['id_file'],
			'name' => $row['game_name'],
			'class' => '',
			'directory' => $row['game_directory'],
			'file' => $row['game_file'],
			'internal_name' => $internal_name,
			'thumbnail' => isset($thumbnail[0]) ? $thumbnail[0] : '',
			'thumbnail_small' => isset($thumbnailSmall[0]) ? $thumbnailSmall[0] : '',
			'game_settings' => array(
				
			),
		);

		unset($thumbnail, $thumbnailSmall);

		// Get information from flash
		if (substr($row['game_file'], -3) == 'swf')
		{
			
			if (isset($gameinfo['flash']))
			{
				if (!empty($gameinfo['flash']['width']) && is_numeric($gameinfo['flash']['width']))
					$game['game_settings']['width'] = (int) $gameinfo['flash']['width'];
				if (!empty($gameinfo['flash']['height']) && is_numeric($gameinfo['flash']['height']))
					$game['game_settings']['height'] = (int) $gameinfo['flash']['height'];
				if (!empty($gameinfo['flash']['version']) && is_numeric($gameinfo['flash']['version']))
					$game['game_settings']['flash_version'] = (int) $gameinfo['flash']['version'];
				if (!empty($gameinfo['flash']['bgcolor']) && strlen($gameinfo['flash']['bgcolor']) == 6)
				{
					$game['game_settings']['background_color'] = array(
						hexdec(substr($gameinfo['flash']['bgcolor'], 0, 2)),
						hexdec(substr($gameinfo['flash']['bgcolor'], 2, 2)),
						hexdec(substr($gameinfo['flash']['bgcolor'], 4, 2))
					);
				}
			}

			// Do we need to detect at least something?
			if (!isset($game['extra_data']['width']) || !isset($game['extra_data']['height']) || !isset($game['extra_data']['version']) || !isset($game['extra_data']['bgcolor']))
			{
				$swf->open($directory . '/' . $row['game_file']);

				// Add missing values
				if (!$swf->error)
				{
					$game['game_settings'] += array(
						'width' => $swf->header['width'],
						'height' => $swf->header['height'],
						'flash_version' => $swf->header['version'],
						'background_color' => $swf->header['background'],
					);
				}

				$swf->close();
			}
		}

		// Detect submit system
		if (empty($row['class']))
		{
			if (isset($gameinfo))
				$row['class'] = $gameinfo['submit'];
			//elseif (substr($row['game_file'], -3) == 'php')
			//	$row['class'] = 'Arcade_Game_[]';
			elseif (substr($row['game_file'], -3) == 'xap')
				$row['class'] = 'Arcade_Game_silver';
			elseif (file_exists($boarddir . '/arcade/gamedata/' . $internal_name . '/v32game.txt'))
				$row['class'] = 'Arcade_Game_ibp32';
			elseif (file_exists($boarddir . '/arcade/gamedata/' . $internal_name . '/v3game.txt'))
				$row['class'] = 'Arcade_Game_ibp3';
			elseif (file_exists($directory . '/' . $internal_name . '.ini'))
				$row['class'] = 'Arcade_Game_pnflash';
			elseif (file_exists($directory . '/' . $internal_name . '.php'))
			{
				$file = file_get_contents($directory . '/' . $internal_name . '.php');

				if (strpos($file, '$config = array(') !== false)
					$row['class'] = 'Arcade_Game_ibp';
				else
					$row['class'] = 'Arcade_Game_v1game';

				unset($file);
			}
			else
				$row['class'] = 'Arcade_Game_v1game';
		}
		$game['class'] = $row['class'];
		$game['score_type'] = isset($gameinfo) && isset($gameinfo['scoring']) ? (int) $gameinfo['scoring'] : 0;

		if (!empty($gameinfo['thumbnail']))
			$game['thumbnail'] = $gameinfo['thumbnail'];
		if (!empty($gameinfo['thumbnail-small']))
			$game['thumbnail_small'] = $gameinfo['thumbnail-small'];


		$game_directory = $game['directory'];		

		// Move files if necessary
		if ($game_directory != $internal_name && $move_games)
		{
			if (!is_dir($modSettings['gamesDirectory'] . '/' . $internal_name) &&
				!mkdir($modSettings['gamesDirectory'] . '/' . $internal_name, 0777))
			{
				$moveFail = true;
				$game['error'] = array('directory_make_failed', array($modSettings['gamesDirectory'] . '/' . $internal_name));

				continue;
			}

			if (!is_writable($modSettings['gamesDirectory'] . '/' . $internal_name))
				@chmod($modSettings['gamesDirectory'] . '/' . $internal_name, 0777);

			$renames = array(
				$directory . '/' . $game['file'] => $modSettings['gamesDirectory'] . '/' . $internal_name . '/' . $game['file'],
			);

			if (!empty($game['thumbnail']))
				$renames[$directory . '/' . $game['thumbnail']] = $modSettings['gamesDirectory'] . '/' . $internal_name . '/' . $game['thumbnail'];

			if (!empty($game['thumbnail_small']))
				$renames[$directory . '/' . $game['thumbnail_small']] = $modSettings['gamesDirectory'] . '/' . $internal_name . '/' . $game['thumbnail_small'];

			foreach ($renames as $from => $to)
			{
				if (!file_exists($from) && file_exists($to))
					continue;

				if (!rename($from, $to))
				{
					$moveFail = true;

					$game['error'] = array('file_move_failed', array($from, $to));

					continue;
				}
			}

			if (!$moveFail)
			{
				$game_directory = $internal_name;
				$directory = $modSettings['gamesDirectory'] . '/' . $game_directory;
			}
		}		

		if (@file_exists($modSettings['gamesDirectory'] . '/' .$game_directory . '/game-info.xml'))
		{
			$gameinfo = readGameInfo($modSettings['gamesDirectory'] . '/' .$game_directory . '/game-info.xml');
			$game['description'] = !empty($gameinfo['description']) ? $gameinfo['description'] : false;	
		}

		if (empty($game['description']) && @file_exists($modSettings['gamesDirectory'] . '/' .$game_directory .'/'.$game['internal_name'].'.php'))
		{
			@require_once($modSettings['gamesDirectory'] . '/' .$game_directory.'/'.$game['internal_name'].'.php');
			$game_info = array('gtitle', 'gwords', 'gkeys');
			$arcade_info = array('name', 'description', 'help');
			$x = 0;
			foreach ($game_info as $info)
				{
					if (!empty($config[$info]))
						{
							$config[$info] = un_htmlspecialchars($config[$info]);
							$game[$arcade_info[$x]] = $config[$info];
						}
					$x++;		
				}			
		}

		// Final install data
		$gameOptions = array(
			'internal_name' => $game['internal_name'],
			'name' => $game['name'],
			'description' => !empty($game['description']) ? $game['description'] : '',
			'thumbnail' => $game['thumbnail'],
			'thumbnail_small' => $game['thumbnail_small'],
			'help' => !empty($game['help']) ? $game['help'] : '',
			'game_file' => $game['file'],
			'game_directory' => $game_directory,
			'class' => $game['class'],
			'score_type' => $game['score_type'],
			'game_settings' => $game['game_settings'],
		);

		$success = false;
		if (!isset($game['error']) && $id_game = createGame($gameOptions))
			$success = true;
		else
			$game['error'] = array('arcade_install_general_fail', array());

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_files
			SET id_game = {int:game}, status = {int:status}, game_directory = {string:directory}
			WHERE id_file = {int:file}',
			array(
				'game' => empty($success) ? 0 : $id_game,
				'status' => empty($success) ? 10 : 1,
				'file' => $game['id_file'],
				'directory' => $game_directory,
			)
		);

		$status[] = array(
			'id' => $id_game,
			'name' => $game['name'],
			'error' => isset($game['error']) ? $game['error'] : false,
		);
	}
	$smcFunc['db_free_result']($request);

	return $status;
}

function unpackGames($games, $move_games = false)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir;

	if (!is_writable($modSettings['gamesDirectory']) && !chmod($modSettings['gamesDirectory'], 0777))
		fatal_lang_error('arcade_not_writable', false, array($modSettings['gamesDirectory']));

	require_once($sourcedir . '/Subs-Package.php');

	$request = $smcFunc['db_query']('', '
		SELECT f.id_file, f.game_file, f.game_directory
		FROM {db_prefix}arcade_files AS f
		WHERE id_file IN ({array_int:games})
			AND (f.status = 10)',
		array(
			'games' => $games,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$from = $modSettings['gamesDirectory'] . '/' . $row['game_directory'] . '/' . $row['game_file'];
		$target = substr($row['game_file'], 0, strpos($row['game_file'], '.'));				
		$targetb = $target;
		if (substr($target, 0, 5) == 'game_')
		{					
			$target = substr($target, 5);																				
			$target = trim($target);
			if ($target == '')
				continue;					
		}		
		$fromb = (str_replace('game_', '', $from));		
		$i = 1;		
		while (@file_exists($modSettings['gamesDirectory'] . '/' . $target))
			{
			@unlink($from);				
			continue 2;			
			}	
		while (file_exists($modSettings['gamesDirectory'] . '/' . $fromb))
			{			
			@unlink($from);			
			continue 2;				
			}			

		if (substr($row['game_file'] , -3) == 'zip')
		{
			$files = read_tgz_file($from, $modSettings['gamesDirectory'] . '/' . $target);
			$data = gameCacheInsertGames(getAvailableGames($target, 'unpack'), true);
		}
		if(substr( $row['game_file'] , -3) == 'tar')
		{
			@require_once($sourcedir . '/Tar.php');
			$path = $modSettings['gamesDirectory'];
		
			$tar = new Archive_Tar($path.'/'.$row['game_file']);
			$data = $tar->listContent();
			if ($data === false)
			{
                  fatal_lang_error('arcade_file_non_read', false);
			}
			else
			{						
				$folder = $modSettings['gamesDirectory'] . '/' . $target;							
				
				if(!@is_dir($folder))
					@mkdir($folder, 0777);
				
				$tar = new Archive_Tar($path.'/'.$row['game_file']);
				$tar->extract($folder);
			}

		}			
	
		if (unlink($from))
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}arcade_files
				WHERE id_file = {int:file}',
				array(
					'file' => $row['id_file'],
				)
			);
	}
	$smcFunc['db_free_result']($request);

	return true;
}

function uninstallGames($games, $delete_files = false)
{
	global $smcFunc, $modSettings, $sourcedir, $boarddir;

	require_once($sourcedir . '/Subs-Package.php');

	$request = $smcFunc['db_query']('', '
		SELECT id_game, internal_name, game_name, game_file, thumbnail, thumbnail_small, game_directory
		FROM {db_prefix}arcade_games
		WHERE id_game IN({array_int:games})',
		array(
			'games' => $games
		)
	);

	$status = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($delete_files)
		{
			$altdir = strtolower ($row['internal_name']);
			$altdir2 = 'game_' . $row['internal_name'];
			$altdir3 = 'game_' . strtolower ($row['internal_name']);	
			$phpfile = substr($row['game_file'], 0, -4) . '.php';		
			if ($row['game_directory'] == $row['internal_name'])
				deltree($modSettings['gamesDirectory'] . '/' . $row['game_directory'], true);
			elseif ($row['game_directory'] == $altdir)
				 deltree($modSettings['gamesDirectory'] . '/' . $altdir, true);	
			elseif ($row['game_directory'] == $altdir2)
				 deltree($modSettings['gamesDirectory'] . '/' . $altdir2, true);	
			elseif ($row['game_directory'] == $altdir3)
				 deltree($modSettings['gamesDirectory'] . '/' . $altdir3, true);
			else
			{
				$files = array_unique(array($row['game_file'], $phpfile, $row['thumbnail'], $row['thumbnail_small']));

				foreach ($files as $f)
				{
					if (!empty($f) && @file_exists($modSettings['gamesDirectory'] . '/' . $row['game_directory'] . '/' . $f))
						@unlink($modSettings['gamesDirectory'] . '/' . $row['game_directory'] . '/' . $f);
				}
			}

			if (@file_exists($boarddir . '/arcade/gamedata/' . $row['internal_name'] . '/'))
				deltree($boarddir . '/arcade/gamedata/' . $row['internal_name'] . '/', true);				
			
			deleteArcadeArchives($modSettings['gamesDirectory'] . '/gamedata/' . $row['internal_name']);				
		}

		deleteGame($row['id_game'], $delete_files);

		$status[] = array(
			'id' => $row['id_game'],
			'name' => $row['game_name'],
		);
	}

	return $status;
}

function moveGames()
{
	global $db_prefix, $modSettings, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT id_game, internal_name, game_directory, game_file, thumbnail, thumbnail_small
		FROM {db_prefix}arcade_games');

	if (!is_writable($modSettings['gamesDirectory']))
		fatal_lang_error('arcade_not_writable', false, array($modSettings['gamesDirectory']));

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$from = $modSettings['gamesDirectory']  . '/' . (!empty($row['game_directory']) ? $row['game_directory'] . '/' : '');
		$to = $modSettings['gamesDirectory'] . '/' . $row['internal_name'] . '/';

		if ($from == $to)
			continue;

		if ((file_exists($to) && !is_dir($to)) || !mkdir($to))
			fatal_lang_error('arcade_not_writable', false, array($to));

		chdir($from);

		// These should be at least there
		$files = array();
		$files[] = $row['game_file'];
		$files[] = $row['thumbnail'];
		$files[] = $row['thumbnail_small'];

		foreach ($files as $file)
		{
			if (@file_exists($from . $file))
				if (!@rename($from . $row['gameFile'], $to . $file))
					fatal_lang_error('arcade_unable_to_move', false, array($file, $from, $to));
		}

		updateGame($row['id_game'], array('game_directory' => $row['internal_name']));
	}
	$smcFunc['db_free_result']($request);
}

function updateCategoryStats()
{
	global $smcFunc;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}arcade_categories
		SET num_games = {int:num_games}',
		array(
			'num_games' => 0,
		)
	);

	$request = $smcFunc['db_query']('', '
		SELECT id_cat, COUNT(*) as games
		FROM {db_prefix}arcade_games
		GROUP BY id_cat');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}arcade_categories
			SET num_games = {int:num_games}
			WHERE id_cat = {int:category}',
			array(
				'category' => $row['id_cat'],
				'num_games' => $row['games'],
			)
		);
	$smcFunc['db_free_result']($request);

	return true;
}

function readGameInfo($file)
{
	if (!file_exists($file))
		return false;

	$gameinfo = new xmlArray(file_get_contents($file));
	$gameinfo = $gameinfo->path('game-info[0]');
	return $gameinfo->to_array();
}

function getGameName($internal_name)
{
	global $smcFunc;

	$internal_name = str_replace(array('_', '-'), ' ', $internal_name);

	if (strtolower(substr($internal_name, -2)) == 'ch' || strtolower(substr($internal_name, -2)) == 'gc')
		$internal_name = substr($internal_name, 0, strlen($internal_name) - 2);
	elseif (strtolower(substr($internal_name, -2)) == 'v2')
		$internal_name = substr($internal_name, 0, strlen($internal_name) - 2) . ' v2';

	$internal_name = trim($internal_name);

	return ucwords($internal_name);
}

function getInternalName($file, $directory)
{
	if (is_dir($directory . '/' . $file))
	{
		if (file_exists($directory . '/' . $file . '/game-info.xml'))
		{
			$gameinfo = readGameInfo($directory . '/' . $file . '/game-info.xml');
			return $gameinfo['id'];
		}
		else
			return $file;
	}

	$pos = strrpos($file, '.');

	if ($pos === false)
		return $file;

	return substr($file, 0, $pos);
}

function isGame($file, $directory)
{
	// Is single file which is game?
	if (!is_dir($directory . '/' . $file) && substr($file, -3) == 'swf')
		return array(true, $directory, array('file' => $file));
	/*elseif (substr($file, -3) == 'php' && $file != 'index.php')
		return array(true, $directory, array('file' => $file));*/
	// Is game directory?
	elseif (is_dir($directory . '/' . $file))
	{
		if (file_exists($directory . '/' . $file . '/' . $file . '.swf'))
			return array(
				true,
				$directory . '/' . $file,
				array('file' => $file . '.swf')
			);
		elseif (file_exists($directory . '/' . $file . '/' . $file . '.xap'))
			return array(
				true,
				$directory . '/' . $file,
				array('file' => $file . '.xap')
			);
		elseif (file_exists($directory . '/' . $file . '/' . $file . '.php'))
			return array(
				true,
				$directory . '/' . $file,
				array('file' => $file . '.php')
			);
		elseif (file_exists($directory . '/' . $file . '/game-info.xml'))
			return array(
				true,
				$directory . '/' . $file,
				readGameInfo($directory . '/' . $file . '/game-info.xml')
			);
	}

	return array(false, false, false);
}

function getAvailableGames($subpath = '', $recursive = true)
{
	global $modSettings;

	if (substr($subpath, -1) == '/')
		$subpath = substr($subpath, 0, -1);

	$directory = $modSettings['gamesDirectory'] . (!empty($subpath) ? '/' . $subpath : '');

	$games = array();

	if ($subpath != '' && $recursive == 'unpack')
	{
		list ($is_game, $gdir, $extra) = isGame(basename($subpath), dirname($directory));

		if ($is_game)
		{
			$gdir_rel = substr($gdir, strlen($modSettings['gamesDirectory']));

			if (substr($gdir_rel, 0, 1) == '/')
				$gdir_rel = substr($gdir_rel, 1);

			$games[] = array(
				'type' => 'game',
				'directory' => $gdir_rel,
				'filename' => $extra['file'],
			);

			return $games;
		}
	}

	$recursive = (bool) $recursive;

	$directoryHandle = opendir($directory);

	while ($file = readdir($directoryHandle))
	{
		if ($file == '.' || $file == '..')
			continue;

		list ($is_game, $gdir, $extra) = isGame($file, $directory);

		if ($is_game)
		{
			$gdir_rel = substr($gdir, strlen($modSettings['gamesDirectory']));

			if (substr($gdir_rel, 0, 1) == '/')
				$gdir_rel = substr($gdir_rel, 1);

			$games[] = array(
				'type' => 'game',
				'directory' => $gdir_rel,
				'filename' => $extra['file'],
			);
		}
		elseif (!is_dir($directory . '/' . $file))
		{
			if (in_array(substr($file, -3), array('zip', 'tar', '.gz')))
			{
				$gameinfo = read_tgz_data(file_get_contents($directory . '/' . $file), 'game-info.xml', true);

				if ($gameinfo)
				{
					$gameinfo = new xmlArray($gameinfo);
					$gameinfo = $gameinfo->path('game-info[0]');

					$games[] = array(
						'type' => 'gamepackage',
						'directory' => $subpath,
						'filename' => $file,
					);

					unset($gameinfo);
				}
				else
					$games[] = array(
						'type' => 'gamepackage-multi',
						'directory' => $subpath,
						'filename' => $file,
					);
			}
		}
		elseif ($recursive)
			$games = array_merge($games, getAvailableGames((!empty($subpath) ? $subpath . '/' : '') . $file, false));

		unset($is_game, $gdir, $extra);
	}

	return $games;
}

function updateGameCache()
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir;

	// Clear entries
	$smcFunc['db_query']('truncate_table', '
		TRUNCATE {db_prefix}arcade_files'
	);

	require_once($sourcedir . '/Subs-Package.php');
	loadClassFile('Class-Package.php');

	// Try to get more memory
	@ini_set('memory_limit', '128M');

	// Do actual update
	gameCacheInsertGames(getAvailableGames());

	updateSettings(array('arcadeDBUpdate' => time()));
}

function gameCacheInsertGames($games, $return = false)
{
	global $scripturl, $txt, $db_prefix, $modSettings, $context, $sourcedir, $smcFunc, $boarddir;

	$filesAvailable = array();
	$filesKeys = array();

	foreach ($games as $id => $game)
	{
		if ($game['type'] == 'game')
		{
			if (!empty($game['directory']))
			{
				$game_directory = $modSettings['gamesDirectory'] . '/' . $game['directory'];
				$game_file = $modSettings['gamesDirectory'] . '/' . $game['directory'] . '/' . $game['filename'];
			}
			else
			{
				$game_directory = $modSettings['gamesDirectory'];
				$game_file = $modSettings['gamesDirectory'] . '/' . $game['filename'];
			}

			$filesAvailable[$game_file] = $id;
			$filesKeys[$game_file] = $id;

			// Move gamedata file for IBP arcade games
			if (file_exists($game_directory . '/gamedata'))
			{
				$from = $game_directory . '/gamedata';
				$to = $boarddir . '/arcade/gamedata';

				if (!file_exists($boarddir . '/arcade/'))
				{
					if (!mkdir($boarddir . '/arcade/', 0755))
						fatal_lang_error('unable_to_make', false, array($boarddir . '/arcade/'));
				}
				if (!file_exists($to))
				{
					if (!mkdir($to, 0755))
						fatal_lang_error('unable_to_make', false, array($to));
				}
				elseif (!is_dir($to))
					fatal_lang_error('unable_to_make', false, array($to));

				if (!is_writable($to))
					fatal_lang_error('unable_to_chmod', false, array($to));

				/*  Changed rename to copy  */
				$dir = opendir($from);
				$success = true;
				while ($fileFrom = readdir($dir))
				{
					if ($fileFrom == '.' || $fileFrom == '..')
						continue;
					copy_arcade_directory($from, $to);	
					deleteArcadeArchives($from);				
					/* $success &= rename($from . '/' . $fileFrom, $to . '/' . $fileFrom); */
				}
				closedir($dir);
				if (!file_exists($to))
					{fatal_lang_error('unable_to_move', false, array($from, $to));} 

				/* if (!$success)
					fatal_lang_error('unable_to_move', false, array($from, $to)); */
			}
		}
	}

	// Installed games
	$request = $smcFunc['db_query']('', '
		SELECT id_game, game_name, internal_name, game_file, game_directory
		FROM {db_prefix}arcade_games'
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!empty($row['game_directory']))
			$game_file = $modSettings['gamesDirectory'] . '/' . $row['game_directory'] . '/' . $row['game_file'];
		else
			$game_file = $modSettings['gamesDirectory'] . '/' . $row['game_file'];

		if (isset($filesKeys[$game_file]))
		{
			$games[$filesKeys[$game_file]] += array(
				'type' => 'game',
				'id' => $row['id_game'],
				'name' => $row['game_name'],
				'directory' => $row['game_directory'],
				'filename' => $row['game_file'],
				'internal_name' => $row['internal_name'],
				'installed' => true,
			);
		}
		else
		{
			$fileKeys[$game_file] = count($games);

			$games[] = array(
				'type' => 'game',
				'id' => $row['id_game'],
				'name' => $row['game_name'],
				'directory' => $row['game_directory'],
				'filename' => $row['game_file'],
				'internal_name' => $row['internal_name'],
				'missing_files' => true,
			);
		}
	}
	$smcFunc['db_free_result']($request);

	$rows = array();

	// Last step
	foreach ($games as $id => $game)
	{
		if (!empty($game['directory']))
		{
			$game_directory = $modSettings['gamesDirectory'] . '/' . $game['directory'];
			$game_file = $modSettings['gamesDirectory'] . '/' . $game['directory'] . '/' . $game['filename'];
		}
		else
		{
			$game_directory = $modSettings['gamesDirectory'];
			$game_file = $modSettings['gamesDirectory'] . '/' . $game['filename'];
		}

		// Regular game?
		if ($game['type'] == 'game')
		{
			// Use game info if possible
			if (file_exists($game_directory . '/game-info.xml') && !isset($game['gameinfo']))
				$gameinfo = readGameInfo($game_directory . '/game-info.xml');
		}
		// Single zipped game?
		elseif ($game['type'] == 'gamepackage')
		{
			$gameinfo = read_tgz_data(file_get_contents($game_file), 'game-info.xml', true);
			$gameinfo = new xmlArray($gameinfo);
			$gameinfo = $gameinfo->path('game-info[0]');
			$gameinfo = $gameinfo->to_array();
		}
		// Gamepackage
		elseif ($game['type'] == 'gamepackage-multi')
			$game['name'] = 'GamePack ' . substr($game['filename'], 0, strrpos($game['filename'], '.'));

		if (isset($gameinfo) && !isset($game['name']))
			$game['name'] = $gameinfo['name'];
		elseif (!isset($game['name']))
			$game['name'] = getGameName(getInternalName($game['filename'], $game_directory));

		// Status of game
		$status = 10;

		if (!empty($game['missing_files']))
			$status = 2;
		elseif (!empty($game['installed']))
			$status = 1;

		if (!isset($game['id']))
			$game['id'] = 0;

		if (!isset($game['directory']))
			$game['directory'] = '';

		if (!isset($game['filename']))
			$game['filename'] = '';

		// Escape data to be inserted into database
		$rows[] = array(
			$game['id'],
			$game['type'],
			$game['name'],
			$status,
			$game['filename'],
			$game['directory'],
		);

		unset($gameinfo);
	}

	if (!empty($rows))
		$smcFunc['db_insert']('insert',
			'{db_prefix}arcade_files',
			array(
				'id_game' => 'int',
				'file_type' => 'string-30',
				'game_name' => 'string-255',
				'status' => 'int',
				'game_file' => 'string-255',
				'game_directory' => 'string-255',
			),
			$rows,
			array('internal_name')
		);

	if ($return)
		return $rows;
}

function arcadeGetGroups($selected = array())
{
	global $smcFunc, $txt, $sourcedir;

	require_once($sourcedir . '/Subs-Members.php');

	$return = array();

	// Default membergroups.
	$return = array(
		-2 => array(
			'id' => '-2',
			'name' => $txt['arcade_group_arena'],
			'checked' => $selected == 'all' || in_array('-2', $selected),
			'is_post_group' => false,
		),
		-1 => array(
			'id' => '-1',
			'name' => $txt['guests'],
			'checked' => $selected == 'all' || in_array('-1', $selected),
			'is_post_group' => false,
		),
		0 => array(
			'id' => '0',
			'name' => $txt['regular_members'],
			'checked' => $selected == 'all' || in_array('0', $selected),
			'is_post_group' => false,
		)
	);

	$groups = groupsAllowedTo('arcade_view');

	if (!in_array(-1, $groups['allowed']))
		unset($context['groups'][-1]);
	if (!in_array(0, $groups['allowed']))
		unset($context['groups'][0]);

	// Load membergroups.
	$request = $smcFunc['db_query']('', '
		SELECT mg.group_name, mg.id_group, mg.min_posts
		FROM {db_prefix}membergroups AS mg
		WHERE mg.id_group > 3 OR mg.id_group = 2
			AND mg.id_group IN({array_int:groups})
		ORDER BY mg.min_posts, mg.id_group != 2, mg.group_name',
		array(
			'groups' => $groups['allowed'],
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$return[(int) $row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'checked' => $selected == 'all' ||  in_array($row['id_group'], $selected),
			'is_post_group' => $row['min_posts'] != -1,
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

/* Copy entire folder/directory  */
function copy_arcade_directory($source, $destination) 
{
	if (@is_dir($source)) 
	{	
		if (@!is_dir($destination))
			@mkdir($destination);
		$directory = @dir($source);
		while (FALSE !== ($readdirectory = $directory->read())) 
		{
			if ($readdirectory == '.' || $readdirectory == '..') 
			{continue;}
			$PathDir = $source . '/' . $readdirectory; 
			if (@is_dir($PathDir)) 
			{
				copy_arcade_directory($PathDir, $destination . '/' . $readdirectory);
				continue;
			}
			copy($PathDir, $destination . '/' . $readdirectory);
		}
 
		$directory->close();
	}
	elseif (@file_exists($source) && !@file_exists($destination)) 
		@copy($source, $destination);
	else
		return false;
	
	return true;	
}

function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val{strlen($val) - 1});
    switch($last)
	{
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/* Delete archives function */
function deleteArcadeArchives($directory)
{
	global $boardurl, $boarddir;
	if(substr($directory,-1) == "/") {$directory = substr($directory,0,-1);}
	if (!@file_exists($directory) || !@is_dir($directory)) {return false;}
	elseif (!@is_readable($directory)) {return false;}	
	$directoryHandle = opendir($directory);	
	while ($contents = @readdir($directoryHandle))
		{			
			if($contents != '.' && $contents != '..') 
				{
					$path = $directory . "/" . $contents;									
					if (@is_dir($path))
						{deleteArcadeArchives($path);}
					@unlink($path);
                
				}
		}
       
	@closedir($directoryHandle);
	if (@is_dir($directory)) {@rmdir($directory);}
	elseif (@file_exists($directory)) {@unlink($directory);}
	else {return false;}	
	return true;    
} 

/* Delete Files Function  */
function deleteArcadeFile($filepath)
{
	if (@file_exists($filepath))
		{
			@unlink($filepath);
			return true;
		}
	return false;		
}

/* File exists - ignore case */
function similar_arcade_file_exists($filename)
{
  if (file_exists($filename)) 
    return $filename;
  
  $dir = @dirname($filename);
  $files = @glob($dir . '/*');
  $lcaseFilename = strtolower($filename);
  foreach($files as $file) {
    if (strtolower($file) == $lcaseFilename) {
      return $file;
    }
  }
  return $filename;
}
?>