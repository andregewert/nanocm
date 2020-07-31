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

function Basicsettings() {
    let app = this;

    app.init = function() {
        $('#button_save').click(function() {
            app.saveBasicSettings();
        });
    };

    app.saveBasicSettings = function() {
        ncm.showDefaultLoadingIndicator();

        $.ajax('admin/basicsettings/ajax/savesettings', {
            cache:      false,
            type:       'post',
            dataType:   'json',
            data:       {
                settings:   {
                    'system.pagetitle':         $('#input_system_pagetitle').val(),
                    'system.lang':              $('#input_system_lang').val(),
                    'system.copyrightnotice':   $('#input_system_copyrightnotice').val(),
                    'system.webmaster.name':    $('#input_system_webmaster_name').val(),
                    'system.webmaster.email':   $('#input_system_webmaster_email').val(),
                    'system.webmaster.url':     $('#input_system_webmaster_url').val(),
                    'system.admin.pagelength':  $('#input_system_admin_pagelength').val(),
                    'system.enablecomments':    $('#input_system_enablecomments').prop('checked')? 1 : 0,
                    'system.enabletrackbacks':  $('#input_system_enabletrackbacks').prop('checked')? 1 : 0,
                    'system.template.path':     $('#input_system_template_path').val()
                }
            }
        }).done(function(data) {
            // TODO Handle response codes
        }).always(function() {
            ncm.hideDefaultLoadingIndicator();
        });
    };

    app.init();
}
