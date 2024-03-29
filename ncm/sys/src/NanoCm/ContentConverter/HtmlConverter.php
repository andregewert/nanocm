<?php
/*
 * NanoCM
 * Copyright (C) 2017-2023 André Gewert <agewert@ubergeek.de>
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
use Exception;
use Ubergeek\Dictionary;
use Ubergeek\KeyValuePair;
use Ubergeek\MarkupParser\MarkupParser;
use Ubergeek\NanoCm\ContentConverter\Plugin\PluginInterface;
use Ubergeek\NanoCm\ContentConverter\Plugin\YoutubePluginOptions;
use Ubergeek\NanoCm\Exception\InvalidConfigurationException;
use Ubergeek\NanoCm\Module\AbstractModule;
use Ubergeek\NanoCm\Module\TemplateRenderer\TemplateRenderer;

/**
 * Generates (X)HTML code from NanoCM markup.
 *
 * This HTML converter class is bound to the NanoCM AbstractModule because it needs to translate URLs.
 * It can utilise plugins which has to be placed in the namespace Ubergeek\NanoCm\ContentConverter\Plugin. In the
 * future the loading mechanism should be more flexible, for example via explicit registration of plugins.
 * Plugins also require a NanoCM module as dependency.
 *
 * A plain markup parser without direct dependencies on NanoCM is emplemented in Ubergeek\MarkupParser\MarkupParser.
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-04
 */
class HtmlConverter {

    // <editor-fold desc="Properties">

    /**
     * Reference to the currently executed NanoCM module
     * @var AbstractModule
     */
    private AbstractModule $module;

    /**
     * @var PluginInterface[] Array with content converter plugins
     */
    private array $plugins;

    /**
     * Indicates if XHTML code should be generated (true) or HTML5 code (false).
     * @var bool
     */
    public bool $generateXhtml = false;

    // </editor-fold>


    // <editor-fold desc="Constructors">

    /**
     * The converter functions requires a reference to the current NanoCM module to create correct urls.
     *
     * @param AbstractModule $module
     */
    public function __construct(AbstractModule $module) {
        $this->module = $module;
        $this->plugins = self::loadAvailableContentPlugins();
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
        // Youtube links are the only remaining placeholders which are supported by this mechanism.
        // Other functionalities should be provided by the new, generic plugin mechanism.
        $output = preg_replace_callback(

            '/<p>\[(youtube):([^]]+?)]<\/p>$/im',

            /**
             * @throws Exception
             */
            static function($matches) use ($module) {
                switch (strtolower($matches[1])) {
                    case 'youtube':
                        if (preg_match('/v=([a-z0-9_\-]*)/i', $matches[2], $im) === 1) {
                            $options = new YoutubePluginOptions();
                            $options->videoId = $im[1];
                            $options->previewImageFormat =  $module->orm->getImageFormatByKey('ytthumb');
                            $renderer = new TemplateRenderer($module, true);
                            return $renderer->renderUserTemplate('blocks/plugin-youtube.phtml', $options);
                        }
                        return '';
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
                $arguments = new Dictionary();
                $plugin = $converter->getPluginByPlaceholder($placeholder);

                if ($plugin !== null && $plugin->isEnabled()) {
                    $supportedParameters = $plugin->getAvailableParameters();
                    foreach ($lines as $line) {
                        if (empty($line)) continue;
                        list($key, $value) = preg_split('/(\s*:\s*)/i', $line, 2);
                        $arguments->set($key, $value);

                        if (!in_array($key, array_keys($supportedParameters))) {
                            $module->warn("Parameter '$key' is not supported by plugin $placeholder");
                        }
                    }

                    // Fill default options
                    foreach (array_keys($supportedParameters) as $key) {
                        if (!in_array($key, $arguments->keys())) {
                            if ($supportedParameters[$key]->required || !empty($supportedParameters[$key]->default)) {
                                $arguments->set($key, $supportedParameters[$key]->default);
                            }
                        }
                    }

                    return $plugin->replacePlaceholder($module, $matches[0], $arguments);
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

    /**
     * Replaces single html tags with the short-closed xml form.
     *
     * For example, the tag <br> is replaced with the xml conforming <br /> tag.
     *
     * @param string $input
     * @return string
     */
    private function closeOpenSingleTags(string $input) : string {
        return preg_replace('/<(img|br|hr)([^>]*)([^\/])?>/i', "<$1$2$3 />", $input);
    }

    /**
     * Replaces named entities by the respective characters.
     *
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
     *
     * @param bool $includeDisabled Set to true if disabled plugins should be included.
     * @return PluginInterface[] List of loaded content converter plugins
     * @todo The properties isEnabled and priority should be set with values from some user configuration
     * @todo There should be ways to register plugins from other namespaces
     */
    public static function loadAvailableContentPlugins(bool $includeDisabled = false, bool $orderAlphabetically = false) : array {
        $plugins = array();
        $dirname = __DIR__ . DIRECTORY_SEPARATOR . 'Plugin';
        if (($dh = opendir($dirname)) !== false) {
            while (($entry = readdir($dh)) !== false) {
                if (preg_match('/Plugin\.php$/', $entry) === 1) {
                    $className = '\\Ubergeek\\NanoCm\\ContentConverter\\Plugin\\';
                    $className .= preg_replace('/\.php$/i', '', $entry);

                    try {
                        $pl = new $className();
                        if ($pl instanceof PluginInterface && ($includeDisabled || $pl->isEnabled())) {
                            if (array_key_exists($pl->getKey(), $plugins)) {
                                throw new InvalidConfigurationException('Plugin key ' . $pl->getKey() . ' already registered!');
                            }
                            $plugins[$pl->getKey()] = $pl;
                        }
                    } catch (Exception) {
                    }
                }
            }
        }

        uasort($plugins, static function(PluginInterface $a, PluginInterface $b) use ($orderAlphabetically) {
            if ($orderAlphabetically) {
                $r = strnatcasecmp($a->getName(), $b->getName());
                if ($r != 0) return $r;
            }

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
            if ($plugin->getKey() === $placeholder) return $plugin;
        }
        return null;
    }

    // </editor-fold>

}