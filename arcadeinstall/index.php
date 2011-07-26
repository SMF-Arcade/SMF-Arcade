<?php

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(dirname(__FILE__)) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(dirname(__FILE__)) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please upload ptinstall directory to SMF directory.');
// Make sure we have access to install packages
if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');

// Temporary
die('Installing database via SSI isn\'t supported due to bugs!!');

echo 'Project Tools !';

?>