<?php
if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

/**
 * Returns the folder that receives the packages.
 *
 * @return string Forum relative folder
 */
function backup_plugin_backup_dir()
{
    return 'Backup Plugins';
}

/**
 * Creates the backups folder and its guards when they are missing.
 *
 * @return bool
 */
function backup_plugin_prepare_backup_dir()
{
    $dir = MYBB_ROOT.backup_plugin_backup_dir();

    if(!is_dir($dir))
    {
        @mkdir($dir, 0755, true);
    }

    if(!is_dir($dir) || !is_writable($dir))
    {
        return false;
    }

    if(!file_exists($dir.'/.htaccess'))
    {
        @file_put_contents($dir.'/.htaccess', "Order Deny,Allow\nDeny from all");
    }

    if(!file_exists($dir.'/index.html'))
    {
        @file_put_contents($dir.'/index.html', '');
    }

    return true;
}

/**
 * Builds a safe file name for the package.
 *
 * @param string $plugin_name Plugin display name
 * @param string $codename Plugin codename
 * @return string
 */
function backup_plugin_safe_name($plugin_name, $codename)
{
    $name = trim(strip_tags((string)$plugin_name));
    $name = preg_replace('/[^a-zA-Z0-9_\-\s\.]/', '', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));

    if($name === '')
    {
        $name = $codename;
    }

    return $name;
}

/**
 * Writes the package.
 *
 * @param string $plugin_name Plugin display name
 * @param string $codename Plugin codename
 * @param array $files Forum relative paths
 * @param string $mode replace, new or download
 * @return array success, path and filename, or an error key
 */
function backup_plugin_create_zip($plugin_name, $codename, $files, $mode = 'replace')
{
    if(!backup_plugin_prepare_backup_dir())
    {
        return array('error' => 'perms');
    }

    if(!class_exists('ZipArchive'))
    {
        return array('error' => 'zip');
    }

    $safe_name = backup_plugin_safe_name($plugin_name, $codename);
    $base = backup_plugin_backup_dir().'/'.$safe_name.' plugin';

    if($mode == 'new')
    {
        $counter = 1;
        $candidate = $base.' ('.$counter.')';

        while(file_exists(MYBB_ROOT.$candidate.'.zip'))
        {
            $counter++;
            $candidate = $base.' ('.$counter.')';
        }

        $base = $candidate;
    }

    $zip_filename = $base.'.zip';
    $zip_path = MYBB_ROOT.$zip_filename;

    $zip = new ZipArchive();

    if($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
    {
        return array('error' => 'zip');
    }

    $added = 0;

    foreach($files as $rel_path)
    {
        $rel_path = backup_plugin_clean_path($rel_path);
        $full_path = MYBB_ROOT.$rel_path;

        if($rel_path === '' || !is_file($full_path))
        {
            continue;
        }

        if($zip->addFile($full_path, $rel_path) === true)
        {
            $added++;
        }
    }

    $zip->close();

    if($added === 0)
    {
        @unlink($zip_path);

        return array('error' => 'empty');
    }

    return array(
        'success'  => true,
        'path'     => $zip_path,
        'filename' => $zip_filename,
        'files'    => $added,
    );
}

/**
 * Streams a package to the browser.
 *
 * @param string $rel_path Forum relative package path
 * @return void
 */
function backup_plugin_send_zip($rel_path)
{
    $rel_path = backup_plugin_clean_path($rel_path);
    $path = MYBB_ROOT.$rel_path;

    if($rel_path === '' || !is_file($path))
    {
        return;
    }

    $filename = basename($path);

    while(ob_get_level() > 0)
    {
        @ob_end_clean();
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.filesize($path));
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($path);
}

/**
 * Removes a package from disk.
 *
 * @param string $rel_path Forum relative package path
 * @return void
 */
function backup_plugin_delete_zip($rel_path)
{
    $rel_path = backup_plugin_clean_path($rel_path);

    if($rel_path === '')
    {
        return;
    }

    $directory = backup_plugin_backup_dir().'/';

    if(strpos($rel_path, $directory) !== 0)
    {
        return;
    }

    if(is_file(MYBB_ROOT.$rel_path))
    {
        @unlink(MYBB_ROOT.$rel_path);
    }
}