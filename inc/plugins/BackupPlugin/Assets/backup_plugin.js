/**
 * Backup Plugin - plugin list helper.
 *
 * Adds a Backup link next to the Activate / Deactivate links of every plugin
 * row. Configuration is read from the #backup_plugin_config element, no inline
 * script is used.
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        var $selectAll = $('#backup_plugin_select_all');

        if ($selectAll.length) {
            $selectAll.on('change', function () {
                $('.backup_plugin_skipped input[type="checkbox"][name="selected[]"]')
                    .prop('checked', this.checked);
            });
        }

        var config = document.getElementById('backup_plugin_config');

        if (!config) {
            return;
        }

        var canBackup = config.getAttribute('data-can-backup') === '1';
        var canLink = config.getAttribute('data-can-link') === '1';
        var base = config.getAttribute('data-base') || '';
        var label = config.getAttribute('data-label') || 'Backup';

        if (!canBackup || !canLink) {
            return;
        }

        $('a[href*="module=config-plugins"]').filter(function () {
            return /action=(activate|deactivate)/.test($(this).attr('href') || '');
        }).each(function () {
            var $link = $(this);
            var $row = $link.closest('tr');

            if ($row.find('.backup_plugin_link').length) {
                return;
            }

            var match = ($link.attr('href') || '').match(/[?&]plugin=([^&]+)/);

            if (!match) {
                return;
            }

            var codename = decodeURIComponent(match[1]);

            $('<a class="backup_plugin_link" />')
                .attr('href', base + encodeURIComponent(codename))
                .attr('title', $row.find('td').first().text().replace(/\s+/g, ' ').trim())
                .text(label)
                .insertAfter($link);

            $link.after(' | ');
        });
    });
}(jQuery));