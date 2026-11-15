<?php
$l['backup_plugin_title'] = 'Backup Plugin';
$l['backup_plugin_desc'] = 'Adds a backup link to each plugin in the plugin manager. An advanced scan collects the files that really belong to the plugin and shows you exactly what goes into the ZIP before it is written.';
$l['backup_plugin_backup'] = 'Backup';
$l['backup_plugin_replace'] = 'Replace old package';
$l['backup_plugin_add_new'] = 'Add new package';
$l['backup_plugin_download_only'] = 'Download only (no file saved)';
$l['backup_plugin_backup_choice'] = 'Backup Mode';
$l['backup_plugin_creating'] = 'Creating backup...';
$l['backup_plugin_success'] = 'The plugin "{1}" has been backed up successfully. {3} file(s), {4}. <a href="{2}">Download the plugin</a>';
$l['backup_plugin_error'] = 'An error occurred while backing up the plugin "{1}".';
$l['backup_plugin_error_zip'] = 'Could not create the ZIP file. Please check that the ZipArchive extension is available.';
$l['backup_plugin_error_empty'] = 'No file could be added to the package. Please check the scan results.';
$l['backup_plugin_error_noplugin'] = 'No plugin was specified for backup.';
$l['backup_plugin_error_invalid'] = 'The specified plugin is not valid.';
$l['backup_plugin_error_nofiles'] = 'No files were found for the plugin "{1}".';
$l['backup_plugin_error_perms'] = 'Could not write to the backups directory. Please check permissions.';
$l['backup_plugin_download'] = 'Download';
$l['backup_plugin_saved'] = 'The backup has been saved to: {1}';
$l['backup_plugin_available'] = 'Available Backups';
$l['backup_plugin_size'] = 'Size';

$l['can_backup_plugins'] = 'Can back up plugins?';
$l['can_see_backup_link'] = 'Can see the "Backup" link in the plugin list?';
$l['can_use_download_only'] = 'Can use "Download only" backup mode (no file saved)?';
$l['can_manage_backup_plugin'] = 'Can use the Backup Plugin scan page?';
$l['backup_plugin_error_denied'] = 'You do not have permission to perform this action.';

$l['backup_plugin_preview_title'] = 'Backup Plugin Scan';
$l['backup_plugin_version'] = 'version {1}';
$l['backup_plugin_edit_settings'] = 'Backup Plugin Settings';
$l['back_to_plugins'] = 'Back to Plugins';
$l['backup_plugin_yes'] = 'Yes';
$l['backup_plugin_no'] = 'No';
$l['backup_plugin_nothing_found'] = 'Nothing here.';

$l['backup_plugin_stat_files'] = 'Files';
$l['backup_plugin_stat_size'] = 'Total size';
$l['backup_plugin_stat_mode'] = 'Scan mode';
$l['backup_plugin_stat_pluginlibrary'] = 'PluginLibrary';
$l['backup_plugin_stat_codename'] = 'Codename';
$l['backup_plugin_folders_label'] = 'Folders owned by this plugin:';
$l['backup_plugin_no_plugin_folder'] = 'none, the plugin is a single file';
$l['backup_plugin_mode_strict'] = 'Strict';
$l['backup_plugin_mode_balanced'] = 'Balanced';

$l['backup_plugin_included_label'] = 'Included files';
$l['backup_plugin_skipped_label'] = 'Skipped files';
$l['backup_plugin_column_note'] = 'Why it is included';
$l['backup_plugin_column_skip_note'] = 'Why it is skipped';
$l['backup_plugin_column_size'] = 'Size';
$l['backup_plugin_select_all'] = 'Select all skipped files';
$l['backup_plugin_skipped_hint'] = 'Tick any skipped file or folder that must still be packaged, then press "Review selected files". A ticked folder brings all of its files with it.';
$l['backup_plugin_review_selected'] = 'Review selected files';

$l['backup_plugin_reason_entry'] = 'Plugin entry file';
$l['backup_plugin_reason_owned'] = 'Inside the plugin folder';
$l['backup_plugin_reason_referenced'] = 'Folder referenced by plugin code';
$l['backup_plugin_reason_verified'] = 'Referenced by plugin code';
$l['backup_plugin_reason_explicit'] = 'Listed by the plugin';
$l['backup_plugin_reason_root_entry'] = 'Entry script in the forum root';
$l['backup_plugin_reason_pluginlibrary'] = 'PluginLibrary dependency';
$l['backup_plugin_reason_dependency'] = 'Dependency of another plugin';
$l['backup_plugin_reason_selected'] = 'Selected by the administrator';

$l['backup_plugin_skip_excluded'] = 'Excluded by settings ({1})';
$l['backup_plugin_skip_not_related'] = 'Name matches but the file is not plugin code';
$l['backup_plugin_skip_core'] = 'MyBB core file';
$l['backup_plugin_skip_foreign'] = 'Belongs to another plugin';
$l['backup_plugin_skip_not_code'] = 'Data folder, not plugin code';
$l['backup_plugin_skip_too_large'] = 'Larger than the maximum file size';
$l['backup_plugin_skip_setting'] = 'Turned off in the plugin settings';

$l['backup_plugin_category_plugin'] = 'Plugin code';
$l['backup_plugin_category_language'] = 'Language files';
$l['backup_plugin_category_asset'] = 'Styles and images';
$l['backup_plugin_category_data'] = 'Templates and data files';
$l['backup_plugin_category_dependency'] = 'Dependencies';

$l['backup_plugin_pluginlibrary_yes'] = 'Added';
$l['backup_plugin_pluginlibrary_no'] = 'Not used';
$l['backup_plugin_pluginlibrary_disabled'] = 'Turned off in settings';