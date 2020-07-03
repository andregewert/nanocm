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

function NcmSetup(baseUrl) {

    let app = this;

    app.baseUrl = baseUrl;

    app.initSetupForm = function() {
        $('#form_setup').submit(function() {
            let valid = true;
            let emptyFieldLabels = [];
            let requiredFields = [
                'input_pagetitle',
                'input_lang',
                'input_webmaster_firstname',
                'input_webmaster_lastname',
                'input_webmaster_email',
                'input_admin_name',
                'input_admin_password1',
                'input_admin_password2'
            ];

            // Pflichtfelder prüfen
            requiredFields.forEach(function(elementName) {
                let value = $('#' + elementName).val();


                if (typeof value == 'undefined' || value.length == 0) {
                    emptyFieldLabels.push($('#' + elementName).attr('data-label'));
                }

            });

            if (emptyFieldLabels.length > 0) {
                valid = false;
                let out = 'Bitte füllen Sie folgende Felder aus:';
                emptyFieldLabels.forEach(function(label) {
                    out += "\n" + label;
                });
                alert(out);
            }

            // Passwort-Eingaben überprüfen
            let pw1 = $('#input_admin_password1').val();
            let pw2 = $('#input_admin_password2').val();
            if (pw1 != pw2) {
                valid = false;
                alert('Die beiden Passwörter stimmen nicht überein!');
            }

            return valid;
        });
    };

}
