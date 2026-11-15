<?php
if(!defined('IN_MYBB'))
{
    die('This file cannot be accessed directly.');
}

if(!defined('BACKUP_PLUGIN_ROOT'))
{
    define('BACKUP_PLUGIN_ROOT', MYBB_ROOT.'inc/plugins/BackupPlugin/');
}

if(!defined('BACKUP_PLUGIN_VERSION'))
{
    define('BACKUP_PLUGIN_VERSION', '1.4.0');
}

if(!defined('PLUGINLIBRARY'))
{
    define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');
}

require_once BACKUP_PLUGIN_ROOT.'Functions/admin_functions.php';
require_once BACKUP_PLUGIN_ROOT.'Functions/scan_functions.php';
require_once BACKUP_PLUGIN_ROOT.'Functions/zip_functions.php';
require_once BACKUP_PLUGIN_ROOT.'Functions/display_functions.php';
require_once BACKUP_PLUGIN_ROOT.'install.php';

if(defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_config_plugins_begin', 'backup_plugin_admin_action');
    $plugins->add_hook('admin_config_plugins_plugin_list', 'backup_plugin_set_flag');
    $plugins->add_hook('admin_page_output_footer', 'backup_plugin_admin_footer');
    $plugins->add_hook('admin_config_permissions', 'backup_plugin_admin_permissions');
    $plugins->add_hook('admin_config_menu', 'backup_plugin_admin_menu');
    $plugins->add_hook('admin_config_action_handler', 'backup_plugin_admin_action_handler');
    $plugins->add_hook('admin_load', 'backup_plugin_admin_load');
}

function backup_plugin_info()
{
    global $lang;

    $lang->load('backup_plugin');

    return array(
        'name'          => 'Backup Plugin',
        'description'   => $lang->backup_plugin_desc,
        'website'       => 'https://mybb.epizy.com',
        'author'        => 'PARADOX',
        'authorsite'    => 'https://community.mybb.com/user-134311.html',
        'version'       => BACKUP_PLUGIN_VERSION,
        'guid'          => '',
        'compatibility' => '18*',
        'codename'      => 'backup_plugin'
    );
}

function backup_plugin_install()
{
    backup_plugin_install_run();
}

function backup_plugin_is_installed()
{
    return file_exists(MYBB_ROOT.backup_plugin_backup_dir().'/.installed');
}

function backup_plugin_uninstall()
{
    backup_plugin_uninstall_run();
}

function backup_plugin_activate()
{
    backup_plugin_activate_run();
}

function backup_plugin_deactivate()
{
    backup_plugin_deactivate_run();
}

/**
 * Handles the backup and download requests coming from the plugin list.
 *
 * The scan runs once and the result is reused for the package and for the
 * flash message.
 */
function backup_plugin_admin_action()
{
    global $mybb, $lang, $plugins;

    $action = $mybb->get_input('action');

    if($action == 'do_download_backup')
    {
        if(!backup_plugin_admin_can('backup'))
        {
            backup_plugin_deny();
        }

        if(!verify_post_check($mybb->get_input('my_post_key')))
        {
            admin_redirect('index.php?module=config-plugins');
        }

        $file = $mybb->get_input('file');
        $file = backup_plugin_clean_path($file);

        if($file === '' || strpos($file, backup_plugin_backup_dir().'/') !== 0 || !is_file(MYBB_ROOT.$file))
        {
            admin_redirect('index.php?module=config-plugins');
        }

        $cleanup = $mybb->get_input('cleanup') == 1;

        backup_plugin_send_zip($file);

        if($cleanup)
        {
            backup_plugin_delete_zip($file);
        }

        exit;
    }

    if($action != 'do_backup')
    {
        return;
    }

    $lang->load('backup_plugin');

    if(!backup_plugin_admin_can('backup'))
    {
        backup_plugin_deny();
    }

    if(!verify_post_check($mybb->get_input('my_post_key')))
    {
        flash_message($lang->invalid_post_verify_key2, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    // Large plugins can hold thousands of files, the scan must not time out.
    if(function_exists('my_set_time_limit'))
    {
        my_set_time_limit();
    }

    $codename = backup_plugin_normalize_codename($mybb->get_input('plugin'));

    if($codename === '')
    {
        flash_message($lang->backup_plugin_error_noplugin, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if(!file_exists(MYBB_ROOT.'inc/plugins/'.$codename.'.php'))
    {
        flash_message($lang->backup_plugin_error_invalid, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $mode = $mybb->get_input('mode');

    if(!in_array($mode, array('replace', 'new', 'download'), true))
    {
        $mode = 'replace';
    }

    if($mode == 'download' && !backup_plugin_admin_can('backup_download'))
    {
        backup_plugin_deny();
    }

    $scan = backup_plugin_scan($codename);

    // Paths the administrator ticked in the skipped table override the scan.
    $selected = $mybb->get_input('selected', MyBB::INPUT_ARRAY);

    if(is_array($selected) && !empty($selected))
    {
        backup_plugin_merge_selected($scan, $selected);
    }

    if(empty($scan['files']))
    {
        $lang->backup_plugin_error_nofiles = $lang->sprintf($lang->backup_plugin_error_nofiles, $scan['plugin_name']);
        flash_message($lang->backup_plugin_error_nofiles, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $result = backup_plugin_create_zip($scan['plugin_name'], $codename, $scan['files'], $mode);

    if(isset($result['error']))
    {
        if($result['error'] == 'perms')
        {
            flash_message($lang->backup_plugin_error_perms, 'error');
        }
        elseif($result['error'] == 'empty')
        {
            flash_message($lang->backup_plugin_error_empty, 'error');
        }
        else
        {
            flash_message($lang->backup_plugin_error_zip, 'error');
        }

        admin_redirect('index.php?module=config-plugins');
    }

    $download_url = 'index.php?module=config-plugins&action=do_download_backup&file='.urlencode($result['filename']).'&my_post_key='.$mybb->post_code;

    if($mode == 'download')
    {
        $download_url .= '&cleanup=1';
    }

    $lang->backup_plugin_success = $lang->sprintf($lang->backup_plugin_success, $scan['plugin_name'], $download_url, count($scan['files']), backup_plugin_format_size($scan['total_size']));
    flash_message($lang->backup_plugin_success, 'success');

    admin_redirect('index.php?module=config-plugins');
}