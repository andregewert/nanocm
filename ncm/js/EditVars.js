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

function EditVars() {
    let app = this;

    app.removeRow = function (clickedElem) {
        let tr = $(clickedElem).parents('tr');
        $(tr).remove();
    };

    app.getValue = function () {
        let vars = {};
        $('#editvars_list').find('tr.entry').each(function () {
            var key = $(this).find('input.key').val();
            var value = $(this).find('input.value').val();
            if (key.length > 0) {
                vars[key] = value;
            }
        });
        let val = JSON.stringify(vars);
        if (val == '{}') val = '';
        return val;
    };

    app.addRow = function () {
        let table = $('#editvars_list').get(0);

        var row = document.createElement('tr');
        row.setAttribute('class', 'entry');
        var td, input;
        var image, link;

        td = document.createElement('td');
        input = document.createElement('input');
        input.setAttribute('type', 'text');
        input.setAttribute('class', 'key');
        input.setAttribute('style', 'width: 100%');
        td.appendChild(input);
        row.appendChild(td);

        td = document.createElement('td');
        input = document.createElement('input');
        input.setAttribute('type', 'text');
        input.setAttribute('class', 'value');
        input.setAttribute('style', 'width: 100%');
        td.appendChild(input);
        row.appendChild(td);

        td = document.createElement('td');
        td.setAttribute('style', 'text-align: center');
        link = document.createElement('a');
        link.setAttribute('href', 'javascript:void(0);');
        link.setAttribute('onclick', 'editVars.removeRow(this)');
        image = document.createElement('img');
        image.setAttribute('src', 'ncm/img/fatcow/16/delete.png');
        image.setAttribute('width', '16');
        image.setAttribute('height', '16');
        image.setAttribute('style', 'vertical-align: middle');
        link.appendChild(image);
        td.appendChild(link);
        row.appendChild(td);

        table.appendChild(row);

        app.getValue();
    };
}