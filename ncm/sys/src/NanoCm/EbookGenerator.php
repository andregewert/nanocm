<?php
/**
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

namespace Ubergeek\NanoCm;

use Ubergeek\Epub\Document;
use Ubergeek\Epub\Epub3Writer;
use Ubergeek\NanoCm\ContentConverter\ContentConverterInterface;
use Ubergeek\NanoCm\ContentConverter\DecoratedContentConverter;
use Ubergeek\NanoCm\ContentConverter\HtmlConverter;
use Ubergeek\NanoCm\Module\AbstractModule;
use Ubergeek\NanoCm\Module\CoreModule;
use Ubergeek\Net\Fetch;

/**
 * Kapselt Funktionen zum Erstellen von E-Books
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@gmail.com>
 * @created 2020-01-09
 */
class EbookGenerator {

    // <editor-fold desc="Internal properties">

    /**
     * Referenz auf die laufende NanoCM-Instanz
     * @var NanoCm
     */
    private $ncm;

    /**
     * @var CoreModule
     */
    private $module;

    /**
     * Liste der übersetzten URLs
     * @var MappedUrl[]
     */
    private $mappedUrls = array();

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct(AbstractModule $module) {
        $this->module = $module;
        $this->ncm = $module->ncm;
        //$this->contentConverter = new HtmlConverter($module);
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    public function createEpubForArticleWithId(int $id) {
        $article = $this->ncm->orm->getArticleById($id);
        if ($article != null) {
            return $this->createEpubForArticles(array($article), $article->headline);
        }
    }

    public function createEpubForArticleSeriesWithId(int $id) {
        // TODO implementieren
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Erstellt ein E-Book für die übergebenen Artikel mit angegebenem Titel und angegebener Kurzbeschreibung
     *
     * @param Article[] $articles Die zu verpackenden Artikel
     * @param string $title Titel für das E-Book
     * @param string $description Kurzbeschreibung für das E-Book
     * @return string E-Pub-Daten in String-Form
     * @throws \Exception
     */
    private function createEpubForArticles(array $articles, string $title = '', string $description = '') {

        // TODO Autorennamen automatisch "einsammeln"

        $mappedUrls = array();

        $writer = new Epub3Writer();
        $document = new Document();
        $document->title = $title;
        $document->description = $description;
        $document->language = $this->ncm->lang;
        $document->identifier = uniqid();

        foreach ($articles as $article) {
            $this->module->article = $article;
            $xhtml = $this->module->renderUserTemplate('epub-content.phtml');
            $xhtml = $this->replaceLinkedContents(
                $document, $xhtml, $mappedUrls
            );

            $this->replaceLinkedContents($document, $xhtml, $mappedUrls);

            $document->addContent(
                $document->createContentFromString(
                    $article->headline,
                    $this->createArticleFilename($article),
                    $xhtml
                )
            );

            echo $xhtml;
        }

        $document->addContentAtBeginning(
            $document->createTocContent('Inhalt')
        );

        $document->addContent(
            $document->createNcxContent('Inhalt')
        );

        return $writer->createDocumentFile($document);
    }

    private function createArticleFilename(Article $article) {
        return 'article-' . $article->id . '.xhtml';
    }

    /**
     * Versucht, verlinkte Inhalte (CSS-Dateien, andere Inhaltsseiten, Images etc.) zu ersetzen
     * @param string $content
     * @param $mappedUrls
     */
    private function replaceLinkedContents(Document $document, string $content, &$mappedUrls) {
        return preg_replace_callback('/((href=\"|src=\")([^\"]+)(\"))/i', function($matches) use ($document, $mappedUrls) {
            if (!$this->isExternalLink($matches[3])) {
                $sourceUrl = $matches[3];
                $targetUrl = $this->module->convUrlToAbsolute($sourceUrl);

                if (!array_key_exists($targetUrl, $mappedUrls)) {
                    $c = Fetch::fetchFromUrl($targetUrl);

                    if (!empty($c)) {

                        $mc = new MappedUrl();
                        $mc->originalUrl = $sourceUrl;
                        $mc->targetUrl = $targetUrl;
                        $mc->content = $c;
                        $mc->title = '';
                        $mc->mimeType = $this->guessMimeType(basename($targetUrl));
                        $mc->virtualUrl = basename($targetUrl);
                        $mappedUrls[$mc->targetUrl] = $mc;

                        $document->addContent(
                            $document->createContentFromStringWithType(
                                $mc->title,
                                $mc->virtualUrl,
                                $mc->content,
                                $mc->mimeType,
                                null,
                                null,
                                false
                            )
                        );
                        var_dump($mc);
                    }
                } else {
                    $mc = $mappedUrls[$targetUrl];
                }
                return $matches[2] . $mc->virtualUrl . $matches[4];
            }
            return $matches[1];
        }, $content);
    }

    private function isExternalLink($link) {
        return preg_match('/^([a-z]+\:)/i', $link) !== 0;
    }

    private function guessMimeType($filename) : string {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }

    // </editor-fold>
}
