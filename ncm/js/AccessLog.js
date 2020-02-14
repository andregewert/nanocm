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

function AccessLog() {
    let app = this;
    app.page = $('#input_searchPage').val();

    /**
     * Initialisiert Event-Handler etc.
     */
    app.init = function() {
        $('#button_refresh').click(function() {
            app.refresh();
        });
        app.refresh();
    };

    /**
     * Aktualisiert den angezeigten Ausschnitt aus dem AccessLog
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/stats/html/listaccesslog', {
            cache:      false,
            type:       'GET',
            dataType:   'HTML',
            data: {
                searchYear:         $('#input_searchYear').val(),
                searchMonth:        $('#select_searchMonth').val(),
                searchPage:         page
            }
        }).done(function(data) {
            $('#placeholder_accesslog .content').html(data);
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
            $('#input_searchYear').focus();
        });
    };
    app.init();
}