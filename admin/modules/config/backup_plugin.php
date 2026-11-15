<?php
/**
 * Backup Plugin bridge file.
 *
 * MyBB loads this file for module=config-backup_plugin. The backup_plugin_admin_load
 * hook renders the page and exits, so this file only has to exist.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}