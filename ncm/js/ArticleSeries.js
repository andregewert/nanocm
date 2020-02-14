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

function Articleseries() {
    let app = this;
    app.page = $('#input_searchPage').val();

    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });
        $('#button_add').click(function() {
            app.editSeries(null);
        });
        $('#button_edit').click(function() {
            app.editSelected();
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

        app.refresh();
    };

    /**
     * Aktualisiert die angezeigte Seite der Artikelserien
     * @param {int} page
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/articleseries/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val(),
                searchPage:         page
            }
        }).done(function(data) {
            $('#placeholder_articleseries .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
        });
    };

    app.editClickedSeries = function(clickedLink) {
        let id = $(clickedLink).attr('data-id');
        app.editSeries(id);
    };

    app.editSeries = function(id) {
        let dlg = new ncm.InlinePopup(
            'admin/articleseries/html/edit/', {
                id: id
            }, {
                headline:   (id == null)? 'Artikelserie anlegen' : 'Artikelserie bearbeiten',
                width:      500,
                height:     320,
                loaded:     function() {
                    ncm.focusDefaultElement();
                }
            }, {
                cancel: {
                    caption: 'Abbrechen',
                    clicked: function() {
                        dlg.close();
                    }
                },
                save: {
                    caption: 'Speichern',
                    clicked: function() {
                        ncm.showDefaultLoadingIndicator();
                        $.ajax('admin/articleseries/ajax/save', {
                            cache: false,
                            type: 'POST',
                            dataType: 'JSON',
                            data:   {
                                id:                 id,
                                status_code:        $('#edit_articleseries_status_code').val(),
                                title:              $('#edit_articleseries_title').val(),
                                description:        $('#edit_articleseries_description').val(),
                                sorting_key:        $('#edit_articleseries_sorting_key').val()
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
     * Ruft die Bearbeitungsmaske für die erste ausgewählte Artikelserie auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id != null) {
            app.editSeries(id);
        }
    };

    app.lockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            $.ajax('admin/articleseries/ajax/lock', {
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
    };

    app.unlockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            $.ajax('admin/articleseries/ajax/unlock', {
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
    };

    /**
     * Löscht alle ausgewählten Artikelserien
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Artikelserien wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/articleseries/ajax/delete', {
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

    app.init();
}