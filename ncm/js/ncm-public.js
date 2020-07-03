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

function NcmPublic(baseUrl) {

    let app = this;

    app.baseUrl = baseUrl;

    app.container = null;

    app.initCommentForm = function(container) {
        app.container = container;
        $(app.container).find('.submit').click(function() {
            app.commentButtonClicked();
        });
    };

    app.tryToSendComment = function() {
        let matches = window.location.href.match(/article\/(\d+)/i);
        if (matches === null) return;
        let articleId = parseInt(matches[1], 10);

        $(app.container).find('.captcha_container span.checkbox span').show(400, function() {
            $(app.container).find('.captcha_container .captcha').addClass('loading');
        });

        $.ajax(app.baseUrl + 'ajax/captcha', {
            cache:      false,
            type:       'GET',
            dataType:   'JSON'
        }).done(function(data) {
            var solution = 0;
            if (data.operator == '+') {
                solution = parseInt(data.operand1, 10) +parseInt(data.operand2, 10);
            } else {
                solution = parseInt(data.operand1, 10) -parseInt(data.operand2, 10);
            }

            let commentData = {
                cpsid:      data.captchaId,
                sc:         solution,
                _a:         articleId,
                _n:         $('#input_n').val(),
                _e:         $('#input_e').val(),
                _g:         ($('#input_g').prop('checked'))? '1' : '0',
                _h:         $('#input_h').val(),
                _t:         $('#input_t').val()
            };

            $.ajax(app.baseUrl + 'ajax/comment', {
                cache:      false,
                type:       'POST',
                dataType:   'JSON',
                data:       commentData
            }).done(function(data) {
                $(app.container).find('.captcha_container .captcha').remove();
                if (data.status == 0) {
                    var url = window.location.href;
                    if (url.match(/#comment/i) === null) {
                        url += '#comment-' + data.comment.id;
                    }
                    window.location.href = url;
                    window.location.hash = 'comment-' + data.comment.id;
                    window.location.reload(true);
                } else {
                    $(app.container).find('.submit').show(400, function() {
                        alert(data.message);
                    });
                }
            });
        });
    };

    app.commentButtonClicked = function() {
        // Kommentar-Button ausblenden und Captcha einblenden
        $(app.container).find('.submit').hide(400, function() {
            $(app.container).find('.captcha_container'). append("<div class=\"captcha\">\n" +
                "                            <span class=\"checkbox\"><span>&times;</span></span>\n" +
                "                            <span class=\"label\">Ich bin kein Roboter</span>\n" +
                "                        </div>");
            $(app.container).find('.captcha_container span.checkbox').click(function() {
                app.tryToSendComment();
            });
        });
    };

}
