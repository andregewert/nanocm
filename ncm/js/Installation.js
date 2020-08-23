/*
 * NanoCM
 * Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Functions for the installation manager module
 * @constructor
 */
function Installation() {
    let app = this;

    /**
     * Initializes event handlers etc.
     * @returns {void}
     */
    app.init = function() {
        app.page = $('#input_searchPage').val();
        $('#button_refresh').click(function() {
            app.refresh();
        });

        $('#button_create').click(function()  {
            app.openCreateBackupDialog();
        });

        $('#button_restore').click(function() {
            app.openRestoreBackupDialog();
        });

        $('#button_delete').click(function() {
            app.deleteSelected();
        });

        app.refresh();
    };

    /**
     * Refreshes the list of existing backups
     * @param {number|undefined} page
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();

        if (typeof page === 'undefined' || page === null) page = app.page;
        app.page = page;

        $.ajax('admin/installation/html/list', {
            cache:      false,
            type:       'get',
            dataType:   'HTML',
            data:       {
                searchPage:     page
            }
        }).done(function(data) {
            $('#placeholder_installation .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
        });
    };

    /**
     * Opens the dialog for creating a new backup
     * @returns {void}
     */
    app.openCreateBackupDialog = function() {
        let dlg = new ncm.InlinePopup(
            'admin/installation/html/createbackup', {
            }, {
                headline:   'Backup erstellen',
                width:      600,
                height:     300,
                loaded:     function() {
                    ncm.focusDefaultElement();
                }
            }, {
                cancel:     {
                    id:         'buttonCancel',
                    caption:    'Abbrechen',
                    clicked:    function() {
                        dlg.close();
                    }
                },
                save:       {
                    id:         'buttonCreate',
                    caption:    'Erstellen',
                    clicked:    function() {
                        $(dlg.buttons['buttonCreate']).prop('disabled', true);
                        $(dlg.buttons['buttonCancel']).prop('disabled', true);
                        dlg.setClosingDisabled();
                        $('#infoWaiting').show();

                        $.ajax('admin/installation/ajax/create', {
                            cache:      false,
                            type:       'POST',
                            dataType:   'JSON'
                        }).always(function() {
                            $('#infoWaiting').hide();
                            dlg.forceClose();
                            app.refresh();
                        });
                    }
                }
            }
        );
    };

    /**
     * Opens the dialog for restoring a single backup
     * @return {void}
     */
    app.openRestoreBackupDialog = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id === null) return;

        let dlg = new ncm.InlinePopup(
            'admin/installation/html/restorebackup', {
                key:            id
            }, {
                headline:       'Backup wiederherstellen',
                width:          600,
                height:         350,
                loaded:         function() {
                    ncm.focusDefaultElement();
                }
            }, {
                cancel:     {
                    id:         'buttonCancel',
                    caption:    'Abbrechen',
                    clicked:    function() {
                        dlg.close();
                    }
                },
                save:       {
                    id:         'buttonRestore',
                    caption:    'Wiederherstellen',
                    clicked:    function() {
                        if (confirm('Wollen Sie dieses Backup wirklich wiederherstellen? Es können dabei neuere Daten verloren gehen!')) {
                            $(dlg.buttons['buttonCancel']).prop('disabled', true);
                            $(dlg.buttons['buttonRestore']).prop('disabled', true);
                            dlg.setClosingDisabled();
                            $('#infoWaiting').show();

                            $.ajax('admin/installation/ajax/restore', {
                                cache:      false,
                                type:       'POST',
                                dataType:   'JSON',
                                data:       {
                                    key:    id
                                }
                            }).always(function() {
                                $('#infoWaiting').hide();
                                dlg.forceClose();
                                app.refresh();
                            });
                        }
                    }
                }
            }
        );
    };

    /**
     * Deletes the selected backup files
     * @returns {void}
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Backups wirklich unwiederbringlich löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/installation/ajax/delete', {
                    cache:      false,
                    type:       'GET',
                    dataType:   'JSON',
                    data:       {
                        keys:   ids
                    }
                }).always(function() {
                    ncm.hideDefaultLoadingIndicator();
                    app.refresh();
                });
            }
        }
    };

    app.init();
}