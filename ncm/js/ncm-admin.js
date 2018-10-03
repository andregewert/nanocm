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
    let app = this;

    app.showDefaultLoadingIndicator = function() {
        $('#toolbar_spinner').addClass('loading');
        $('.placeholder').addClass('loading');
    };

    app.hideDefaultLoadingIndicator = function() {
        $('#toolbar_spinner').removeClass('loading');
        $('.placeholder').removeClass('loading');
    };

    app.toggleAllRowsSelection = function(headerCheckbox) {
        $(headerCheckbox).parents('table').find('input[type=checkbox]').prop(
            'checked',
            $(headerCheckbox).prop('checked')
        );
    };

    app.getFirstSelectedRowId = function() {
        var elem = $('table.list').find('input.selection:checked').first();
        if (elem.length == 1) {
            return elem.val();
        }
        return null;
    };

    app.getSelectedRowIds = function() {
        var ids = [];
        $('table.list').find('input.selection:checked').each(function() {
            ids.push($(this).val());
        });
        console.log(ids);
        return ids;
    };

    /**
     * Initialisiert einen TextEditor, indem innerhalb der Toolbar-Elementes die passenden EventHandler
     * gesetzt werden.
     * @param toolbarElem Element mit den Toolbar-Buttons für den Text-Editor (als jQuery-Objekt)
     * @param textareaElem Textarea-Element (als jQuery-Objekt)
     */
    app.initTextEditor = function(toolbarElem, textareaElem) {
        // TODO Vervollständigen!
        $(toolbarElem).find('.edit_bold').click(function() {
            app.surroundSelectionWith(textareaElem[0], '**', '**');
        });

        $(toolbarElem).find('.edit_italic').click(function() {
            app.surroundSelectionWith(textareaElem[0], '*', '*');
        });

        $(toolbarElem).find('.edit_underline').click(function() {
            app.surroundSelectionWith(textareaElem[0], '_', '_');
        });

        $(toolbarElem).find('.edit_strikethrough').click(function() {
            app.surroundSelectionWith(textareaElem[0], '~', '~');
        });

        $(toolbarElem).find('.edit_superscript').click(function() {
            app.surroundSelectionWith(textareaElem[0], '^', '^');
        });

        $(toolbarElem).find('.edit_subscript').click(function() {
            app.surroundSelectionWith(textareaElem[0], '°', '°');
        });

        $(toolbarElem).find('.edit_caps').click(function() {
            app.surroundSelectionWith(textareaElem[0], '|', '|');
        });

        $(toolbarElem).find('.edit_hr').click(function() {
            app.insertTextAtCaret(textareaElem[0], '\n---\n');
        });
    };

    app.surroundSelectionWith = function(textArea, contentBefore, contentAfter) {
        textArea.focus();

        if (textArea.setSelectionRange) {
            var c = textArea.scrollTop;
            var e = textArea.selectionStart;
            var f = textArea.selectionEnd;

            textArea.value = textArea.value.substring(0, textArea.selectionStart)
                + contentBefore
                + textArea.value.substring(textArea.selectionStart, textArea.selectionEnd)
                + contentAfter
                + textArea.value.substring(textArea.selectionEnd, textArea.value.length);
            textArea.selectionStart = e;
            textArea.selectionEnd = f + contentBefore.length + contentAfter.length;
            textArea.scrollTop = c;
        } else {
            if (document.selection && document.selection.createRange) {
                textArea.focus();
                var b = document.selection.createRange();
                if (b.text != "") {
                    b.text = contentBefore + b.text + contentAfter;
                } else {
                    //b.text = contentBefore + "REPLACE" + contentAfter;
                    console.log('???');
                }
                textArea.focus();
            }
        }
    };

    app.insertTextAtCaret = function(textArea, content) {
        // IE
        if (document.selection) {
            textArea.focus();
            var sel = document.selection.createRange();
            sel.text = content;
        }

        // Others
        else if (textArea.selectionStart || textArea.selectionStart === '0') {
            var startPos = textArea.selectionStart;
            var endPos = textArea.selectionEnd;
            textArea.value = textArea.value.substring(0, startPos) + content + textArea.value.substring(endPos, textArea.value.length);
            textArea.focus();
            textArea.selectionStart = startPos + content.length;
            textArea.selectionEnd = startPos + content.length;
        }
        else {
            textArea.value += content;
            textArea.focus();
        }
    };

    /**
     * Verbindet Toolbar und Textarea miteinander, indem auf den Toolbar-
     * Elementen die benötigten EventHandler gesetzt werden
     * @param textArea
     * @param toolbarContainer
     */
    app.createEditorToolbar = function(textArea, toolbarContainer) {

    }

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
var module;
