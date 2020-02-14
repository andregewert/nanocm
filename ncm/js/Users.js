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

function Users() {
    let app = this;
    app.page = $('#input_searchPage').val();

    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });

        $('#button_add').click(function() {
            app.editUser(null);
        });

        $('#button_edit').click(function() {
            app.editSelected();
        });

        $('#button_unlock').click(function() {
            app.unlockSelected();
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
     * Aktualisiert die angezeigte Seite der Benutzerkonten
     * @param {int} page
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/users/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val(),
                searchPage:         page
            }
        }).done(function(data) {
            $('#placeholder_users .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
        });
    };

    /**
     * Löscht alle ausgewählten Benutzerkonten
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Benutzerkonten wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/users/ajax/delete', {
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
     * Sperrt alle ausgewählten Benutzerkonten
     */
    app.lockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/users/ajax/lock', {
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
     * Entsperrt alle ausgewählten Benutzerkonten
     */
    app.unlockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/users/ajax/unlock', {
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
     * Ruft die Bearbeitungsmaske für den ersten ausgewählten Benutzerdatensatz auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        if (id != null) app.editUser(id);
    }

    /**
     * Ruft die Bearbeitungsmaske für das angeklickte Benutzerkonto auf
     */
    app.editClickedUser = function(clickedLink) {
        let id = $(clickedLink).attr('data-id');
        if (id != null) app.editUser(id);
    }

    /**
     * Ruft die Bearbeitungsmaske für Benutzerkonto mit der angegebenen ID auf
     * @param {int} id
     */
    app.editUser = function(id) {
        let dlg = new ncm.InlinePopup(
            'admin/users/html/edit/', {
                id: id
            }, {
                headline:   (id > 0)? 'Benutzerkonto bearbeiten' : 'Benutzerkonto hinzufügen',
                width:      500,
                height:     480,
                loaded:     function() {
                    ncm.focusDefaultElement();
                }
            }, {
                cancel: {
                    caption: 'Abbrechen',
                    clicked:  function() {
                        dlg.close();
                    }
                }, save: {
                    caption: 'Speichern',
                    clicked: function() {
                        ncm.showDefaultLoadingIndicator();
                        $.ajax('admin/users/ajax/save', {
                            cache:      false,
                            type:       'POST',
                            dataType:   'JSON',
                            data:        {
                                id:             id,
                                status_code:    $('#edit_user_status_code').val(),
                                firstname:      $('#edit_user_firstname').val(),
                                lastname:       $('#edit_user_lastname').val(),
                                username:       $('#edit_user_username').val(),
                                email:          $('#edit_user_email').val(),
                                usertype:       $('#edit_user_usertype').val(),
                                password:       $('#edit_user_password').val()
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

    app.init();
}