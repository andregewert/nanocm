/*
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

function Ncm() {
    var app = this;

    app.showLoadingIndicator = function() {

    };

    app.hideLoadingIndicator = function() {

    };

    app.toggleAllRowsSelection = function(headerCheckbox) {
        $(headerCheckbox).parents('table').find('input[type=checkbox]').prop(
            'checked',
            $(headerCheckbox).prop('checked')
        );
    };

    // Standard-DHTML-Elemente initialisieren
    $(document).ready(function() {

        // Popup-Buttons initialisieren
        $('.imgtoolbar_popupbutton').each(function() {
            var button = $(this);
            var buttonTimer;

            $(this).find('a').click(function() {
                $(button).find('.imgtoolbar_popup').toggle();
            });

            $(button).mouseout(function() {
                if ($(button).is(':visible')) {
                    buttonTimer = window.setTimeout(function () {
                        $(button).find('.imgtoolbar_popup').hide();
                    }, 800);
                }
            });

            $(button).mouseover(function() {
                if (buttonTimer != null) {
                    window.clearTimeout(buttonTimer);
                }
            });
        });

    });

}

var ncm = new Ncm();
