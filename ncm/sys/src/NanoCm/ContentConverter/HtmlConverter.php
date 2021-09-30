<?php

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

namespace Ubergeek\NanoCm\ContentConverter;
use Ubergeek\KeyValuePair;
use Ubergeek\MarkupParser\MarkupParser;
use Ubergeek\NanoCm\ContentConverter\Plugin\PluginInterface;
use Ubergeek\NanoCm\ContentConverter\Plugin\PluginParameter;
use Ubergeek\NanoCm\Module\AbstractModule;

/**
 * Generates (X)HTML code from NanoCM markup.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-04
 */
class HtmlConverter {

    // <editor-fold desc="Properties">

    /**
     * Reference to the currently executed NanoCM module
     * @var AbstractModule
     */
    private $module;

    /**
     * @var PluginInterface[] Array with content converter plugins
     */
    private $plugins;

    /**
     * Indicates if XHTML code should be generated (true) or HTML5 code (false).
     * @var bool
     */
    public $generateXhtml = false;

    // </editor-fold>


    // <editor-fold desc="Constructors">

    /**
     * The converter functions require a reference to the current NanoCM module to create correct urls.
     * @param AbstractModule $module
     */
    public function __construct(AbstractModule $module) {
        $this->module = $module;
        $this->plugins = $this->loadPlugins();
    }

    // </editor-fold>


    // </editor-fold desc="Public methods">

    public function convertFormattedText(string $input, array $options = array()): string {

        // Replace simple markup
        $parser = new MarkupParser();
        foreach ($options as $key => $value) {
            if ($key === 'converter.html.idPrefix') {
                $parser->idPrefix = $value;
            }
        }
        $output = $parser->parse($input);
        $module = $this->module;

        // Extended placeholders for media management
        $output = preg_replace_callback(

            '/<p>\[(youtube|album|image|download|twitter):([^]]+?)]<\/p>$/im',

            /**
             * @throws \Exception
             */
            static function($matches) use ($module) {
                $module->setVar($module::VAR_CONVERTER_PLACEHOLDER, $matches[0]);
                switch (strtolower($matches[1])) {

                    // Youtube-Einbettungen (click-to-play)
                    case 'youtube':
                        if (preg_match('/v=([a-z0-9_\-]*)/i', $matches[2], $im) === 1) {
                            $vid = $im[1];
                            $module->setVar($module::VAR_CONVERTER_YOUTUBE_VID, $vid);
                            return $module->renderUserTemplate('blocks/media-youtube.phtml');
                        }
                        return '';

                    // Bildergalerie aus der Medienverwaltung
                    case 'album':
                        $module->setVar($module::VAR_CONVERTER_ALBUM_ID, (int)$matches[2]);
                        return $module->renderUserTemplate('blocks/media-album.phtml');

                    // Vorschaubild aus der Medienverwaltung
                    case 'image':
                        list($id, $format) = explode(':', $matches[2], 2);
                        $module->setVar($module::VAR_CONVERTER_IMAGE_ID, (int)$id);
                        $module->setVar($module::VAR_CONVERTER_IMAGE_FORMAT, $format);
                        return $module->renderUserTemplate('blocks/media-image.phtml');

                    // Download-Link aus der Medienverwaltung
                    case 'download':
                        $module->setVar($module::VAR_CONVERTER_DOWNLOAD_ID, (int)$matches[2]);
                        return $module->renderUserTemplate('blocks/media-download.phtml');

                    // Eingebettete Tweets
                    case 'twitter':
                        $info = $module->ncm->mediaManager->getTweetInfoByUrl($matches[2]);
                        if ($info !== null) {
                            return preg_replace('/(<script[^>]*>[^>]*<\/script>)/i', '', $info->html);
                        }
                        return "<p>Einzubettenden Tweet nicht gefunden!</p>";
                }
                return $matches[0];
            },
            $output
        );

        // Replace placeholders by plugins
        $converter = $this;
        $output = preg_replace_callback('/(<p>)?\[pl:([^]]+)]([^\[]*?)(\[\/pl:[^]]+])(<\/p>)?/ims',

            static function($matches) use ($converter, $module) {
                $placeholder = $matches[2];
                $lines = preg_split('/<br\s*?\/?>/i', $matches[3]);
                $params = array();

                $plugin = $converter->getPluginByPlaceholder($placeholder);
                if ($plugin !== null && $plugin->isEnabled()) {

                    $availableParameters = $plugin->getAvailableParameters();

                    foreach ($lines as $line) {
                        if (empty($line)) continue;
                        list($key, $value) = preg_split('/(\s*:\s*)/i', $line, 2);
                        if (in_array($key, array_keys($availableParameters))) {
                            $param = $availableParameters[$key];
                            $param->value = $value;
                            $params[$key] = $param;
                        } else {
                            $module->warn("Parameter '$key' is not supported by plugin $placeholder");
                        }
                    }

                    return $plugin->replacePlaceholder($matches[0], $params);
                }
                return '';
            },
            $output
        );

        // Work around to create xhtml compatible code
        if ($this->generateXhtml) {
            $output = $this->closeOpenSingleTags($output);
            $output = $this->replaceNamedEntities($output);
        }

        return $output;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function closeOpenSingleTags(string $input) : string {
        return preg_replace('/<(img|br|hr)([^>]*)([^\/])?>/i', "<$1$2$3 />", $input);
    }

    /**
     * Replaces named entities by the respective characters.
     * @param string $input Input string
     * @return string Converted string
     */
    private function replaceNamedEntities(string $input) {
        $table = get_html_translation_table(HTML_ENTITIES, ENT_NOQUOTES);
        unset($table['<'], $table['>']);
        return str_replace(array_values($table), array_keys($table), $input);
    }

    /**
     * Loads available converter plugins.
     * @return PluginInterface[] List of loaded content converter plugins
     * @todo The properties isEnabled and priority should be set with values from some user configuration
     */
    private function loadPlugins() : array {
        $plugins = array();
        $dirname = __DIR__ . DIRECTORY_SEPARATOR . 'Plugin';
        if (($dh = opendir($dirname)) !== false) {
            while (($entry = readdir($dh)) !== false) {
                if (preg_match('/Plugin\.php$/', $entry) === 1) {
                    $className = '\\Ubergeek\\NanoCm\\ContentConverter\\Plugin\\';
                    $className .= preg_replace('/\.php$/i', '', $entry);

                    try {
                        $pl = new $className();
                        if ($pl instanceof PluginInterface) {
                            $pl->setModule($this->module);
                            // TODO Set isEnbaled / priority
                            $plugins[] = $pl;
                        }
                    } catch (\Exception $ex) {
                        $this->module->log->warn("Could not load content converter plugin", $ex);
                    }
                }
            }
        }

        usort($plugins, static function(PluginInterface $a, PluginInterface $b) {
            if ($a->getPriority() === $b->getPriority()) {
                return 0;
            }
            return ($a->getPriority() < $b->getPriority()) ? -1 : 1;
        });

        return $plugins;
    }

    /**
     * Returns the content converter plugin with the given placeholder or null
     * @param string $placeholder The placeholder
     * @return PluginInterface|null
     */
    private function getPluginByPlaceholder(string $placeholder) : ?PluginInterface {
        foreach ($this->plugins as $plugin) {
            if ($plugin->getPlaceholder() === $placeholder) return $plugin;
        }
        return null;
    }

    // </editor-fold>

}