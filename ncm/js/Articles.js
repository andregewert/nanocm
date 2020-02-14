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

function Articles() {
    let app = this;
    app.page = $('#input_searchPage').val();

    /**
     * Initialisiert EventHandler etc.
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
     * Aktualisiert die Artikelliste
     * @param {int} page
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/articles/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data:       {
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val(),
                searchPage:         page
            }
        }).done(function(data) {
            $('#placeholder_articles .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            $('#input_searchTerm').focus();
        });
    };

    /**
     * Ruft die Bearbeitungsmaske für den ersten ausgewählten Artikel auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id != null) {
            document.location.href = 'admin/articles/edit/' + id;
        }
    };

    /**
     * Löscht alle ausgewählten Artikel
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Artikel wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/articles/ajax/delete', {
                    cache: false,
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        ids: ids
                    }
                }).always(function () {
                    ncm.hideDefaultLoadingIndicator();
                    app.refresh();
                });
            }
        }
    };

    /**
     * Sperrt die ausgewählten Artikel
     */
    app.lockSelected = function()  {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/articles/ajax/lock', {
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
    };

    app.init();
}