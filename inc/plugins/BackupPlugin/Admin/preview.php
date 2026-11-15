<?php
/**
 * Backup Plugin scan page.
 *
 * Reached from the Backup link on the plugin list. Shows what the advanced scan
 * found and what it left out before a package is written.
 */

if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

global $mybb, $lang, $page;

$lang->load('backup_plugin');

$codename = backup_plugin_normalize_codename($mybb->get_input('plugin'));

if($codename === '')
{
    flash_message($lang->backup_plugin_error_noplugin, 'error');
    admin_redirect('index.php?module=config-plugins');
}

if(!backup_plugin_admin_can('backup'))
{
    backup_plugin_deny();
}

if(!file_exists(MYBB_ROOT.'inc/plugins/'.$codename.'.php'))
{
    flash_message($lang->backup_plugin_error_invalid, 'error');
    admin_redirect('index.php?module=config-plugins');
}

// Large plugins can hold thousands of files, the scan must not time out.
if(function_exists('my_set_time_limit'))
{
    my_set_time_limit();
}

$backup_plugin_scan = backup_plugin_scan($codename);

// Files the administrator ticked in the skipped table are moved into the
// package before the page is rendered again.
$backup_plugin_selected = $mybb->get_input('selected', MyBB::INPUT_ARRAY);

if(!is_array($backup_plugin_selected))
{
    $backup_plugin_selected = array();
}

if(!empty($backup_plugin_selected))
{
    backup_plugin_merge_selected($backup_plugin_scan, $backup_plugin_selected);
}

if(empty($backup_plugin_scan['files']))
{
    $lang->backup_plugin_error_nofiles = $lang->sprintf($lang->backup_plugin_error_nofiles, $backup_plugin_scan['plugin_name']);
    flash_message($lang->backup_plugin_error_nofiles, 'error');
    admin_redirect('index.php?module=config-plugins');
}

backup_plugin_output_preview($backup_plugin_scan, $codename, $backup_plugin_selected);