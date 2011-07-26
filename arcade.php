<?php
/**
 * SMF Arcade
 *
 * @package SMF Arcade
 * @version 2.5
 * @license http://download.smfarcade.info/license.php New-BSD
 */


if (!isset($_REQUEST['sessdo']))
	die('Hacking attempt...');

$_POST['action'] = 'arcade';
if (isset($_REQUEST['gamename']))
	$_POST['game'] = $_REQUEST['gamename'];
$_POST['v3arcade'] = true;

if ($_REQUEST['sessdo'] == 'sessionstart')
	$_POST['sa'] = 'vbSessionStart';
elseif ($_REQUEST['sessdo'] == 'permrequest')
	$_POST['sa'] = 'vbPermRequest';
elseif ($_REQUEST['sessdo'] == 'burn')
	$_POST['sa'] = 'vbBurn';
else
	die('Hacking attempt...');

require_once(dirname(__FILE__) . '/index.php');

?>