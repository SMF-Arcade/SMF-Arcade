<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.6 Alpha
 * @license http://download.smfarcade.info/license.php New-BSD
 */

// Provides support for phpbb games
if (!isset($_POST['game_name']))
	die('Hacking attempt...');

$_POST['action'] = 'arcade';
$_POST['sa'] = 'submit';
$_POST['phpbb'] = true;

require_once(dirname(__FILE__) . '/index.php');

?>