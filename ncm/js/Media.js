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

function Media() {
    let app = this;
    app.page = $('#input_searchPage').val();
    app.parentId = $('#input_searchParentId').val();

    /**
     * Initialisiert Event-Handler etc.
     */
    app.init = function() {

        // Drag and drop für File upload initialisieren
        $('#media_dropzone').on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
            e.stopPropagation();
            e.preventDefault();
        }).on('dragover dragenter', function(e) {
            $('#media_dropzone').addClass('dragover');
            e.originalEvent.dataTransfer.dropEffect = 'copy';
        }).on('dragleave dragend drop', function() {
            $('#media_dropzone').removeClass('dragover');
        }).on('drop', function(e) {
            let files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                let reader = new FileReader();
                let senddata = new Object();

                senddata.name = files[0].name;
                senddata.date = files[0].lastModified;
                senddata.size = files[0].size;
                senddata.type = files[0].type;

                reader.onload = function (fileData) {
                    senddata.fileData = fileData.target.result;
                    ncm.showDefaultLoadingIndicator();

                    // TODO Auf Fehler bzw. negative Antworten reagieren!
                    $.ajax('admin/media/upload', {
                        cache:      false,
                        type:       'POST',
                        dataType:   'JSON',
                        data:       {
                            parent_id:  app.parentId,
                            file:       senddata
                        }
                    }).always(function() {
                        ncm.hideDefaultLoadingIndicator();
                        app.refresh();
                    });
                };
                reader.readAsBinaryString(files[0]);
            }
        });

        $('#button_addfolder').click(function() {
            app.editFolder(0);
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

        $('#button_refresh').click(function() {
            app.refresh();
        });

        app.refresh();
    };

    /**
     * Aktualisiert die Liste der Pages
     *
     * @param {int} page Anzuzeigende Seite
     * @param {int} parentId Übergeordneter Ordner
     */
    app.refresh = function(page, parentId) {
        ncm.showDefaultLoadingIndicator();

        if (typeof page === 'undefined' || page === null) page = app.page;
        if (typeof parentId === 'undefined' || parentId === null) parentId = app.parentId;
        app.page = page;
        app.parentId = parentId;

        $.ajax('admin/media/html/list', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                searchTerm:         $('#input_searchTerm').val(),
                searchStatusCode:   $('#select_searchStatusCode').val(),
                searchPage:         page,
                searchParentId:     parentId
            }
        }).done(function(data) {
            $('#placeholder_media .content').html(data);
            $('.selectall').click(function() {
                ncm.toggleAllRowsSelection(this);
            });
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            $('#input_searchTerm').focus();
        });
    };

    /**
     * Löscht die ausgewählten Mediendateien
     */
    app.deleteSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            if (confirm('Wollen Sie die ausgewählten Mediendateien wirklich löschen?')) {
                ncm.showDefaultLoadingIndicator();
                $.ajax('admin/media/ajax/delete', {
                    cache:      false,
                    type:       'GET',
                    dataType:   'JSON',
                    data: {
                        ids:    ids
                    }
                }).always(function () {
                    ncm.hideDefaultLoadingIndicator();
                    app.refresh();
                });
            }
        }
    };

    /**
     * Sperrt die ausgewählten Mediendateien
     */
    app.lockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/media/ajax/lock', {
                cache:      false,
                type:       'GET',
                dataType:   'JSON',
                data: {
                    ids:    ids
                }
            }).always(function () {
                ncm.hideDefaultLoadingIndicator();
                app.refresh();
            });
        }
    };

    /**
     * Entsperrt die ausgewählten Mediendateien
     */
    app.unlockSelected = function() {
        let ids = ncm.getSelectedRowIds();
        if (ids.length > 0) {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/media/ajax/unlock', {
                cache:      false,
                type:       'GET',
                dataType:   'JSON',
                data: {
                    ids:    ids
                }
            }).always(function () {
                ncm.hideDefaultLoadingIndicator();
                app.refresh();
            });
        }
    };

    /**
     * Ruft den Bearbeitungsdialog für eine bestimmte Mediendatei auf
     *
     * @param {int} id
     */
    app.editMedium = function(id) {
        id = parseInt(id, 10);
        if (id == 0) return;

        let dlg = new ncm.InlinePopup(
            'admin/media/html/editmedium', {
                id: id
            }, {
                headline:   'Datei bearbeiten',
                width:      600,
                height:     550,
                loaded:     function () {
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
                        $.ajax('admin/media/ajax/savemedium', {
                            cache:      false,
                            type:       'POST',
                            dataType:   'JSON',
                            data:       {
                                id: id,
                                parent_id: $('#edit_medium_parent_id').val(),
                                title: $('#edit_medium_title').val(),
                                tags: $('#edit_medium_tags').val(),
                                status_code: $('#edit_medium_status_code').val(),
                                description: $('#edit_medium_description').val(),
                                attribution: $('#edit_medium_attribution').val()
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
     * Ruft den Bearbeitungsdialog für einen bestimmten Ordner auf
     *
     * @param {int} id Datensatz-ID des zu bearbeitenden Ordners
     */
    app.editFolder = function(id) {
        id = parseInt(id, 10);

        let dlg = new ncm.InlinePopup(
            'admin/media/html/editfolder', {
                id: id
            }, {
                headline:   (id > 0)? 'Ordner bearbeiten' : 'Ordner erstellen',
                width:      500,
                height:     250,
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
                        $.ajax('admin/media/ajax/savefolder', {
                            cache:      false,
                            type:       'POST',
                            dataType:   'JSON',
                            data:       {
                                id:             id,
                                parent_id:      app.parentId,
                                filename:       $('#edit_medium_filename').val(),
                                title:          $('#edit_medium_title').val(),
                                description:    $('#edit_medium_description').val(),
                                tags:           $('#edit_medium_tags').val()
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