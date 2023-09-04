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

namespace Ubergeek\NanoCm\Module\TemplateRenderer;

use Exception;
use Ubergeek\Dictionary;
use Ubergeek\NanoCm\Constants;
use Ubergeek\NanoCm\ContentConverter\Plugin\YoutubePluginOptions;
use Ubergeek\NanoCm\Exception\InvalidArgumentException;
use Ubergeek\NanoCm\Medium;
use Ubergeek\NanoCm\Module\AbstractModule;
use Ubergeek\NanoCm\Module\TemplateRenderer\Exception\TemplateNotFoundException;
use Ubergeek\NanoCm\Util;

/**
 * This class should replace existing template rendering method which are placed in the Module-Classes.
 * Another task of this class will be - in the future - to replace placeholders with localized strings.
 * This class and it's usage are work in progress.
 * @created 2023-09-04
 * @author André Gewert <agewert@ubergeek.de>
 */
class TemplateRenderer
{

    /**
     * Reference to the calling nano cm module.
     * @var AbstractModule
     */
    private AbstractModule $callingModule;

    /**
     * Specifies if user defined templates are allowed.
     * The administration modules for example should NOT use templates from user defined directories, but
     * the provided admin templates only.
     * @var bool
     */
    private bool $allowUserTemplates = true;

    /**
     * Specifies if placeholders should be replaced by localized strings.
     * This feature is not ready yet and should be enabled in the future.
     * @var bool
     */
    private bool $enableLocalization = false;

    /**
     * specifies the language which should be used for loading localized strings.
     * If the specified language is not found, the fallback language should be English (en).
     * If no file with string definitions can be found, the placeholders will be replaced by marked up
     * error messages.
     * @var string
     */
    private string $lang = 'de';

    /**
     * Specifies the target format: HTML or XHTML
     * @var string
     */
    private string $targetFormat = Constants::FORMAT_HTML;

    /**
     * The options which should be passed to the template.
     * @var RendererOptions
     */
    public RendererOptions $options;

    public function __construct(AbstractModule $callingModule, bool $allowUserTemplates = true) {
        $this->callingModule = $callingModule;
        $this->allowUserTemplates = $allowUserTemplates;
    }

    /**
     * Renders the requested template file and returns the rendered content as a string.
     * @param string $file Template file (relative path)
     * @param RendererOptions|null $options Options to be passed to the template
     * @return string The rendered contents
     */
    public function renderUserTemplate(string $file, RendererOptions $options = null): string {
        $content = '';
        $ncm = $this->callingModule->ncm;
        $this->options = $options;

        $filename = Util::createPath($ncm->tpldir, $file);
        if ($this->allowUserTemplates && !file_exists($filename)) {
            $filename = Util::createPath($ncm->pubdir, 'tpl', 'default', $file);
        }

        if (!file_exists($filename)) {
            throw new TemplateNotFoundException("Template not found: $file");
        }

        // Executing template file
        ob_start();
        try {
            include($filename);
            $content = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        // TODO Localization
        //if ($this->enableLocalization) {
        //  Load string definitions
        //  Replace placeholders with definitions or error messages
        //}

        return $content;
    }

    /**
     * Sets the language code (two character ISO code) for the localization feature, if enabled.
     * @param string $language The two character ISO code for the desired language.
     * @return void
     */
    public function setLanguage(string $language) {
        $this->lang = $language;
    }

    /**
     * Sets the desired target format (html or xhtml)
     * @param string $targetFormat
     * @return void
     */
    public function setTargetFormat(string $targetFormat) {
        $this->targetFormat = $targetFormat;
    }

    // <editor-fold desc="Converting methods">

    /**
     * Encodes special characters within the given string for html output.
     * @param string $string The string to be encoded.
     * @param string|null $overrideTargetFormat Optional: overrides the target format (html or xhtml)
     * @return string The encoded (x)html string
     */
    public function htmlEncode(string $string, string $overrideTargetFormat = null) : string {
        $format = $overrideTargetFormat ?? $this->targetFormat;
        return Util::htmlEncode($string, $format);
    }

    /**
     * Converts an installation relative URL to an aboslute one.
     * @param string $relativeUrl
     * @return string
     */
    public function convUrl(string $relativeUrl): string {
        if (preg_match('/^[a-z]+:/i', $relativeUrl)) return $relativeUrl;
        $url = $this->callingModule->ncm->relativeBaseUrl;
        if ($relativeUrl[0] === '/') {
            $relativeUrl = substr($relativeUrl, 1);
        }
        return $url . $relativeUrl;
    }

    /**
     * Converts an installation relative URL to an absolute one and returns it HTML encoded.
     * @param string $relativeUrl
     * @return string
     */
    public function htmlConvUrl(string $relativeUrl) : string {
        return $this->htmlEncode($this->convUrl($relativeUrl));
    }

    /**
     * Returns an installation relative URL for a specific image file (from media manager).
     * @param Medium $medium Media file
     * @param string $formatKey Key for the requested image format
     * @param integer $scaling Scaling factor
     * @return string Relative URL to the preview image
     */
    public function getImageUrl(Medium $medium, string $formatKey, int $scaling = 1): string {
        return $medium->getImageUrl($formatKey, $scaling);
    }

    /**
     * Replaces newlines and / or carriage return by <br> tags.
     * @param string $string Input string
     * @return string String with replaced newlines / carriage returns
     */
    public function nl2br(string $string) : string {
        $string = preg_replace('/(\n|\r\n|\n\r)/i', "<br>", $string);
        return($string);
    }

    /**
     * Creates the link for a youtube preview image generated by NanoCM.
     * This link is relative to the installation directory of Nano CM and has to be converted within
     * the output template with htmlConvUrl() or convUrl().
     * @param string $youtubeId Youtube video id
     * @param string $formatKey ImageFormat key
     * @return string Link for a cached youtube preview image
     */
    public function getYoutubeThumbnailUrl(string $youtubeId, string $formatKey) {
        return "/media/$youtubeId/yt/$formatKey";
    }

    // </editor-fold>


    // <editor-fold desc="Wrapping methods">

    /**
     * Converts a string containing simple formatting only into an HTML encoded string.
     * @param string $input
     * @return string
     * @throws Exception
     */
    public function convertTextWithBasicMarkup(string $input) : string {
        switch ($this->targetFormat) {
            case Constants::FORMAT_HTML:
            case Constants::FORMAT_XHTML:
                if ($this->targetFormat === Constants::FORMAT_XHTML) {
                    $output = htmlentities($input, ENT_COMPAT | ENT_XHTML | ENT_SUBSTITUTE, 'UTF-8', false);
                } else {
                    $output = htmlentities($input, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
                }

                $output = $this->nl2br($output);
                $output = str_replace(array("'", ' ...', '...', ' -- '), array('&rsquo;', '&nbsp;&hellip;', '&hellip;', '&nbsp;&ndash; '), $output);
                $output = preg_replace('/&quot;(.+?)&quot;/i', '&bdquo;$1&ldquo;', $output);
                $output = preg_replace('/_(.+?)_/', '<em>$1</em>', $output);
                $output = preg_replace('/\*(.+?)\*/', '<strong>$1</strong>', $output);
                $output = preg_replace('/\(c\)/i', '&copy;', $output);

                // URL replacement including youtube previews
                $module = $this->callingModule;
                $output = preg_replace_callback('/(https?:\/\/([^\s<>]+))/i', static function($matches) use ($module) {
                    if (preg_match('/(www\.)?youtube\.com\/watch\?v=(\w+)/i', $matches[2], $innerMatches)) {
                        $options = new YoutubePluginOptions();
                        $options->videoId = $innerMatches[2];
                        $options->previewImageFormat =  $module->orm->getImageFormatByKey('ytthumb');
                        $renderer = new TemplateRenderer($module, true);
                        return $renderer->renderUserTemplate('blocks/plugin-youtube.phtml', $options);
                    }
                    return '<a href="' . $matches[1] . '" target="_blank">' . $matches[2] . '</a>';
                }, $output);

                $output = preg_replace('/<\/div>\s?<br>/', '</div>', $output);
                $output = trim($output);
                break;

            default:
                throw new InvalidArgumentException("Unsupported target format: $this->targetFormat");
        }

        return $output;
    }

    /**
     * Returns the download link for a file from media manager.
     * The returned URL is relative to the installation of Nano CM and should be converted to an absolute URL
     * by convUrl() or htmlConvUrl().
     * @param Medium $medium The medium to be linked.
     * @return string Installation relative download link
     */
    public function getDownloadUrl(Medium $medium): string {
        return '/media/' . $medium->hash . '/download/';
    }

    // </editor-fold>

}