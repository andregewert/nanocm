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

function Terms() {
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
     * Refreshes the current page
     * @param {int} page
     */
    app.refresh = function(page) {
        ncm.showDefaultLoadingIndicator();
        if (typeof page === 'undefined') page = app.page;

        $.ajax('admin/terms/html/list', {
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
        // TODO not implemented yet
    };

    app.init();
}