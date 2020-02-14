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

function MediaFormats() {
    let app = this;

    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });
        $('#button_add').click(function() {
            app.editFormat(null);
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
     * Aktualisiert die angezeigte Seite der Bildformate
     */
    app.refresh = function() {
        ncm.showDefaultLoadingIndicator();

        $.ajax('admin/media/formats/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
            }
        }).done(function(data) {
            $('#placeholder_formats .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
        });
    };

    app.editClickedFormat = function(clickedLink) {
        let key = $(clickedLink).attr('data-key');
        app.editFormat(key);
    };

    app.editFormat = function(key) {
        let dlg = new ncm.InlinePopup(
            'admin/media/formats/html/edit/', {
                key: key
            }, {
                headline:   (key == null || key == '')? 'Bildformat anlegen' : 'Bildformat bearbeiten',
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
                        $.ajax('admin/media/formats/ajax/save', {
                            cache: false,
                            type: 'POST',
                            dataType: 'JSON',
                            data:   {
                                key:            $('#edit_format_key').val(),
                                title:          $('#edit_format_title').val(),
                                description:    $('#edit_format_description').val(),
                                width:          $('#edit_format_width').val(),
                                height:         $('#edit_format_height').val()
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
     * Ruft die Bearbeitungsmaske für das erste ausgewählte Bildformat auf
     */
    app.editSelected = function() {
        let key = ncm.getFirstSelectedRowId();
        if (key != null) {
            app.editFormat(key);
        }
    };

    /**
     * Löscht alle ausgewählten Bildformate
     */
    app.deleteSelected = function() {
        let keys = ncm.getSelectedRowIds();
        if (keys.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Bildformate wirklich endgültig löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/media/formats/ajax/delete', {
                    cache: false,
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        keys: keys
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