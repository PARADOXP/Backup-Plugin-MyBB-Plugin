<?php
if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

/**
 * Adds the Backup Plugin page to the Configuration tab.
 *
 * @param array $sub_menu Configuration sub menu
 * @return void
 */
function backup_plugin_admin_menu(&$sub_menu)
{
    global $lang;

    $lang->load('backup_plugin');

    $sub_menu[] = array(
        'id'    => 'backup_plugin',
        'title' => $lang->backup_plugin_title,
        'link'  => 'index.php?module=config-backup_plugin',
    );
}

/**
 * Routes the module to the plugin owned admin page.
 *
 * @param array $actions Config module actions
 * @return void
 */
function backup_plugin_admin_action_handler(&$actions)
{
    $actions['backup_plugin'] = array(
        'active' => 'backup_plugin',
        'file'   => 'backup_plugin.php',
    );
}

/**
 * Loads the plugin admin page and stops MyBB from loading its own stub.
 *
 * @return void
 */
function backup_plugin_admin_load()
{
    global $page, $lang;

    if(!isset($page->active_action) || $page->active_action != 'backup_plugin')
    {
        return;
    }

    if(!backup_plugin_admin_can('backup'))
    {
        backup_plugin_deny();
    }

    $lang->load('backup_plugin');

    $admin_file = BACKUP_PLUGIN_ROOT.'Admin/preview.php';

    if(!file_exists($admin_file))
    {
        return;
    }

    require $admin_file;
    exit;
}

/**
 * Hook: admin_config_permissions
 *
 * Adds the Backup Plugin permissions to the Configuration & Plugins permission
 * screens, per user or per usergroup.
 *
 * @param array $admin_permissions Current config module permissions
 * @return array
 */
function backup_plugin_admin_permissions($admin_permissions)
{
    global $lang;

    $lang->load('backup_plugin');

    $admin_permissions['backup']          = $lang->can_backup_plugins;
    $admin_permissions['backup_link']     = $lang->can_see_backup_link;
    $admin_permissions['backup_download'] = $lang->can_use_download_only;
    $admin_permissions['backup_plugin']   = $lang->can_manage_backup_plugin;

    return $admin_permissions;
}

/**
 * Checks one Backup Plugin permission. Super admins always pass.
 *
 * @param string $key One of: backup, backup_link, backup_download, backup_plugin
 * @return bool
 */
function backup_plugin_admin_can($key)
{
    global $mybb;

    if(empty($mybb->user['uid']))
    {
        return false;
    }

    if(is_super_admin($mybb->user['uid']))
    {
        return true;
    }

    if(!empty($mybb->admin['permissions']) && is_array($mybb->admin['permissions']))
    {
        return isset($mybb->admin['permissions']['config'][$key]) && $mybb->admin['permissions']['config'][$key] == '1';
    }

    return false;
}

/**
 * Stops the current action with a "no permission" message.
 *
 * @return void
 */
function backup_plugin_deny()
{
    global $lang;

    $lang->load('backup_plugin');

    flash_message($lang->backup_plugin_error_denied, 'error');
    admin_redirect('index.php?module=config-plugins');
}

/**
 * Hook: admin_config_plugins_plugin_list
 *
 * Remembers that the plugin list is being rendered so the footer hook knows it
 * has work to do.
 *
 * @return void
 */
function backup_plugin_set_flag()
{
    global $backup_plugin_on_plugins_page;

    $backup_plugin_on_plugins_page = true;
}

/**
 * Hook: admin_page_output_footer
 *
 * Drops the configuration the plugin script needs on the plugins page. No
 * inline script is written, the values are read from data attributes.
 *
 * @return void
 */
function backup_plugin_admin_footer()
{
    global $mybb, $lang, $backup_plugin_on_plugins_page;

    if(empty($backup_plugin_on_plugins_page))
    {
        return;
    }

    $can_backup = backup_plugin_admin_can('backup') ? 1 : 0;
    $can_link = backup_plugin_admin_can('backup_link') ? 1 : 0;

    $lang->load('backup_plugin');

    $base = 'index.php?module=config-backup_plugin&amp;action=preview&amp;plugin=';
    $asset = $mybb->settings['bburl'].'/inc/plugins/BackupPlugin/Assets/backup_plugin.js';
    $label = htmlspecialchars_uni($lang->backup_plugin_backup);

    echo '<div id="backup_plugin_config" data-can-backup="'.$can_backup.'" data-can-link="'.$can_link.'" data-base="'.$base.'" data-label="'.$label.'" data-version="'.htmlspecialchars_uni(BACKUP_PLUGIN_VERSION).'" style="display: none;"></div>';
    echo '<script type="text/javascript" src="'.htmlspecialchars_uni($asset).'?ver='.BACKUP_PLUGIN_VERSION.'"></script>';
}

/**
 * Reads the plugin settings, falling back to the shipped defaults when the
 * settings group has not been installed yet.
 *
 * @return array Short setting names to values, plus the raw pattern lists
 */
function backup_plugin_get_settings()
{
    global $mybb;

    static $defaults = array(
        'scan_mode'                    => 'balanced',
        'excluded_paths'               => "inc/plugins/temp\nBackup Plugins\nadmin/backups\nuploads\n.git\nnode_modules",
        'included_paths'               => '',
        'include_pluginlibrary'        => '1',
        'include_root_scripts'         => '1',
        'include_external_dependencies' => '0',
        'max_file_size'                => '0',
        'show_skipped'                 => '1',
    );

    $settings = array();

    foreach($defaults as $key => $default)
    {
        $name = 'backup_plugin_'.$key;

        // An empty stored value is a valid choice, e.g. no excluded paths at all.
        if(isset($mybb->settings[$name]))
        {
            $settings[$key] = $mybb->settings[$name];
        }
        else
        {
            $settings[$key] = $default;
        }
    }

    $settings['scan_mode'] = $settings['scan_mode'] === 'strict' ? 'strict' : 'balanced';
    $settings['excludes'] = backup_plugin_split_patterns($settings['excluded_paths']);
    $settings['includes'] = backup_plugin_split_patterns($settings['included_paths']);

    return $settings;
}

/**
 * Creates the settings group from settings.json through PluginLibrary.
 *
 * @return void
 */
function backup_plugin_admin_add_settings()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;

    if(!is_object($PL))
    {
        $PL = new PluginLibrary();
    }

    $raw = @file_get_contents(BACKUP_PLUGIN_ROOT.'settings.json');

    if($raw === false)
    {
        return;
    }

    $data = json_decode($raw, true);

    if(!is_array($data) || empty($data['settings']))
    {
        return;
    }

    $list = array();

    foreach($data['settings'] as $setting)
    {
        $name = str_replace('backup_plugin_', '', $setting['name']);

        $list[$name] = array(
            'title'       => $setting['title'],
            'description' => $setting['description'],
            'optionscode' => $setting['optionscode'],
            'value'       => $setting['value'],
            'disporder'   => $setting['disporder'],
        );
    }

    $PL->settings(
        'backup_plugin',
        $data['group']['title'],
        $data['group']['description'],
        $list
    );
}

/**
 * Deletes the settings group.
 *
 * @return void
 */
function backup_plugin_admin_remove_settings()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;

    if(!is_object($PL))
    {
        $PL = new PluginLibrary();
    }

    $PL->settings_delete('backup_plugin', true);
}

/**
 * Registers the admin stylesheet with PluginLibrary so it lands in the MyBB
 * stylesheet cache. No style is ever written into the page head.
 *
 * @return void
 */
function backup_plugin_admin_add_stylesheet()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;

    if(!is_object($PL))
    {
        $PL = new PluginLibrary();
    }

    $css_file = BACKUP_PLUGIN_ROOT.'Assets/backup_plugin.css';

    if(!file_exists($css_file))
    {
        return;
    }

    $css = @file_get_contents($css_file);

    if($css === false)
    {
        return;
    }

    $PL->stylesheet('backup_plugin', $css, 'admin/*.php');
}

/**
 * Removes the registered admin stylesheet.
 *
 * @return void
 */
/**
 * Returns the id of the plugin settings group.
 *
 * MyBB edits a settings group by its numeric id, so the preview page needs
 * this value to build a working link.
 *
 * @return int Group id, 0 when the group is missing
 */
function backup_plugin_settings_gid()
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name='backup_plugin'", array('limit' => 1));
    $row = $db->fetch_array($query);

    return isset($row['gid']) ? (int)$row['gid'] : 0;
}

/**
 * Loads the plugin templates from the Templates folder into the database.
 *
 * Every file in the folder is named backupplugin_<template>.html. PluginLibrary
 * turns the key into backupplugin_<template>, so the database name matches the
 * file name and the plugin keeps one clean folder.
 *
 * @return void
 */
function backup_plugin_admin_add_templates()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;

    if(!is_object($PL))
    {
        $PL = new PluginLibrary();
    }

    $files = glob(BACKUP_PLUGIN_ROOT.'Templates/*.html');

    if(!is_array($files) || empty($files))
    {
        return;
    }

    $templates = array();

    foreach($files as $file)
    {
        $content = @file_get_contents($file);

        if($content !== false)
        {
            $name = basename($file, '.html');
            $key = str_replace('backupplugin_', '', $name);

            $templates[$key] = $content;
        }
    }

    if(!empty($templates))
    {
        $PL->templates('backupplugin', 'Backup Plugin', $templates);
    }
}

/**
 * Removes the plugin templates from the database.
 *
 * @return void
 */
function backup_plugin_admin_remove_templates()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;

    if(!is_object($PL))
    {
        $PL = new PluginLibrary();
    }

    $PL->templates_delete('backupplugin', true);
}

function backup_plugin_admin_remove_stylesheet()
{
    global $PL;

    if(!file_exists(PLUGINLIBRARY))
    {
        return;
    }

    require_once PLUGINLIBRARY;

    if(!is_object($PL))
    {
        $PL = new PluginLibrary();
    }

    $PL->stylesheet_delete('backup_plugin', false, true);
}