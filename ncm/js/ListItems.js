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

function ListItems() {
    let app = this;

    /**
     * Initialisiert Event-Handler etc.
     */
    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });
        $('#button_back').click(function() {
            document.location.href = 'admin/lists/';
        });
        $('#button_lock').click(function() {
            app.lockSelected();
        });
        $('#button_unlock').click(function() {
            app.unlockSelected();
        });
        $('#button_delete').click(function() {
            app.deleteSelected();
        });
        $('#button_add').click(function() {
            app.editListItem(null);
        });

        app.refresh();
    };

    /**
     * Aktualisiert die Liste der Listeneinträge
     */
    app.refresh = function() {
        ncm.showDefaultLoadingIndicator();
        $.ajax('admin/lists/html/listitems', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                id:                 $('#input_list_id').val(),
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val()
            }
        }).done(function(data) {
            $('#placeholder_listitems .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            $('#input_searchTerm').focus();
        });
    };

    app.editListItem = function(id) {
        let dlg = new ncm.InlinePopup(
            'admin/lists/html/edititem', {
                id:             id,
                userlist_id:    $('#input_list_id').val()
            }, {
                headline:       (id > 0)? 'Listeneintrag bearbeiten' : 'Listeneintrag erstellen',
                width:          500,
                height:         350,
                loaded:         function() {
                    ncm.focusDefaultElement();
                }
            }, {
                cancel:     {
                    caption:    'Abbrechen',
                    clicked:    function() {
                        dlg.close();
                    }
                },
                save:       {
                    caption:    'Speichern',
                    clicked:    function() {
                        ncm.showDefaultLoadingIndicator();
                        $.ajax('admin/lists/ajax/saveitem', {
                            cache:      false,
                            type:       'POST',
                            dataType:   'JSON',
                            data:       {
                                id:             id,
                                userlist_id:    $('#edit_listitem_userlist_id').val(),
                                parent_id:      $('#edit_listitem_parent_id').val(),
                                status_code:    $('#edit_listitem_status_code').val(),
                                title:          $('#edit_listitem_title').val(),
                                content:        $('#edit_listitem_content').val(),
                                parameters:     $('#edit_listitem_parameters').val(),
                                sorting_code:   $('#edit_listitem_sorting_code').val()
                            }
                        }).always(function() {
                            ncm.hideDefaultLoadingIndicator();
                            dlg.close();
                            app.refresh();
                        });
                    }
                }
            }
        );
    };

    /**
     * Ruft die Bearbeitungsmaske für den ersten ausgewählten Listeneintrag auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id != null) app.editListItem(id);
    };

    /**
     * Ruft die Bearbeitungsmaske für die angeklickte Zeile auf
     */
    app.editClickedListItem = function(clickedLink) {
        let id = $(clickedLink).attr('data-id');
        if (id != null) app.editListItem(id);
    };

    /**
     * Löscht die ausgewählten Listeneinträge
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Listeneinträge wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/lists/ajax/deleteitems', {
                    cache:      false,
                    type:       'GET',
                    dataType:   'JSON',
                    data:       {
                        ids: ids
                    }
                }).always(function() {
                    ncm.hideDefaultLoadingIndicator();
                    app.refresh();
                });
            }
        }
    };

    /**
     * Entsperrt die ausgewählten Listeneinträge
     */
    app.unlockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/lists/ajax/unlockitems', {
                cache:      false,
                type:       'GET',
                dataType:   'JSON',
                data:       {
                    ids:    ids
                }
            }).always(function() {
                ncm.hideDefaultLoadingIndicator();
                app.refresh();
            });
        }
    };

    /**
     * Sperrt die ausgewählten Listeneinträge
     */
    app.lockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/lists/ajax/lockitems', {
                cache:      false,
                type:       'GET',
                dataType:   'JSON',
                data:       {
                    ids:    ids
                }
            }).always(function() {
                ncm.hideDefaultLoadingIndicator();
                app.refresh();
            });
        }
    };

    app.init();
}