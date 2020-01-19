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
use Ubergeek\NanoCm\Module\AbstractModule;
use Ubergeek\Net\Fetch;

/**
 * Konvertiert den mit Auszeichnungselementen versehenen Eingabe-String nach HTML
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-04
 */
class HtmlConverter extends DecoratedContentConverter {

    /**
     * Referenz auf das aktuell ausgeführte TinyCM-Modul
     * @var Ubergeek\NanoCm\Module\AbstractModule
     */
    private $module;

    /**
     * Gibt an, ob in der Ausgabe XHTML generiert werden soll (true) oder HTML5 (false)
     * @var bool
     */
    public $generateXhtml = false;

    /**
     * Der HTML-Converter ist zum korrekten Erzeugen von URLs etc. auf eine Refeferenz
     * auf das aktuell ausgeführte TinyCM-Modul angewiesen.
     *
     * @param AbstractModule $module
     * @param ContentConverterInterface $decoratedConverter Der zu dekorierende Content-Converter
     */
    public function __construct(AbstractModule $module, ContentConverterInterface $decoratedConverter = null) {
        parent::__construct($decoratedConverter);
        $this->module = $module;
    }

    public function convertFormattedText(string $input, array $options = array()): string {

        if ($this->decoratedConverter !== null) {
            $input = $this->decoratedConverter->convertFormattedText($input, $options);
        }

        // "Normales" Markup ersetzen
        $parser = new MarkupParser();

        foreach ($options as $key => $value) {
            if ($key == 'converter.html.idPrefix') {
                $parser->idPrefix = $value;
            }
        }
        $output = $parser->parse($input);
        $module = $this->module;

        // Erweiterte Platzhalter für die Medienverwaltung
        $output = preg_replace_callback('/\<p\>\[(youtube|album|image|download|twitter)\:([^\]]+?)\]\<\/p\>$/ims', function($matches) use ($module) {
            $module->setVar('converter.placeholder', $matches[0]);

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

                // Eingebettete Tweets
                case 'twitter':
                    $info = $module->ncm->mediaManager->getTweetInfoByUrl($matches[2]);
                    if ($info != null) {
                        return preg_replace('/(\<script[^\>]*\>[^\>]*\<\/script\>)/i', '', $info->html);
                    } else {
                        return "<p>Einzubettenden Tweet nicht gefunden!</p>";
                    }
                    break;
            }
            return $matches[0];
        }, $output);

        // Notlösung, um nachträglich XHTML-kompatiblen Output zu erzwingen ...
        if ($this->generateXhtml) {
            if (function_exists('tidy_repair_string')) {
                $output = tidy_repair_string($output, array(
                    'output-xml' => true
                ));
            } else {
                $output = $this->closeOpenSingleTags($output);
                $output = $this->replaceNamedEntities($output);
            }
        }

        return $output;
    }

    // <editor-fold desc="Internal methods">

    private function closeOpenSingleTags(string $input) : string {
        return preg_replace('/<(img|br|hr)([^>]*)([^\/])?>/i', "<$1$2$3 />", $input);
    }

    private function replaceNamedEntities(string $input) {
        return html_entity_decode($input, ENT_NOQUOTES);
    }

    // </editor-fold>

}