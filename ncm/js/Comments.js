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

function Comments() {
    let app = this;

    /**
     * Initialisiert EventHandler etc.
     */
    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });
        $('#button_lock').click(function() {
            app.lockSelected();
        });
        $('#button_unlock').click(function() {
            app.unlockSelected();
        });
        $('#button_edit').click(function() {
            app.editSelected();
        });
        $('#button_delete').click(function() {
            app.deleteSelected();
        });
        app.refresh();
    };

    /**
     * Aktualisiert die Liste der angezeigten Kommentare
     * @param {int} page Optional: anzuzeigende Seite
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = 1;

        $.ajax('admin/comments/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data:       {
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val(),
                searchPage:         page
            }
        }).done(function(data) {
            $('#placeholder_comments .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            $('#input_searchTerm').focus();
        });
    };

    /**
     * Sperrt die ausgewählten Kommentare
     */
    app.lockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/comments/ajax/lock', {
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
     * Entsperrt die ausgewählten Kommentare
     */
    app.unlockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/comments/ajax/unlock', {
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
     * Ruft die Bearbeitungsmaske für den angeklickten Kommentar auf
     * @param {node} clickedLink
     */
    app.editClickedComment = function(clickedLink) {
        let id = $(clickedLink).attr('data-id');
        app.editComment(id);
    };

    /**
     * Ruft die Bearbeitungsmaske für den Kommentar mit der angegebenen ID auf
     * @param {int} id
     */
    app.editComment = function(id) {
        let dlg = new ncm.InlinePopup(
            'admin/comments/html/edit/', {
                id: id
            }, {
                headline:   'Kommentar bearbeiten',
                width:      550,
                height:     580,
                loaded:     function() {
                    ncm.focusDefaultElement();
                }
            }, {
                cancel: {
                    caption: 'Abbrechen',
                    clicked:  function() {
                        dlg.close();
                    }
                },
                save: {
                    caption: 'Speichern',
                    clicked: function() {
                        ncm.showDefaultLoadingIndicator();
                        $.ajax('admin/comments/ajax/save', {
                            cache:      false,
                            type:       'POST',
                            dataType:   'JSON',
                            data:        {
                                id:             id,
                                username:       $('#edit_comment_username').val(),
                                email:          $('#edit_comment_email').val(),
                                status_code:    $('#edit_comment_status_code').val(),
                                use_gravatar:   $('#edit_comment_use_gravatar').prop('checked')? '1' : '0',
                                headline:       $('#edit_comment_headline').val(),
                                content:        $('#edit_comment_content').val()
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
     * Ruft die Bearbeitungsmaske für den ersten ausgewählten Kommentar auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id != null) {
            app.editComment(id);
        }
    };

    /**
     * Löscht die ausgewählten Kommentare
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Einstellungen wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/comments/ajax/delete', {
                    cache:      false,
                    type:       'POST',
                    dataType:   'JSON',
                    data:       {
                        ids:    ids
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