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

function ArticlesEdit() {
    let app = this;

    app.isBusy = false;
    app.autoSaveTimer = null;

    /**
     * Initialisiert EventHandler u. ä.
     */
    app.init = function() {
        $('#button_save').click(function() {
            app.saveArticle();
        });

        $('#button_cancel').click(function() {
            app.cancelEdit();
        });

        $('#button_publish').click(function() {
            if (confirm('Wollen Sie den Artikel wirklich freigeben?')) {
                $('#input_article_status_code').val(0);
                app.saveArticle();
            }
        });

        $('#button_preview').click(function() {
            app.openPreview();
        });

        $('#button_toggle_settings').click(function() {
            app.toggleSettings();
        });

        $('#button_edit_vars').click(function() {
            app.editTemplateVars();
        });

        ncm.initTextEditor($('.imgtoolbar'), $('#input_article_content'));

        /*
        $(window).on('beforeunload', function() {
            console.log('beforeunload');
            return confirm('Wollen Sie die Seite wirklich verlassen? Nicht gemachte Änderungen gehen verloren!');
        });
        */

        // TODO Autosave sollte konfigurierbar sein
        //page.autoSaveTimer = window.setInterval(page.autoSaveArticle, 60000);
    };

    /**
     * Blendet das Panel mit den Detail-Einstellungen ein oder aus
     */
    app.toggleSettings = function() {
        if ($('#article_settings_sidebar').is(':visible')) {
            $('#article_settings_sidebar').hide();
        } else {
            $('#article_settings_sidebar').show();
        }
    };

    app.surroundSelectionWith = function(contentBefore, contentAfter) {
        let textArea = document.getElementById('input_article_content');
        ncm.surroundSelectionWith(textArea, contentBefore, contentAfter);
    };

    app.insertTextAtCaret = function(content) {
        let textArea = document.getElementById('input_article_content');
        ncm.insertTextAtCaret(textArea, content);
    };

    app.editTemplateVars = function() {
        let dlg = new ncm.InlinePopup(
            'admin/popup/editvars/', {
                vars: $('#input_article_templatevars').val()
            }, {
                headline:   'Template-Variablen bearbeiten',
                width:      500,
                height:     500,
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
                    caption: 'Übernehmen',
                    clicked: function() {
                        // Geänderten Wert übernehmen
                        $('#input_article_templatevars').val(editVars.getValue());
                        dlg.close();
                    }
                }
            }
        );
    };

    app.saveArticle = function(callback) {
        ncm.showDefaultLoadingIndicator();
        $('#button_save').addClass('disabled');
        app.isBusy = true;

        $.ajax('admin/articles/ajax/save', {
            cache:      false,
            type:       'POST',
            dataType:   'JSON',
            data:       {
                id:                     $('#input_article_id').val(),
                author_id:              $('#input_article_author_id').val(),
                medium_id:              $('#input_article_medium_id').val(),
                status_code:            $('#input_article_status_code').val(),
                headline:               $('#input_article_headline').val(),
                teaser:                 $('#input_article_teaser').val(),
                content:                $('#input_article_content').val(),
                start_timestamp:        ($('#input_article_start_date').val() + ' ' + $('#input_article_start_time').val()).trim(),
                stop_timestamp:         ($('#input_article_stop_date').val() + ' ' + $('#input_article_stop_time').val()).trim(),
                publishing_timestamp:   ($('#input_article_publishing_date').val() + ' ' + $('#input_article_publishing_time').val()).trim(),
                enable_trackbacks:      $('#input_article_enable_trackbacks').prop('checked'),
                enable_comments:        $('#input_article_enable_comments').prop('checked'),
                articletype_key:        $('#input_article_articletype_key').val(),
                templatevars:           $('#input_article_templatevars').val(),
                series_id:              $('#input_article_series_id').val(),
                tags:                   $('#input_article_tags').val()
            }
        }).done(function(data) {
            // Aktualisierte Werte ins Interface zurückschreiben
            $('#input_article_id').val(data.id);
            if (data.publishing_timestamp != null) {
                $('#input_article_publishing_date').val(data.publishing_timestamp.date.substr(0, 10));
                $('#input_article_publishing_time').val(data.publishing_timestamp.date.substr(11, 5));
            } else {
                $('#input_article_publishing_date').val('');
                $('#input_article_publishing_time').val('');
            }
            if (data.creation_timestamp != null) {
                $('#input_article_creation_date').val(data.creation_timestamp.date.substr(0, 10));
                $('#input_article_creation_time').val(data.creation_timestamp.date.substr(11, 5));
            } else {
                $('#input_article_creation_date').val('');
                $('#input_article_creation_time').val('');
            }
            if (data.modification_timestamp != null) {
                $('#input_article_modification_date').val(data.modification_timestamp.date.substr(0, 10));
                $('#input_article_modification_time').val(data.modification_timestamp.date.substr(11, 5));
            } else {
                $('#input_article_modification_date').val('');
                $('#input_article_modification_time').val('');
            }
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            app.isBusy = false;
            $('#button_save').removeClass('disabled');

            if (typeof callback == 'function') {
                callback();
            }
        });
    };

    app.autoSaveArticle = function() {
        if (app.isBusy) {
            console.log("Page is busy - skip auto saving");
        } else {
            console.log("Autosave");
            app.saveArticle();
        }
    };

    /**
     * Öffnet die Vorschau des Artikels in einem Popup auf
     *
     * Bevor die Vorschau geöffnet wird, wird der aktuelle Stand des Artikels gespeichert.
     */
    app.openPreview = function() {
        app.saveArticle(function() {
            ncm.showDefaultLoadingIndicator();
            $.ajax('admin/meta/getArticleUrl/' + $('#input_article_id').val(), {
                cache: false,
                type: 'GET',
                dataType: 'JSON'
            }).done(function(data) {
                window.open(data.url, 'preview');
            }).always(function() {
                ncm.hideDefaultLoadingIndicator();
            });
        });
    };

    /**
     * Bricht die Bearbeitung - auf Nachfrage - ab und springt zurück zur Übersicht
     */
    app.cancelEdit = function() {
        if (confirm('Wollen Sie die Bearbeitung wirklich abbrechen? Nicht gespeicherte Änderungen gehen verloren!')) {
            window.location.href = 'admin/articles/';
        }
    };

    app.init();
}