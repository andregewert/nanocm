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

function Definitions() {
    let app = this;
    app.page = $('#input_searchPage').val();

    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });

        $('#button_add').click(function() {
            app.editDefinition('', '');
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
     * Aktualisiert die angezeigte Seite der Einstellungen
     * @param {int} page
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/definitions/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                searchTerm: $('#input_searchTerm').val(),
                searchPage: page
            }
        }).done(function(data) {
            $('#placeholder_definitions .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
        });
    };

    app.editClickedDefinition = function(clickedLink) {
        let definitiontype = $(clickedLink).attr('data-definitiontype');
        let key = $(clickedLink).attr('data-key');
        app.editDefinition(definitiontype, key);
    };

    app.editDefinition = function(definitiontype, key) {
        let dlg = new ncm.InlinePopup(
            'admin/definitions/html/edit/', {
                definitiontype: definitiontype,
                key: key
            }, {
                headline:   (definitiontype == '' && key == '')? 'Definition anlegen' : 'Definition bearbeiten',
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
                        $.ajax('admin/definitions/ajax/save', {
                            cache: false,
                            type: 'POST',
                            dataType: 'JSON',
                            data:   {
                                definitiontype:     $('#edit_definition_definitiontype').val(),
                                key:                $('#edit_definition_key').val(),
                                title:              $('#edit_definition_title').val(),
                                value:              $('#edit_definition_value').val(),
                                parameters:         $('#edit_definition_parameters').val()
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
     * Ruft die Bearbeitungsmaske für die erste ausgewählte Einstellung auf
     */
    app.editSelected = function() {
        let id = ncm.getFirstSelectedRowId();
        console.log(id);
        console.log(id.split(' ', 2));
        if (id != null) {
            app.editDefinition(id.split(' ', 2)[0], id.split(' ', 2)[1]);
        }
    };

    /**
     * Löscht alle ausgewählten Einstellungen
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Einstellungen wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/definitions/ajax/delete', {
                    cache: false,
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        keys: ids
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