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

function Pages() {
    let app = this;
    app.page = $('#input_searchPage').val();

    /**
     * Initialisiert Event-Handler etc.
     */
    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });
        $('#button_edit').click(function() {
            app.editSelected();
        });
        $('#button_lock').click(function() {
            app.lockSelected();
        });
        $('#button_delete').click(function() {
            app.deleteSelected();
        });
        app.refresh();
    };

    /**
     * Aktualisiert die Liste der Pages
     * @param {int} page Anzuzeigende Seite
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/pages/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val(),
                searchPage:         page
            }
        }).done(function(data) {
            $('#placeholder_pages .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            $('#input_searchTerm').focus();
        });
    };

    /**
     * Ruft die Bearbeitungsmaske für die erste ausgewählte Page auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id != null) {
            document.location.href = 'admin/pages/edit/' + id;
        }
    };

    /**
     * Löscht die ausgewählten Pages
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Seiten wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/pages/ajax/delete', {
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

    app.lockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/pages/ajax/lock', {
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