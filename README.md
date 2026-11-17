# Backup Plugin for MyBB

<p align="center">
  <img src=".github/backup-plugin-cover.svg" alt="Backup Plugin for MyBB" width="100%" />
</p>

Backup Plugin adds a **Backup** action to every row of the MyBB Admin CP plugin
manager. It scans a plugin, shows exactly which files belong to it, and writes a
ZIP package that mirrors the forum directory structure.

The scanner does not trust file names alone. A file is only packaged when there
is evidence that it belongs to the plugin:

| Tier | Evidence | Example |
| --- | --- | --- |
| 1 | Ownership | Everything inside `inc/plugins/<Codename>/` |
| 2 | Reference | A `require`, `include`, URL, or glob in plugin code |
| 3 | Verified name | A name match whose content actually references the plugin |

Anything rejected is listed on the preview page with the reason, and the
administrator can pull individual files or whole folders back into the package.

- **Version:** 1.4.0
- **Codename:** `backup_plugin`
- **Compatibility:** MyBB 1.8.x (`18*`)
- **Author:** PARADOX
- **License:** GPL-3.0 (bundled PluginLibrary remains LGPL-3.0+)

## Features

- Backup link on every plugin row, filtered by Admin CP permissions.
- Strict and balanced scan modes.
- Ownership-aware folder discovery, including case-corrected paths.
- Reference parsing for `require`, `include`, `require_once`, `include_once`,
  URLs, and glob patterns found in plugin code.
- Verified-name fallback that inspects file contents instead of trusting names.
- Excluded path patterns with wildcard support, applied to files and folders.
- Extra paths that must always be treated as part of the plugin.
- Optional inclusion of root entry scripts such as `sw.js` or `store.php`.
- Optional inclusion of files from other plugins that are hard-loaded.
- Optional PluginLibrary detection and bundling.
- Per-file size cap, with the plugin entry file and PluginLibrary exempt.
- Preview page with file count, total size, scan mode, detected codename,
  owned folders, category summary, and per-file inclusion reason.
- Skipped-file table with select-all, per-file checkboxes, and folder expansion.
- Three output modes:
  - **Replace old package** — overwrite `Backup Plugins/<name> plugin.zip`
  - **Add new package** — create `<name> plugin (1).zip`, `(2)`, and so on
  - **Download only** — stream the ZIP and delete it after sending
- Packages are stored under `Backup Plugins/` with an `.htaccess` deny rule and
  an empty `index.html`.
- Download action protected by MyBB post verification.
- Four granular permissions: back up plugins, see the Backup link, use
  download-only mode, and use the scan page.
- Settings, templates, and stylesheet registration through PluginLibrary.
- Uninstalling removes settings, templates, and the install marker but keeps
  every existing backup package.

## Requirements

- MyBB 1.8.x
- PHP 7.4 or newer (the source uses `Throwable` and modern array syntax)
- ZIP extension (`ZipArchive`)
- Write access to the forum root so `Backup Plugins/` can be created
- [PluginLibrary](https://community.mybb.com/thread-157841.html) for settings,
  templates, and stylesheet registration

## Installation

The repository mirrors the MyBB forum root. Copy these paths into the forum:

```
admin/modules/config/backup_plugin.php
inc/languages/english/admin/backup_plugin.lang.php
inc/plugins/backup_plugin.php
inc/plugins/pluginlibrary.php
inc/plugins/BackupPlugin/
```

Then:

1. Open **Admin CP → Configuration → Plugins**.
2. Install and activate **Backup Plugin**.
3. Use **Configuration → Backup Plugin** for settings.
4. Return to the plugin list and click **Backup** next to any plugin.

The `Backup Plugins/` folder is created automatically on install. If the web
server cannot create it, create it manually and make it writable by PHP.

## How the scan works

1. The plugin entry file `inc/plugins/<codename>.php` is always added.
2. Every folder owned by the plugin is walked recursively.
3. Configured extra paths are added.
4. Queued PHP, JS, CSS, HTML, JSON, SQL, XML, Markdown, and `.htaccess` files
   are parsed for references, and matching files are added.
5. In balanced mode, name-matched files are accepted only when their contents
   reference the plugin.
6. Root entry scripts are checked when that setting is enabled.
7. PluginLibrary is detected from packaged files and added when enabled.
8. Exclusions and the size cap are applied last and always win.

## Package layout

A package keeps forum-relative paths, for example:

```
inc/plugins/example.php
inc/plugins/Example/install.php
inc/plugins/Example/Functions/admin_functions.php
inc/languages/english/admin/example.lang.php
admin/modules/config/example.php
```

This lets an administrator extract the ZIP directly over a forum root.

## Permissions

| Permission | Language key | Controls |
| --- | --- | --- |
| Back up plugins | `can_backup_plugins` | Scan page and package creation |
| See Backup link | `can_see_backup_link` | Backup link on the plugin list |
| Download only | `can_use_download_only` | Download-only output mode |
| Use scan page | `can_manage_backup_plugin` | Registered permission, not enforced separately |

Super admins bypass every check.

## Known defects and limitations

These are defects in the shipped source. They are documented here rather than
fixed, so the published code stays byte-for-byte identical to the local plugin.

### High

1. **Backups rely on `.htaccess` for protection.** `Backup Plugins/.htaccess`
   uses Apache 2.2 syntax (`Order Deny,Allow`). Apache 2.4 without
   `mod_access_compat` and any nginx server ignore it, so ZIP packages may be
   downloadable from the web root. The packages contain plugin source code.
2. **Arbitrary forum files can be selected into a package.**
   `backup_plugin_expand_selection()` accepts any forum-relative file or folder
   from the `selected[]` request value. It is not restricted to paths that the
   scan actually skipped. An administrator with backup permission can therefore
   package files such as `inc/config.php` or `inc/settings.php`.
3. **Scanning executes plugin code.** `backup_plugin_get_plugin_info()` calls
   `require_once` on the target plugin entry file. A plugin can run arbitrary
   bootstrap code, register hooks, emit output, or call `exit`. The `try/catch`
   only catches `Throwable`, not `exit` or fatal errors from duplicate symbols.
4. **No CSRF token on the preview page.** The scan page reads `selected[]` from
   a GET request without `verify_post_check()`. It does not write a package, but
   a crafted link can pre-select files and change what the administrator sees.
5. **ZIP creation is not atomic.** A fatal error or timeout during
   `ZipArchive::close()` leaves a partial or corrupt package in `Backup Plugins/`.
   There is no temporary file and rename step.

### Medium

6. **`backup_plugin_read_text()` caches by path only.** The `$max_bytes`
   argument is ignored after the first read. A file first read with a 128 KB cap
   stays truncated when a later caller asks for 256 KB.
7. **`backup_plugin_admin_footer()` prints a script tag by string
   concatenation.** This bypasses the project rule that JS must be emitted from
   a registered `{plugin}_js.html` template. The inline config `<div>` is also
   printed directly.
8. **`backup_plugin_output_stylesheet()` and `backup_plugin_output_script()`
   print `<link>` and `<script>` tags directly.** The CSS is also registered
   through PluginLibrary, but the Admin CP never reads `themestylesheets`, so
   the registration is dead weight and the manual tags do the real work.
9. **The download action accepts any file under `Backup Plugins/`.** There is
   no `.zip` extension check, so `.htaccess`, `.installed`, and `index.html` can
   be streamed with a ZIP content type.
10. **Symlinks are not resolved for downloads or deletion.**
    `backup_plugin_send_zip()` and `backup_plugin_delete_zip()` validate only the
    string prefix. A symlink inside `Backup Plugins/` could point outside it.
11. **Install ignores directory creation failure.** `backup_plugin_install_run()`
    discards the return value of `backup_plugin_prepare_backup_dir()`, so the
    plugin reports a successful install even when no writable backup folder
    exists.
12. **New-package numbering is racy.** `backup_plugin_create_zip()` checks for
    the next free `(n)` name and opens the file later. Concurrent requests can
    choose the same name and overwrite each other.
13. **Malformed `settings.json` can emit warnings.**
    `backup_plugin_admin_add_settings()` reads `$data['group']['title']` and
    `$data['group']['description']` without checking that the keys exist.
14. **Case-insensitive path matching on case-sensitive filesystems.**
    `backup_plugin_match_patterns()` uses `strcasecmp()` and `strncasecmp()`, so
    an exclusion for `uploads` also matches a distinct `Uploads` directory.
15. **Dead variable in path cleaning.** `backup_plugin_clean_path()` assigns
    `$absolute = false` and never changes it. The check is harmless but
    misleading.
16. **Duplicate and stale docblock.** `scan_functions.php` contains an orphaned
    docblock before `backup_plugin_content_uses_pluginlibrary()` and an
    unreachable second docblock above
    `backup_plugin_check_pluginlibrary_dependency()`.
17. **`backup_plugin_check_pluginlibrary_dependency()` rescans the plugin.**
    It ignores its `$files` argument for the decision and runs a full second
    scan, which is expensive on large plugins.
18. **Preview render assumes every scan array key exists.**
    `backup_plugin_build_preview()` indexes `file_reasons`, `sizes`, and
    `settings['show_skipped']` without fallbacks. A malformed or partial scan
    produces PHP notices.

### Low

19. **No front-end language file.** The plugin is admin-only, so
    `inc/languages/english/backup_plugin.lang.php` is absent. Any front-end
    string added later will be untranslated.
20. **`backup_plugin_is_installed()` depends on a marker file.** Deleting
    `Backup Plugins/.installed` makes MyBB think the plugin is not installed,
    even though settings and templates remain.
21. **Uninstall leaves the backups directory and `.htaccess` in place.** This is
    intentional to avoid deleting backups, but it leaves orphaned files and
    stale protection rules.
22. **No cleanup of old packages.** Replace mode overwrites one package; new
    mode accumulates numbered ZIPs indefinitely, and there is no admin UI to
    list or delete them.
23. **Silent redirect on invalid download post key.** The download branch calls
    `admin_redirect()` without a flash message, so the failure is invisible.
24. **`backup_plugin_safe_name()` allows leading dots and repeated spaces.**
    Names are sanitized, but a display name such as `.hidden` produces a hidden
    package file.
25. **`backup_plugin_format_size()` uses binary units with decimal labels.**
    It divides by 1024 but prints `KB`, `MB`, and `GB` rather than `KiB`, `MiB`,
    and `GiB`.
26. **`backup_plugin_scan()` silently returns an empty result when the entry
    file is excluded by settings.** The UI reports "no files found" instead of
    explaining that the exclusion matched the plugin entry file.
27. **`backup_plugin_merge_selected()` trusts `filesize()`.** A failed stat
    becomes `0`, so the preview size can be wrong for unreadable files.
28. **Template `backupplugin_preview.html` contains inline `style` attributes.**
    This conflicts with the project rule against inline presentation markup.
29. **`backup_plugin_admin_permissions()` registers `backup_plugin` but no code
    checks it separately.** The permission appears in the UI but adds no access
    control beyond the `backup` check.
30. **`backup_plugin_admin_load()` exits after requiring the preview file.**
    Any admin page output buffering or shutdown handlers registered after the
    `admin_load` hook are skipped.

## Repository layout

```
admin/modules/config/backup_plugin.php
inc/languages/english/admin/backup_plugin.lang.php
inc/plugins/backup_plugin.php
inc/plugins/pluginlibrary.php
inc/plugins/BackupPlugin/
├── Admin/preview.php
├── Assets/backup_plugin.css
├── Assets/backup_plugin.js
├── Functions/admin_functions.php
├── Functions/display_functions.php
├── Functions/scan_functions.php
├── Functions/zip_functions.php
├── Templates/backupplugin_preview.html
├── Templates/backupplugin_skipped.html
├── install.php
└── settings.json
```

## License

The plugin source is licensed under the GNU General Public License v3.0. See
`LICENSE`.

`inc/plugins/pluginlibrary.php` is a bundled third-party dependency and remains
under its own LGPL-3.0-or-later license.
