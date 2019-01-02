<?php

/**
 * NanoCM
 * Copyright (C) 2017 - 2018 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\NanoCm\ContentConverter;
use Ubergeek\MarkupParser\MarkupParser;

/**
 * Konvertiert den mit Auszeichnungselementen versehenen Eingabe-String nach HTML
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-04
 */
class HtmlConverter extends DecoratedContentConverter {
    
    public function convertFormattedText(\Ubergeek\NanoCm\Module\AbstractModule $module, string $input, array $options = array()): string {

        if ($this->decoratedConverter !== null) {
            $input = $this->decoratedConverter->convertFormattedText($module, $input, $options);
        }

        // "Normales" Markup ersetzen
        $parser = new MarkupParser();
        foreach ($options as $key => $value) {
            if ($key == 'converter.html.idPrefix') {
                $parser->idPrefix = $value;
            }
        }
        $output = $parser->parse($input);

        // Erweiterte Platzhalter für die Medienverwaltung
        $output = preg_replace_callback('/\<p\>\[(youtube|album|image|download)\:([^\]]+?)\]\<\/p\>$/ims', function($matches) use ($module) {
            $module->setVar('converter.placeholder', $matches[0]);
            var_dump($matches);

            switch (strtolower($matches[1])) {
                // Youtube-Einbettungen (click-to-play)
                case 'youtube':
                    if (preg_match('/v=([a-z0-9_\-]*)/i', $matches[2], $im) === 1) {
                        $vid = $im[1];
                        $module->setVar('converter.youtube.vid', $vid);
                        return $module->renderUserTemplate('blocks/media-youtube.phtml');
                    }
                    return '';

                // Bildergalerie aus der Medienverwaltung
                case 'album':
                    $module->setVar('converter.album.id', intval($matches[2]));
                    return $module->renderUserTemplate('blocks/media-album.phtml');

                // Vorschaubild aus der Medienverwaltung
                case 'image':
                    list($id, $format) = explode(':', $matches[2], 2);
                    $module->setVar('converter.image.id', intval($id));
                    $module->setVar('converter.image.format', $format);
                    return $module->renderUserTemplate('blocks/media-image.phtml');

                // Download-Link aus der Medienverwaltung
                case 'download':
                    $module->setVar('converter.download.id', intval($matches[2]));
                    return $module->renderUserTemplate('blocks/media-download.phtml');
            }
            return $matches[0];
        }, $output);

        return $output;
    }

}