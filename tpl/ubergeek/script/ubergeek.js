/*
 * NanoCM
 * Copyright (c) 2017 - 2021 André Gewert <agewert@ubergeek.de>
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

initUbergeek = function(baseurl) {

    // Lightbox
    lightbox.option({
        'fadeDuration':         200,
        'imageFadeDuration':    250,
        'resizeDuration':       250
    });

    // Syntax highlighting (Highlight.js)
    hljs.initHighlightingOnLoad();

    // Play-Icon on youtube previews
    $('div.video-preview img').each(function () {
        let div = document.createElement('img');
        div.setAttribute('class', 'overlay');
        div.setAttribute('src', baseurl + '/img/VideoPlay.png');
        $(div).insertBefore(this);
    });

    // Auto slideshow
    $('div.album').each(function () {
        let img = $(this).find('div.image');
        let count = img.length;
        let current = 0;
        let play = true;

        for (i = 1; i < count; i++) {
            $(img[i]).hide();
        }

        let showNext = function () {
            setTimeout(function () {
                if (play) {
                    let next = (current + 1) % count;
                    $(img[next]).fadeIn(2000);
                    $(img[current]).fadeOut(2000, showNext);
                    current = next;
                }
            }, 5000);
        };

        showNext();
    });

    // Clickable slideshows
    $('div.slideshow').each(function () {
        let img = $(this).find('div.image');
        let count = img.length;
        let current = 0;

        for (i = 1; i < count; i++) {
            $(img[i]).hide();
        }

        let switchImage = function (dir) {
            let nextIndex = current + dir;
            if (nextIndex < 0) nextIndex = count - 1;
            if (nextIndex + 1 > count) nextIndex = 0;
            $(img[current]).hide();
            $(img[nextIndex]).show();
            current = nextIndex;
            spanCurrentImage.innerText = current + 1;
        }

        let controls = document.createElement('div');
        controls.setAttribute('class', 'controls');

        let linkLeft = document.createElement('a');
        linkLeft.setAttribute('class', 'left');
        linkLeft.setAttribute('href', 'javascript:void(0)');
        linkLeft.onclick = function () {
            switchImage(-1);
        };
        let imgLeft = document.createElement('img');
        imgLeft.setAttribute('src', baseurl + '/img/left-white.gif');
        imgLeft.setAttribute('alt', '&larr;');
        imgLeft.setAttribute('title', 'Vorheriges Bild');
        imgLeft.setAttribute('width', '9');
        imgLeft.setAttribute('height', '17');
        linkLeft.appendChild(imgLeft);
        controls.appendChild(linkLeft);

        controls.appendChild(document.createTextNode('Bild '));
        let spanCurrentImage = document.createElement('span');
        spanCurrentImage.appendChild(document.createTextNode(current + 1));
        controls.appendChild(spanCurrentImage);
        controls.appendChild(document.createTextNode(' von ' + count));

        let linkRight = document.createElement('a');
        linkRight.setAttribute('class', 'right');
        linkRight.setAttribute('href', 'javascript:void(0)');
        linkRight.onclick = function () {
            switchImage(1);
        }
        let imgRight = document.createElement('img');
        imgRight.setAttribute('src', baseurl + '/img/right-white.gif');
        imgRight.setAttribute('alt', '&rarr;');
        imgRight.setAttribute('title', 'Nächstes Bild');
        imgRight.setAttribute('width', '9');
        imgRight.setAttribute('height', '17');
        linkRight.appendChild(imgRight);
        controls.appendChild(linkRight);

        $(controls).insertBefore($(this).find('div.frame'));
    });
}
