<?php
// NanoCM
// Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

namespace Ubergeek\NanoCm;

use Exception;
use Ubergeek\Cache\CacheInterface;
use Ubergeek\Epub\Document;
use Ubergeek\Epub\Epub3Writer;
use Ubergeek\Log\LoggerInterface;
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

    /**
     * Optionale Referenz auf einen Logger
     * @var LoggerInterface
     */
    private $log;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct(AbstractModule $module, $log = null) {
        $this->module = $module;
        $this->ncm = $module->ncm;
        $this->log = $log;
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Erstellt eine E-Pub-Datei für den übergebenen Artikel
     *
     * Diese Methode verwendet den in der NCM-Instanz konfigurierten E-Book-Cache, falls vorhanden!
     * Hierbei sollte es sich im Normalfall um einen langfristigen (mehrtägigen) Cache handeln.
     *
     * @param Article $article Der als E-Pub zu schreibende Artikel
     * @return string|null E-Pub-Inhalt in Form eines String
     * @throws Exception Bei einem Fehler
     */
    public function createEpubForArticle(Article $article): ?string {
        $cacheKey = 'ebook-article-' . $article->id;
        //$ebook = $this->getContentFromEbookCache($cacheKey);
        $ebook = null;
        if ($ebook === null) {
            $ebook = $this->createEpubForArticles(array($article), $article->headline);
            $this->putContentToEbookCache($cacheKey, $ebook);
        }
        return $ebook;
    }

    /**
     * Erstellt ein E-Book im ePub-Format für eine Artikelserie und gibt dieses in Form eines
     * Strings zurück
     *
     * Diese Methode verwendet den in der NCM-Instanz konfigurierten E-Book-Cache, falls vorhanden!
     * Hierbei sollte es sich im Normalfall um einen langfristigen (mehrtägigen) Cache handeln.
     *
     * @param int $id ID der Artikelserie
     * @return string Generierte ePub-Datei in einem String
     * @throws Exception
     */
    public function createEpubForArticleSeriesWithId(int $id) {
        $cacheKey = 'ebook-series-' . $id;
        $ebook = $this->getContentFromEbookCache($cacheKey);

        if ($ebook === null) {
            $series = $this->ncm->orm->getArticleseriesById($id);
            $articles = array();

            if ($series === null) {
                // TODO Exception werfen
            }

            // TODO Artikel pro Serie auslesen

            $ebook = $this->createEpubForArticles($articles, $series->title, $series->description);

            // TODO E-Book ggf. in den Cache schreiben
        }
        return $ebook;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Ermittelt das jüngste enthaltene Veröffentlichungsdatum aus den übergebenen Artikeln
     *
     * @param Article[] $articles Die zu durchsuchenden Artikel
     * @return \DateTime Das jüngste enthaltene Veröffentlichungsdatum
     * @throws Exception
     */
    private function getLatestDateFromArticles(array $articles) : \DateTime {
        $date = null;
        foreach ($articles as $article) {
            if ($article->publishing_timestamp != null && ($date == null || $article->publishing_timestamp > $date)) {
                $date = $article->publishing_timestamp;
            }
        }
        if ($date == null) {
            $date = new \DateTime('now');
        }
        return $date;
    }

    /**
     * Fasst alle in den übergebenen Artikeln genutzten Tags in einem einzelnen String zusammen
     *
     * @param Article[] $articles Die zu durchsuchenden Artikel
     * @return string Alle enthaltenen Tags in Form eines einzelnen String
     */
    private function getTagsAsStringFromArticles(array $articles) : string {
        $tags = array();
        foreach ($articles as $article) {
            if ($article->tags !== null) {
                $tags = array_unique(array_merge($tags, $article->tags));
            }
        }
        sort($tags);
        return implode(', ', $tags);
    }

    /**
     * Erstellt aus den übergebenen Artikeln eine gemeinsame Autorenangabe (sofern möglich)
     *
     * @param Article[] $articles Die zu durchsuchenden Artikel
     * @return string Eine zusammengefasste Autorenangabe
     */
    private function getCreatorInfoFromArticles(array $articles) : string {
        $authorIds = array();
        foreach ($articles as $article) {
            if (!in_array($article->author_id, $authorIds, true)) {
                $authorIds[] = $article->author_id;
            }
        }

        $authorStrings = array();
        foreach ($authorIds as $id) {
            $author = $this->ncm->orm->getUserById($id, true);
            if ($author !== null) {
                $authorStrings[] = $author->getFullName();
            }
        }

        if (count($authorStrings) === 1) {
            return $authorStrings[0];
        }

        if (count($authorStrings) >= 2) {
            return $authorStrings[0] . " et al.";
        }

        return "";
    }

    /**
     * Versucht, ein E-Book aus dem Cache zu laden
     *
     * @param string $cacheKey Eindeutiger Cache-Schlüssel für den gesuchten Inhalte
     * @return string|null Der Dateiinhalt als String oder null
     */
    private function getContentFromEbookCache(string $cacheKey): ?string {
        if ($this->ncm->ebookCache instanceof CacheInterface) {
            $book = $this->ncm->ebookCache->get($cacheKey);
            if ($book !== null) return $book;
        }
        return $book;
    }

    /**
     * Legt den übergebenen E-Book-Inhalt unter dem angegebenen Schlüssel im E-Book-Cache ab
     *
     * @param string $cacheKey Eindeutiger Cache-Schlüssel für das zu speichernde E-Book
     * @param string $content Der zu speichernde E-Book-Inhalt
     * @return void
     */
    private function putContentToEbookCache(string $cacheKey, string $content): void {
        if ($this->ncm->ebookCache instanceof CacheInterface) {
            $this->ncm->ebookCache->put($cacheKey, $content);
        }
    }

    /**
     * Erstellt ein E-Book für die übergebenen Artikel mit angegebenem Titel und angegebener Kurzbeschreibung
     *
     * @param Article[] $articles Die zu verpackenden Artikel
     * @param string $title Titel für das E-Book
     * @param string $description Kurzbeschreibung für das E-Book
     * @param bool $createCoverPage Gibt an, ob eine Seite mit dem Buch-Umschlag bzw. -Titel erstellt werden soll
     * @param bool $createTitlePage Gibt an, ob eine Titelseite vor dem Buch-Inhalt erstellt werden soll
     * @return string E-Pub-Daten in String-Form
     * @throws Exception
     */
    private function createEpubForArticles(array $articles, string $title = '', string $description = '', $createCoverPage = true, $createTitlePage = true): string {
        $mappedUrls = array();

        $writer = new Epub3Writer($this->ncm->cachedir);
        $document = new Document();
        $this->module->ebook = $document;

        $document->title = $title;
        $document->description = $description;
        $document->language = $this->ncm->lang;
        $document->identifier = uniqid('', true);
        $document->rights = $this->ncm->orm->getCopyrightNotice();
        $document->publisher = $this->ncm->orm->getSiteTitle();
        $document->date = $this->getLatestDateFromArticles($articles);
        $document->subject = $this->getTagsAsStringFromArticles($articles);
        $document->creator = $this->getCreatorInfoFromArticles($articles);

        // Optionale Umschlagseite
        if ($createCoverPage) {
            $xhtml = $this->module->renderUserTemplate('epub-cover.phtml');
            $xhtml = $this->replaceLinkedContents(
                $document, $xhtml, $mappedUrls
            );
            $document->addContent(
                $document->createContentFromString(
                    $document->coverTitle,
                    'cover.xhtml',
                    $xhtml,
                    array('cover', 'svg')
                )
            );
        }

        // Optionale Titelseite
        if ($createTitlePage) {
            $this->module->articles = $articles;
            $xhtml = $this->module->renderUserTemplate('epub-titlepage.phtml');
            $xhtml = $this->replaceLinkedContents(
                $document, $xhtml, $mappedUrls
            );
            $document->addContent(
                $document->createContentFromString(
                    $document->titlePageTitle,
                    'titlepage.xhtml',
                    $xhtml,
                    array('title-page')
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
            $document->createTocContent($document->tocTitle)
        );
        $document->addContent(
            $document->createNcxContent($document->tocTitle)
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
     * @param string $content Der zu modifizierende Inhalt
     * @param &array $mappedUrls Referenz auf die gemappten URLs
     * @return string Der modifizierte Inhalt
     */
    private function replaceLinkedContents(Document $document, string $content, &$mappedUrls): string {
        return preg_replace_callback('/((href=\"|src=\")([^\"]+)(\"))/i', function($matches) use ($document, &$mappedUrls) {
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
            } else {
                $mimeType = $this->getMimeTypeByUrl($targetUrl);

                if ($this->isMimeTypeEmbeddable($mimeType)) {
                    $content = Fetch::fetchFromUrl($targetUrl);

                    if (!empty($content)) {
                        $extension = $this->getDefaultFileExtensionByMimeType($mimeType);
                        $counter = 0;

                        // Ensure that filename ($virtualUrl) is unique
                        do {
                            $fileExists = false;
                            $virtualUrl = basename($targetUrl);
                            if ($counter > 0) {
                                $virtualUrl .= "-$counter";
                            }
                            if (strtolower(substr($virtualUrl, -strlen($extension))) !== $extension) {
                                $virtualUrl .= ".$extension";
                            }

                            foreach ($mappedUrls as $mappedUrl) {
                                if ($mappedUrl->virtualUrl === $virtualUrl) {
                                    $fileExists = true;
                                }
                            }
                            $counter++;
                        } while ($fileExists);

                        $mappedContent = new MappedUrl();
                        $mappedContent->originalUrl = $sourceUrl;
                        $mappedContent->targetUrl = $targetUrl;
                        $mappedContent->content = $content;
                        $mappedContent->title = '';
                        $mappedContent->mimeType = $mimeType;
                        $mappedContent->virtualUrl = $virtualUrl;
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
     * @param string $link Der zu prüfende Link
     * @return bool true, wenn der Link (nur) auf einen Anker zeigt
     */
    private function isAnchorLink($link): bool {
        return strpos($link, '#') === 0;
    }

    /**
     * Überprüft, ob es sich beim übergebenen Link um einen externen Link handelt
     *
     * Die Überprüfung beschränkt sich darauf, ob der Link mit einer Protokollangabe
     * (HTTP, HTTPS, MAILTO etc.) beginnt.
     * @param string $link Der zu prüfende Link
     * @return bool true, wenn es sich um einen externen Link handelt
     */
    private function isExternalLink($link): bool {
        return preg_match('/^([a-z]+:)/i', $link) !== 0;
    }

    /**
     * Überprüft, ob es sich beim übergebenen MIME-Type um einen in das E-Book einbettbares Dateiformat handelt
     *
     * @param string $mimeType Der zu prüfende MIME-Type
     * @return bool true, wenn es sich um einen einbettbaren Inhaltstyp handelt
     */
    private function isMimeTypeEmbeddable($mimeType): bool {
        if (!is_string($mimeType)) {
            return false;
        }

        $mimeType = strtolower($mimeType);
        $embeddable = array(
            'text/css',
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
        );

        return $mimeType != null && in_array($mimeType, $embeddable);
    }

    /**
     * Ermittelt die Standard-Dateiendung für den angegebenen MIME-Type
     *
     * @param string $mimeType Der zu prüfende MIME-Type
     * @return string Die zugehörige Standard-Dateiendung
     */
    private function getDefaultFileExtensionByMimeType($mimeType): string {
        $mimeType = strtolower($mimeType);
        if ($mimeType === 'image/svg+xml') return 'svg';
        return strtolower(explode('/', $mimeType)[1]);
    }

    /**
     * Ermittelt den MIME-Type für eine URL
     *
     * @param string $url Zu überprüfende URL
     * @return string|null Der ermittelte MIME-Type oder null
     */
    private function getMimeTypeByUrl(string $url): ?string {
        $type = null;
        $typeHeader = Fetch::getContentTypeHeaderForUrl($url);
        if ($typeHeader != null) {
            $type = strtolower(trim(explode(';', $typeHeader)[0]));
        }
        return $type;
    }

    // </editor-fold>
}
