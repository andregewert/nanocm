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
     * Referenz auf das aufrufende NanoCM-Modul
     * @var CoreModule
     */
    private $module;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct(AbstractModule $module) {
        $this->module = $module;
        $this->ncm = $module->ncm;
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Erstellt ein E-Book im ePub-Format und gibt dieses in Form eines Strings zurück
     *
     * @param int $id Artikel-ID
     * @return string Generierte ePub-Datei in einem String
     * @throws \Exception
     */
    public function createEpubForArticleWithId(int $id) {
        $article = $this->ncm->orm->getArticleById($id);
        if ($article == null) {
            // TODO Exception werfen
        }
        return $this->createEpubForArticles(array($article), $article->headline);
    }

    /**
     * Erstellt ein E-Book im ePub-Format für eine Artikelserie und gibt dieses in Form eines
     * Strings zurück
     *
     * @param int $id ID der Artikelserie
     * @return string Generierte ePub-Datei in einem String
     * @throws \Exception
     */
    public function createEpubForArticleSeriesWithId(int $id) {
        $series = $this->ncm->orm->getArticleseriesById($id);
        $articles = array();

        if ($series == null) {
            // TODO Exception werfen
        }

        // TODO Artikel pro Serie auslesen

        return $this->createEpubForArticles($articles,$series->title, $series->description);
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Erstellt ein E-Book für die übergebenen Artikel mit angegebenem Titel und angegebener Kurzbeschreibung
     *
     * @param Article[] $articles Die zu verpackenden Artikel
     * @param string $title Titel für das E-Book
     * @param string $description Kurzbeschreibung für das E-Book
     * @param string $tocTitle Überschrift für das automatisch generierte Inhaltsverzeichnis
     * @return string E-Pub-Daten in String-Form
     * @throws \Exception
     */
    private function createEpubForArticles(array $articles, string $title = '', string $description = '', string $tocTitle = 'Inhalt') {
        $mappedUrls = array();

        $writer = new Epub3Writer();
        $document = new Document();
        $this->module->ebook = $document;

        $document->title = $title;
        $document->description = $description;
        $document->language = $this->ncm->lang;
        $document->identifier = uniqid();

        // Titelseite, wenn mehr als ein Artikel enthalten ist
        if (count($articles) > 0) {
            $this->module->articles = $articles;
            $xhtml = $this->module->renderUserTemplate('epub-cover.phtml');
            $xhtml = $this->replaceLinkedContents(
                $document, $xhtml, $mappedUrls
            );
            $document->addContent(
                $document->createContentFromString(
                    $document->title,
                    'title.phtml',
                    $xhtml
                )
            );
        }

        // Einzelartikel
        foreach ($articles as $article) {
            $this->module->article = $article;
            $xhtml = $this->module->renderUserTemplate('epub-article.phtml');
            $xhtml = $this->replaceLinkedContents(
                $document, $xhtml, $mappedUrls
            );

            $document->addContent(
                $document->createContentFromString(
                    $article->headline,
                    $this->createArticleFilename($article),
                    $xhtml
                )
            );
        }

        // Inhaltsverzeichnis
        $document->addContentAtBeginning(
            $document->createTocContent($tocTitle)
        );

        $document->addContent(
            $document->createNcxContent($tocTitle)
        );

        return $writer->createDocumentFile($document);
    }

    private function createArticleFilename(Article $article) {
        return 'article-' . $article->id . '.xhtml';
    }

    /**
     * Versucht, verlinkte Inhalte (CSS-Dateien, andere Inhaltsseiten, Images etc.) zu ersetzen
     *
     * @param Document $document Das E-Book-Dokument
     * @param $mappedUrls Referenz auf die gemappten URLs
     * @return string Der modifizierte Inhalt
     */
    private function replaceLinkedContents(Document $document, string $content, &$mappedUrls) {
        return preg_replace_callback('/((href=\"|src=\")([^\"]+)(\"))/i', function($matches) use ($document, $mappedUrls) {
            if ($this->isAnchorLink($matches[3])) {
                return $matches[1];
            }

            if (!$this->isExternalLink($matches[3])) {
                $sourceUrl = $matches[3];
                $targetUrl = $this->module->convUrlToAbsolute($sourceUrl);
            } else {
                $sourceUrl = $matches[3];
                $targetUrl = $matches[3];
            }

            $mappedContent = null;
            if (array_key_exists($targetUrl, $mappedUrls)) {
                $mappedContent = $mappedUrls[$targetUrl];
            } elseif ($this->isEmbeddableContent($targetUrl)) {
                $c = Fetch::fetchFromUrl($targetUrl);

                if (!empty($c)) {
                    $mappedContent = new MappedUrl();
                    $mappedContent->originalUrl = $sourceUrl;
                    $mappedContent->targetUrl = $targetUrl;
                    $mappedContent->content = $c;
                    $mappedContent->title = '';
                    $mappedContent->mimeType = $this->guessMimeType(basename($targetUrl));
                    $mappedContent->virtualUrl = basename($targetUrl);
                    $mappedUrls[$mappedContent->targetUrl] = $mappedContent;

                    $document->addContent(
                        $document->createContentFromStringWithType(
                            $mappedContent->title,
                            $mappedContent->virtualUrl,
                            $mappedContent->content,
                            $mappedContent->mimeType,
                            null,
                            null,
                            false
                        )
                    );
                }
            }

            if ($mappedContent !== null) {
                return $matches[2] . $mappedContent->virtualUrl . $matches[4];
            }

            return $matches[2] . $targetUrl . $matches[4];
        }, $content);
    }

    /**
     * Überprüft, ob der Link einen Anker bezeichnet
     *
     * @param $link Der zu prüfende Link
     * @return bool true, wenn der Link (nur) auf einen Anker zeigt
     */
    private function isAnchorLink($link) {
        return substr($link, 0, 1) == '#';
    }

    /**
     * Überprüft, ob es sich beim übergebenen Link um einen externen Link handelt
     *
     * Die Überprüfung beschränkt sich darauf, ob der Link mit einer Protokollangabe
     * (HTTP, HTTPS, MAILTO etc.) beginnt.
     * @param $link Der zu prüfende Link
     * @return bool true, wenn es sich um einen externen Link handelt
     */
    private function isExternalLink($link) {
        return preg_match('/^([a-z]+\:)/i', $link) !== 0;
    }

    /**
     * Überprüft, ob es sich bei dem hinter dem übergebenen URL liegenden Inhalt
     * um einen in das E-Book einbettbaren Inhalt handelt
     *
     * @param $url Der zu prüfende URL
     * @return bool true, wenn es sich um einen einbettbaren Inhalt handelt
     */
    private function isEmbeddableContent($url) {
        $embeddable = array(
            'text/css',
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
        );

        $type = Fetch::getContentTypeForUrl($url);
        if ($type != null) {
            $type = trim(explode(';', $type)[0]);
        }

        return in_array($type, $embeddable);
    }

    /**
     * Versucht, den MIME-Type anhand eines Dateinames zu ermitteln
     *
     * @param $filename Der zu prüfende Dateiname
     * @return string MIME-Type
     */
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
