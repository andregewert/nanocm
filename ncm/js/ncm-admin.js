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

/**
 * Diese zentrale Klasse stellt grundlegende Funktionen für den Administrationsbereich zur Verfügung
 *
 * @constructor
 */
function Ncm() {
    let app = this;

    /**
     * Blendet das globale Abblend-Element ein
     * @param {Node} blanker Das Blanker-Element
     */
    Ncm.prototype.showGlobalBlanker = function(blanker) {
//    app.showGlobalBlanker = function(blanker) {
        console.log('showGlobalBlanker');
        if (typeof blanker == 'undefined') {
            blanker = document.getElementById('globalblanker');
        }
        if (blanker != null) {
            blanker.style['width'] = '100%';
            blanker.style['height'] = '100%';
            blanker.style['top'] = '0';
            blanker.style['left'] = '0';
            blanker.style['display'] = 'block';
        }
    }

    /**
     * Blendet das globale Abblend-Element aus
     * @param blanker
     */
    app.hideGlobalBlanker = function(blanker) {
        if (typeof blanker == 'undefined') {
            blanker = document.getElementById('globalblanker');
        }
        if (blanker != null) {
            blanker.style['display'] = 'none';
        }
    }

    /**
     * Gibt true zurück, wenn das globale Abblend-Element sichtbar ist
     * @param blanker
     * @returns {boolean}
     */
    app.isGlobalBlankerOpen = function(blanker) {
        if (typeof blanker === 'undefined') {
            blanker = document.getElementById('globalblanker');
        }
        if (typeof blanker !== 'undefined' && blanker !== null) {
            return blanker.style['display'] === 'block';
        }
        return false;
    }

    /**
     * Blendet den Standard-Loading-Indicator ein
     */
    app.showDefaultLoadingIndicator = function() {
        $('#toolbar_spinner').addClass('loading');
        $('.placeholder').addClass('loading');
    };

    /**
     * Blendet den Standard-Loading-Indicator aus
     */
    app.hideDefaultLoadingIndicator = function() {
        $('#toolbar_spinner').removeClass('loading');
        $('.placeholder').removeClass('loading');
    };

    app.focusDefaultElement = function() {
        $('.autofocus').first().focus();
        $('.autofocus').first().select();
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

        $(toolbarElem).find('.edit_insert_char').click(function() {
            app.insertTextAtCaret(textareaElem[0], $(this).attr('data-char'));
        });

        $(toolbarElem).find('.edit_insert_video').click(function() {
            app.openInsertVideoLinkPopup(textareaElem[0]);
        });

        $(toolbarElem).find('.edit_insert_image').click(function() {
            let dlg = new app.InlinePopup('admin/media/html/imageselection', {
                param: 'param'
            }, {
                options: 'options',
                headline: 'Bild einfügen'
            }, {
                close: {
                    caption: 'Schließen',
                    clicked: function() {
                        dlg.close();
                    }
                }
            });
        });
    };

    /**
     * Öffnet das Dialog-Popup für das Einfügen von Videolinks
     * @param textArea Referenz auf die Textarea, in die der Link eingefügt werden soll
     */
    app.openInsertVideoLinkPopup = function(textArea) {
        let dlg = new app.InlinePopup('admin/media/html/insertvideolink', {
            param:  'param'
        }, {
            headline:   'Videolink einfügen',
            width:      500,
            height:     240,
            loaded:     function() {
                ncm.focusDefaultElement();
            }
        }, {
            cancel:  {
                caption:    'Abbrechen',
                clicked:    function() {
                    dlg.close();
                }
            },
            insert: {
                caption:    'Einfügen',
                clicked:    function() {
                    let url = $('#input_media_videolink').val();
                    app.insertTextAtCaret(textArea, "\n" + '[Youtube:' + url + ']' + "\n");
                    dlg.close();
                }
            }
        })
    };

    /**
     * Fügt der aktuellen Auswahl der angegebenen TextArea links und recht die angegebenen Strings hinzu
     * @param textArea
     * @param {string} contentBefore
     * @param {string} contentAfter
     */
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

    /**
     * Fügt einen String an der aktuellen Cursor-Position der angegebenen TextArea ein
     * @param textArea
     * @param {string} content
     */
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
     * Macht einen Container "draggable"
     * @param element Das zu modifizierende DOM-Element
     */
    app.makeDraggable = function(element) {
        var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }

        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            element.style.top = (element.offsetTop - pos2) + "px";
            element.style.left = (element.offsetLeft - pos1) + "px";
        }

        function closeDragElement() {
            document.onmouseup = null;
            document.onmousemove = null;
        }

        if ($(element).find('.headline').length > 0) {
            $(element).find('.headline').addClass('draggable');
            $(element).find('.headline').mousedown(dragMouseDown);
        } else {
            $(element).addClass('draggable');
            $(element).mousedown(dragMouseDown);
        }
    };

    /**
     * Öffnet ein Inline-Popup, dessen Inhalt per AJAX dynamisch geladen wird
     *
     * @param {string} url
     * @param params
     * @param options
     * @param buttonsRight
     * @param buttonsLeft
     * @returns {Ncm.InlinePopup}
     * @constructor
     */
    app.InlinePopup = function(url, params, options, buttonsRight, buttonsLeft) {
        let dlg = this;
        let dummy;
        //let ch;

        /**
         * Wird aufgerufen, wenn das Dialog-Fenster (tatsächlich) geschlossen worden ist
         * Rückfragen sollen ab hier *nicht* mehr erfolgen. Soll etwa der Close-Button
         * mit einer Rückfratge versehen werden, muss entsprechend die
         * closing-Option mit einer Callback-Funktion belegt werden.
         * @returns {void}
         */
        dlg._closed = function() {
            if (typeof options.closed === 'function') {
                options.closed();
            }
        };

        /**
         * Eine Aktion führt dazu, dass das Dialogfenster geschlossen werden soll
         * Wenn ein entsprechender Callback definiert ist und false zurückliefert,
         * so kann das Schließen des Fensters noch abgebrochen werden.
         * @returns {void}
         */
        dlg._closing = function(disableCallback) {
            var cont = true;
            if (typeof options.closing === 'function'
                && typeof disableCallback === 'undefined'
                && disableCallback !== true) {
                cont = options.closing() !== false;
            }

            if (cont) {
                document.body.removeChild(dlg.container);
                if (ncm.isGlobalBlankerOpen() && dlg._countOpenInlinePopups() === 0) {
                    ncm.hideGlobalBlanker();
                }
                dlg._closed();
                //delete dlg;
            }
        };

        dlg._loaded = function() {
            if (typeof options.loaded === 'function') {
                options.loaded();
            }
        };

        dlg._countOpenInlinePopups = function() {
            return $('div.inlinepopup').length;
        };

        dlg.close = function() {
            dlg._closing();
        };

        dlg.forceClose = function() {
            dlg._closing(true);
        };

        dlg.createButton = function(buttonDesc, type) {
            type = (type === 'left')? 'left' : 'right';
            var b = document.createElement('input');

            b.className = 'button ' + type;
            b.setAttribute('type', 'button');
            b.setAttribute('value', buttonDesc.caption);
            b.onclick = function() {
                if (typeof buttonDesc.clicked === 'function') {
                    buttonDesc.clicked(dlg);
                }
            }
            if (typeof buttonDesc.disabled !== 'undefined' && buttonDesc.disabled === true) {
                b.setAttribute('disabled', 'disabled');
            }
            return b;
        };

        if (typeof options !== 'object') {
            options = {};
        }

        if (typeof buttonsLeft !== 'object') {
            buttonsLeft = {};
        }

        if (typeof buttonsRight !== 'object') {
            buttonsRight = {};
        }

        options.width = (typeof options.width !== 'undefined')? parseInt(options.width, 10) : 700;
        options.height = (typeof options.height !== 'undefined')? parseInt(options.height, 10) : 500;
        dlg.loaded = false;
        //ch = options.height -60;

        dlg.container = document.createElement('div');
        dlg.container.Dialog = this;
        dlg.container.className = 'inlinepopup loading';
        dlg.container.style.width = options.width + 'px';
        dlg.container.style.height = options.height + 'px';

        dlg.controls = document.createElement('div');
        dlg.controls.className = 'controls';
        dlg.container.appendChild(dlg.controls);

        dlg.closeButton = document.createElement('a');
        dlg.closeButton.className = 'imgbutton nolabel';
        dlg.closeButton.setAttribute('href', 'javascript:void(0)');
        dlg.closeButton.setAttribute('title', 'Schließen');
        dlg.closeButton.onclick = function() {
            dlg.close();
        };
        dlg.controls.appendChild(dlg.closeButton);

        dummy = document.createElement('img');
        dummy.src = 'ncm/img/fatcow/16/cross.png';
        dummy.setAttribute('width', '16');
        dummy.setAttribute('height', '16');
        dummy.setAttribute('border', '0');
        dummy.setAttribute('alt', '[X]');
        dlg.closeButton.appendChild(dummy);

        // Headline / Titlebar initialisieren
        if (typeof options.headline !== 'undefined') {
            dlg.headline = document.createElement('div');
            dlg.headline.className = 'headline';
            dummy = document.createElement('h2');
            $(dummy).text(options.headline);
            dlg.headline.appendChild(dummy);
            dlg.container.appendChild(dlg.headline);
            //ch = ch -32;
        } else {
            dlg.headline = null;
        }

        // Inhalts-Container initialisieren
        dlg.content = document.createElement('div');
        dlg.content.className = 'popupcontent';
        dlg.content.style.overflow = 'auto';
        dlg.container.appendChild(dlg.content);

        // Button-Leiste
        dlg.buttonbar = document.createElement('div');
        dlg.buttonbar.className = 'buttons';
        const spacer = document.createElement('div');
        spacer.className = 'spacer';
        dlg.buttonbar.appendChild(spacer);

        var i = 0;

        for (var prop in buttonsLeft) {
            if (buttonsLeft.hasOwnProperty(prop)) {
                spacer.appendChild(
                    dlg.createButton(buttonsLeft[prop], 'left')
                );
                i++;
            }
        }

        for (var prop in buttonsRight) {
            if (buttonsRight.hasOwnProperty(prop)) {
                spacer.appendChild(
                    dlg.createButton(buttonsRight[prop], 'right')
                );
                i++;
            }
        }
        if (i > 0) {
            dlg.container.appendChild(dlg.buttonbar);
        }

        // Auf Escape-Taste reagieren
        dlg.container.onkeyup = function(e) {
            if (e.key == 'Escape') {
                dlg.close();
            }
        };

        document.body.appendChild(dlg.container);
        if (!ncm.isGlobalBlankerOpen()) ncm.showGlobalBlanker();

        // Initiale Position des Popups bestimmen
        let top = ($(window).height() - ($(window).height() / 4) - $(dlg.container).height()) / 2 + $(window).scrollTop();
        if (top < 0) top = 0;
        let left = ($(window).width() - $(dlg.container).width()) / 2 + $(window).scrollLeft();
        if (left < 0) left = 0;
        $(dlg.container).css("top", top + "px");
        $(dlg.container).css("left", left + "px");

        // Drag and drop initialisieren
        app.makeDraggable(dlg.container);

        // Inhalt laden
        $.ajax(url, {
            cache:          false,
            type:           'POST',
            dataType:       'html',
            data:           params
        } ).done(function(data) {
            $(dlg.content).html(data);
        } ).always(function() {
            $(dlg.container).removeClass('loading');
            dlg.loaded = true;
            dlg._loaded();
        } );

        // Referenz auf dieses Popup zurückgeben
        return dlg;
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
