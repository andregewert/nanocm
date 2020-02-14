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

function PagesEdit() {
    let app = this;

    app.isBusy = false;
    app.autoSaveTimer = null;

    /**
     * Initialisiert EventHandler etc.
     */
    app.init = function() {

        // TODO Bei Eingabe / Änderung der URL muss sofort geprüft werden, ob sie nicht bereits vergeben ist!

        $('#button_save').click(app.savePage);
        $('#button_cancel').click(app.cancelEdit);
        $('#button_toggle_settings').click(app.toggleSettings);

        $('#button_publish').click(function() {
            if (confirm('Wollen Sie die Seite wirklich freigeben?')) {
                $('#input_page_status_code').val(0);
                app.savePage();
            }
        });

        ncm.initTextEditor($('.imgtoolbar'), $('#input_page_content'));
    };

    /**
     * Blendet das Panel mit den Detail-Einstellungen ein oder aus
     */
    app.toggleSettings = function() {
        if ($('#page_settings_sidebar').is(':visible')) {
            $('#page_settings_sidebar').hide();
        } else {
            $('#page_settings_sidebar').show();
        }
    };

    app.isUrlAlreadyExisting = function(url) {
        // TODO implementieren
    };

    /**
     * Speichert die aktuelle Seite
     */
    app.savePage = function() {
        ncm.showDefaultLoadingIndicator();
        $('#button_save').addClass('disabled');
        app.isBusy = true;

        $.ajax('admin/pages/ajax/save', {
            cache:      false,
            type:       'POST',
            dataType:   'JSON',
            data:       {
                id:                     $('#input_page_id').val(),
                author_id:              $('#input_page_author_id').val(),
                status_code:            $('#input_page_status_code').val(),
                url:                    $('#input_page_url').val(),
                headline:               $('#input_page_headline').val(),
                content:                $('#input_page_content').val(),
                publishing_timestamp:   ($('#input_page_publishing_date').val() + ' ' + $('#input_page_publishing_time').val()).trim()
            }
        }).done(function(data) {
            // Aktualisierte Werte ins Interface zurückschreiben
            $('#input_page_id').val(data.id);
            if (data.publishing_timestamp != null) {
                $('#input_page_publishing_date').val(data.publishing_timestamp.date.substr(0, 10));
                $('#input_page_publishing_time').val(data.publishing_timestamp.date.substr(11, 5));
            } else {
                $('#input_page_publishing_date').val('');
                $('#input_page_publishing_time').val('');
            }
            if (data.creation_timestamp != null) {
                $('#input_page_creation_date').val(data.creation_timestamp.date.substr(0, 10));
                $('#input_page_creation_time').val(data.creation_timestamp.date.substr(11, 5));
            } else {
                $('#input_page_creation_date').val('');
                $('#input_page_creation_time').val('');
            }
            if (data.modification_timestamp != null) {
                $('#input_page_modification_date').val(data.modification_timestamp.date.substr(0, 10));
                $('#input_page_modification_time').val(data.modification_timestamp.date.substr(11, 5));
            } else {
                $('#input_page_modification_date').val('');
                $('#input_page_modification_time').val('');
            }
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            app.isBusy = false;
            $('#button_save').removeClass('disabled');
        });
    };

    app.autoSavePage = function() {
        if (app.isBusy) {
            console.log("Page is busy - skip auto saving");
        } else {
            console.log("Autosave");
            app.savePage();
        }
    };

    app.openPreview = function() {
        // TODO implementieren
        alert('Vorschau öffnen');
    };

    /**
     * Bricht die Bearbeitung - auf Nachfrage - ab und springt zurück zur Übersicht
     */
    app.cancelEdit = function() {
        if (confirm('Wollen Sie die Bearbeitung wirklich abbrechen? Nicht gespeicherte Änderungen gehen verloren!')) {
            window.location.href = 'admin/pages/';
        }
    };

    app.init();
}