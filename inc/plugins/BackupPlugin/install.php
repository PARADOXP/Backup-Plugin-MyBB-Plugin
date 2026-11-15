<?php
if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

if(!defined('PLUGINLIBRARY'))
{
    define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');
}

function backup_plugin_install_run()
{
    backup_plugin_prepare_backup_dir();
    backup_plugin_add_install_marker();
}

function backup_plugin_activate_run()
{
    backup_plugin_install_run();

    backup_plugin_admin_add_settings();
    backup_plugin_admin_add_stylesheet();
    backup_plugin_admin_add_templates();

    if(function_exists('rebuild_settings'))
    {
        rebuild_settings();
    }
}

function backup_plugin_deactivate_run()
{
    backup_plugin_admin_remove_stylesheet();
}

function backup_plugin_uninstall_run()
{
    backup_plugin_admin_remove_stylesheet();
    backup_plugin_admin_remove_templates();
    backup_plugin_admin_remove_settings();

    // Only the install marker is removed. The backups folder and every package
    // inside it are kept, so uninstalling never deletes a backup.
    backup_plugin_remove_install_marker();
}

function backup_plugin_add_install_marker()
{
    $marker = MYBB_ROOT.backup_plugin_backup_dir().'/.installed';

    if(!file_exists($marker))
    {
        @file_put_contents($marker, '');
    }
}

function backup_plugin_remove_install_marker()
{
    $marker = MYBB_ROOT.backup_plugin_backup_dir().'/.installed';

    if(file_exists($marker))
    {
        @unlink($marker);
    }
}