<?php
if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

/**
 * Human readable label for an acceptance reason.
 *
 * @param string $reason Reason key
 * @param array $scan Scan result
 * @return string
 */
function backup_plugin_reason_label($reason, $scan)
{
    global $lang;

    $lang->load('backup_plugin');

    switch($reason)
    {
        case 'entry':
            return $lang->backup_plugin_reason_entry;
        case 'owned':
            return $lang->backup_plugin_reason_owned;
        case 'referenced':
            return $lang->backup_plugin_reason_referenced;
        case 'verified':
            return $lang->backup_plugin_reason_verified;
        case 'explicit':
            return $lang->backup_plugin_reason_explicit;
        case 'root_entry':
            return $lang->backup_plugin_reason_root_entry;
        case 'pluginlibrary':
            return $lang->backup_plugin_reason_pluginlibrary;
        case 'dependency':
            return $lang->backup_plugin_reason_dependency;
        case 'selected':
            return $lang->backup_plugin_reason_selected;
        default:
            return $reason;
    }
}

/**
 * Human readable label for a skip reason.
 *
 * @param string $reason Reason key
 * @param string $detail Matching exclusion pattern when relevant
 * @return string
 */
function backup_plugin_skip_label($reason, $detail = '')
{
    global $lang;

    $lang->load('backup_plugin');

    switch($reason)
    {
        case 'excluded':
            return $lang->sprintf($lang->backup_plugin_skip_excluded, $detail);
        case 'not_related':
            return $lang->backup_plugin_skip_not_related;
        case 'core':
            return $lang->backup_plugin_skip_core;
        case 'foreign_plugin':
            return $lang->backup_plugin_skip_foreign;
        case 'not_code':
            return $lang->backup_plugin_skip_not_code;
        case 'too_large':
            return $lang->backup_plugin_skip_too_large;
        case 'setting':
            return $lang->backup_plugin_skip_setting;
        default:
            return $reason;
    }
}

/**
 * Human readable label for a category.
 *
 * @param string $category Category key
 * @return string
 */
function backup_plugin_category_label($category)
{
    global $lang;

    $lang->load('backup_plugin');

    switch($category)
    {
        case 'plugin':
            return $lang->backup_plugin_category_plugin;
        case 'language':
            return $lang->backup_plugin_category_language;
        case 'asset':
            return $lang->backup_plugin_category_asset;
        case 'data':
            return $lang->backup_plugin_category_data;
        case 'dependency':
            return $lang->backup_plugin_category_dependency;
        default:
            return $category;
    }
}

/**
 * Renders the files of a scan as a table body.
 *
 * @param array $rows List of array('file', 'note', 'size')
 * @return string
 */
function backup_plugin_render_rows($rows)
{
    global $lang;

    $html = '';

    $i = 0;

    foreach($rows as $row)
    {
        // alt_row is the striped row class the Admin CP table style uses.
        $class = ($i % 2) ? ' class="alt_row"' : '';
        $i++;

        $html .= '<tr'.$class.'>';
        $html .= '<td><code>'.htmlspecialchars_uni($row['file']).'</code></td>';
        $html .= '<td>'.htmlspecialchars_uni($row['note']).'</td>';
        $html .= '<td style="text-align: right;">'.htmlspecialchars_uni($row['size']).'</td>';
        $html .= '</tr>';
    }

    if($html === '')
    {
        $html = '<tr class="alt_row"><td colspan="3">'.$lang->backup_plugin_nothing_found.'</td></tr>';
    }

    return $html;
}

/**
 * Renders the skipped files with a checkbox so an administrator can pull a
 * rejected path into the package.
 *
 * The checkboxes live in a second table but belong to the form declared by the
 * preview template, so they use the HTML form attribute.
 *
 * @param array $rows List of array('file', 'note', 'size')
 * @param array $selected Paths picked on a previous review
 * @return string
 */
function backup_plugin_render_skipped_rows($rows, $selected = array())
{
    global $lang;

    $picked = array();

    foreach((array)$selected as $path)
    {
        if(is_string($path) && $path !== '')
        {
            $picked[$path] = true;
        }
    }

    $html = '';
    $i = 0;

    foreach($rows as $row)
    {
        $class = ($i % 2) ? ' class="alt_row"' : '';
        $i++;

        $checked = isset($picked[$row['file']]) ? ' checked="checked"' : '';

        $html .= '<tr'.$class.'>';
        $html .= '<td style="text-align: center;"><input type="checkbox" name="selected[]" form="backup_plugin_actions_form" value="'.htmlspecialchars_uni($row['file']).'"'.$checked.' /></td>';
        $html .= '<td><code>'.htmlspecialchars_uni($row['file']).'</code></td>';
        $html .= '<td>'.htmlspecialchars_uni($row['note']).'</td>';
        $html .= '<td style="text-align: right;">'.htmlspecialchars_uni($row['size']).'</td>';
        $html .= '</tr>';
    }

    if($html === '')
    {
        $html = '<tr class="alt_row"><td colspan="4">'.$lang->backup_plugin_nothing_found.'</td></tr>';
    }

    return $html;
}

/**
 * Builds the scan page.
 *
 * @param array $scan Scan result
 * @param string $codename Plugin codename
 * @param array $selected Paths picked on a previous review
 * @return string Page HTML
 */
function backup_plugin_build_preview($scan, $codename, $selected = array())
{
    global $mybb, $lang, $templates, $db;

    $lang->load('backup_plugin');

    $plugin_name = $scan['plugin_name'];
    $version = $scan['plugin_version'];
    $counts = backup_plugin_category_counts($scan);

    $backup_plugin_plugin_name = htmlspecialchars_uni($plugin_name).($version !== '' ? ' <span class="smalltext">'.$lang->sprintf($lang->backup_plugin_version, htmlspecialchars_uni($version)).'</span>' : '');
    $backup_plugin_codename = htmlspecialchars_uni($codename);
    $backup_plugin_file_count = count($scan['files']);
    $backup_plugin_total_size = backup_plugin_format_size($scan['total_size']);
    $backup_plugin_skip_count = count($scan['skipped']);
    $backup_plugin_folders = '';

    foreach($scan['owned_folders'] as $folder)
    {
        $backup_plugin_folders .= '<li><code>'.htmlspecialchars_uni($folder).'</code></li>';
    }

    if($backup_plugin_folders === '')
    {
        $backup_plugin_folders = '<li>'.$lang->backup_plugin_no_plugin_folder.'</li>';
    }

    $backup_plugin_mode = $lang->backup_plugin_mode_strict;

    if($scan['scan_mode'] === 'balanced')
    {
        $backup_plugin_mode = $lang->backup_plugin_mode_balanced;
    }

    $backup_plugin_pluginlibrary = $lang->backup_plugin_pluginlibrary_no;

    if($scan['uses_pluginlibrary'])
    {
        $backup_plugin_pluginlibrary = $scan['pluginlibrary']
            ? $lang->backup_plugin_pluginlibrary_yes
            : $lang->backup_plugin_pluginlibrary_disabled;
    }

    $included_rows = array();

    foreach($scan['files'] as $rel_path)
    {
        $included_rows[] = array(
            'file' => $rel_path,
            'note' => backup_plugin_reason_label($scan['file_reasons'][$rel_path], $scan),
            'size' => backup_plugin_format_size($scan['sizes'][$rel_path]),
        );
    }

    $backup_plugin_included_rows = backup_plugin_render_rows($included_rows);

    $backup_plugin_included_summary = '';

    foreach($counts as $category => $count)
    {
        if($count === 0)
        {
            continue;
        }

        if($backup_plugin_included_summary !== '')
        {
            $backup_plugin_included_summary .= ' &middot; ';
        }

        $backup_plugin_included_summary .= backup_plugin_category_label($category).': '.$count;
    }

    $backup_plugin_skipped_rows = '';
    $backup_plugin_skipped_table = '';

    if($scan['skipped'] && !empty($scan['settings']['show_skipped']))
    {
        $skipped_rows = array();

        foreach($scan['skipped'] as $rel_path => $reason)
        {
            $detail = isset($scan['skip_detail'][$rel_path]) ? $scan['skip_detail'][$rel_path] : '';

            if(is_int($rel_path))
            {
                $rel_path = $reason;
                $reason = 'excluded';
            }

            $skipped_rows[] = array(
                'file' => $rel_path,
                'note' => backup_plugin_skip_label($reason, $detail),
                'size' => '-',
            );
        }

        $backup_plugin_skipped_rows = backup_plugin_render_skipped_rows($skipped_rows, $selected);

        // Templates::get() only returns the raw template text, so the row
        // variable inside it would never be replaced. Evaluate it here, in the
        // scope that owns the variables, before the outer template is built.
        eval("\$backup_plugin_skipped_table = \"".$templates->get('backupplugin_skipped')."\";");
    }

    $review_url = 'index.php?module=config-backup_plugin&action=preview&plugin='.urlencode($codename);

    $backup_plugin_actions = '<form id="backup_plugin_actions_form" method="post" action="'.htmlspecialchars_uni('index.php?module=config-plugins&action=do_backup').'">';
    $backup_plugin_actions .= '<input type="hidden" name="my_post_key" value="'.htmlspecialchars_uni($mybb->post_code).'" />';
    $backup_plugin_actions .= '<input type="hidden" name="plugin" value="'.htmlspecialchars_uni($codename).'" />';
    $backup_plugin_actions .= '<button type="submit" name="mode" value="replace" class="submit_button">'.htmlspecialchars_uni($lang->backup_plugin_replace).'</button> ';
    $backup_plugin_actions .= '<button type="submit" name="mode" value="new" class="submit_button">'.htmlspecialchars_uni($lang->backup_plugin_add_new).'</button>';

    if(!empty($scan['skipped']) && !empty($scan['settings']['show_skipped']))
    {
        $backup_plugin_actions .= ' <button type="submit" formaction="'.htmlspecialchars_uni($review_url).'" formmethod="post" class="submit_button">'.htmlspecialchars_uni($lang->backup_plugin_review_selected).'</button>';
    }

    if(backup_plugin_admin_can('backup_download'))
    {
        $backup_plugin_actions .= ' <button type="submit" name="mode" value="download" class="submit_button">'.htmlspecialchars_uni($lang->backup_plugin_download_only).'</button>';
    }

    $backup_plugin_actions .= '</form>';

    $backup_plugin_settings_link = '<a href="'.htmlspecialchars_uni('index.php?module=config-settings').'">'.$lang->backup_plugin_edit_settings.'</a>';

    $gid = backup_plugin_settings_gid();

    if($gid > 0)
    {
        $backup_plugin_settings_link = '<a href="'.htmlspecialchars_uni('index.php?module=config-settings&action=change&gid='.$gid).'">'.$lang->backup_plugin_edit_settings.'</a>';
    }
    $backup_plugin_plugins_link = '<a href="'.htmlspecialchars_uni('index.php?module=config-plugins').'">'.$lang->back_to_plugins.'</a>';

    eval("\$backup_plugin_content = \"".$templates->get('backupplugin_preview')."\";");

    return $backup_plugin_content;
}

/**
 * Prints the link to the plugin stylesheet.
 *
 * The Admin CP builds its head from files in admin/styles/ and never reads the
 * themestylesheets table, so a stylesheet registered through PluginLibrary is
 * not output here. The link is printed directly after the ACP header instead.
 */
function backup_plugin_output_stylesheet()
{
    global $mybb;

    $file = BACKUP_PLUGIN_ROOT.'Assets/backup_plugin.css';

    if(!file_exists($file))
    {
        return;
    }

    $url = htmlspecialchars_uni($mybb->settings['bburl'].'/inc/plugins/BackupPlugin/Assets/backup_plugin.css?ver='.(int)@filemtime($file));

    echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\" />\n";
}

/**
 * Prints the scan page helper script.
 *
 * The Admin CP footer hook only loads the script on the plugin list, so the
 * scan page prints it here. It powers the select all checkbox.
 *
 * @return void
 */
function backup_plugin_output_script()
{
    global $mybb;

    $file = BACKUP_PLUGIN_ROOT.'Assets/backup_plugin.js';

    if(!file_exists($file))
    {
        return;
    }

    $url = htmlspecialchars_uni($mybb->settings['bburl'].'/inc/plugins/BackupPlugin/Assets/backup_plugin.js?ver='.(int)@filemtime($file));

    echo "\t<script type=\"text/javascript\" src=\"{$url}\"></script>\n";
}

/**
 * Prints the scan page.
 *
 * @param array $scan Scan result
 * @param string $codename Plugin codename
 * @param array $selected Paths picked on a previous review
 * @return void
 */
function backup_plugin_output_preview($scan, $codename, $selected = array())
{
    global $page, $lang;

    $lang->load('backup_plugin');

    $page->add_breadcrumb_item($lang->backup_plugin_title, 'index.php?module=config-backup_plugin');
    $page->add_breadcrumb_item(htmlspecialchars_uni($scan['plugin_name']));
    $page->output_header($lang->backup_plugin_preview_title);

    backup_plugin_output_stylesheet();
    backup_plugin_output_script();

    echo backup_plugin_build_preview($scan, $codename, $selected);

    $page->output_footer();
    exit;
}