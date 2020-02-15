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

namespace Ubergeek\Epub;

use Ubergeek\Epub\Exception\DuplicateContentIdException;

/**
 * Bildet ein ePub-Dokument ab
 *
 * @package Ubergeek\Epub
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-09
 */
class Document {

    // <editor-fold desc="Public properties">

    /**
     * @var string Titel des Dokumentes
     */
    public $title;

    /**
     * @var string Titel bzw. Überschrift für die Seite mit dem Buch-Umschlag
     */
    public $coverTitle = 'Cover';

    /**
     * @var string Titel bzw. Überschrift für das Inhaltsverzeichnis (falls vorhanden)
     */
    public $tocTitle = 'Inhalt';

    /**
     * @var string Titel bzw. Überschrift für die Titelseite zwischen Cover und eigentlichem Buchinhalt
     */
    public $titlePageTitle = 'Übersicht';

    /**
     * @var string Eindeutige Identifikation für dieses Dokument, bspw. eine ISBN
     */
    public $identifier = '';

    /**
     * Kurzbeschreibung oder Anrisstext / Klappentext o. ä.
     * @var string
     */
    public $description;

    /**
     * (Haupt-)Sprache des Dokumentes
     * @var string
     */
    public $language = 'en';

    /**
     * Optionale Autorenangabe
     * @var null|string
     */
    public $creator = null;

    /**
     * Optionale Publisher-Angabe
     * @var null|string
     */
    public $publisher = null;

    /**
     * Optionale Hinweise zum Urheberrecht
     * @var null|string
     */
    public $rights = null;

    /**
     * Zusatzangaben (Stichworte) zum Inhalt
     * @var null|string
     */
    public $subject = null;

    /**
     * Optionale Angabe zum Erscheinungsdatum oder -Jahr
     * @var null|\DateTime
     */
    public $date = null;

    /**
     * Optionale Zeitangabe der letzten Änderung
     *
     * Wenn diese Angabe nicht explizit gesetzt wird, so wird der Writer
     * als Modifikationsdatum den Zeitpunkt der Dateierstellung einsetzen.
     *
     * @var null|\DateTime
     */
    public $modified = null;

    /**
     * Ein Array der verfügbaren Inhalte bzw. Inhaltsabschnitte
     * @var Content[]
     */
    public $contents;

    /**
     * Präfix für die eigentlichen E-Book-Inhaltsdateien innerhalb des EPub-Archivs
     * @var string Präfix für Inhaltsdateien innerhalb des EPub-Archivs
     */
    public $contentPrefix = 'contents/';

    /**
     * Gesamtanzahl der Seiten (im Print) dieses Dokumentes
     * @var int
     */
    public $totalPageCount = 0;

    /**
     * Höchste Seitenzahl (entspr. Print-Ausgabe), zu der navigiert werden kann
     * @var int
     */
    public $maxPageNumber = 0;

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Fügt den übergebenen Inhalt am Anfang des Dokumentes hinzu
     *
     * @param Content $content Das hinzuzufügende Content-Objekt
     * @return Content Das hinzugefügte Content-Objekt
     */
    public function addContentAtBeginning(Content $content) {
        if ($this->isContentIdExisting($content->id)) {
            throw new DuplicateContentIdException("Duplicate content id: id '$content->id' already exists!");
        }
        array_unshift($this->contents, $content);
        return $content;
    }

    /**
     * Fügt dem Dokument den angegebenen Inhalt hinzu
     *
     * @param Content $content Das hinzuzufügende Content-Objekt
     * @return Content Das hinzugefügte Content-Objekt
     */
    public function addContent(Content $content) {
        if ($this->isContentIdExisting($content->id)) {
            throw new DuplicateContentIdException("Duplicate content id: id '$content->id' already exists!");
        }
        $this->contents[] = $content;
        return $content;
    }

    public function createContentFromString(string $title,
                                            string $filename,
                                            string $contents,
                                            $properties = null,
                                            string $id = null,
                                            $includeInSpine = true) : Content {
        return $this->createContentFromStringWithType($title, $filename, $contents, '', $properties, $id, $includeInSpine);
    }

    public function createContentFromStringWithType(string $title,
                                                    string $filename,
                                                    string $contents,
                                                    string $mimeType,
                                                    $properties = null,
                                                    string $id = null,
                                                    $includeInSpine = true) : Content {
        if (!is_array($properties) && $properties != null) {
            $properties = array($properties);
        } elseif (!is_array($properties)) {
            $properties = array();
        }

        $content = new Content();
        $content->id = ($id === '' || $id === null)? $this->createContentId() : $id;
        $content->filename = $this->translateFilename($filename);
        $content->title = $title;
        $content->contents = $contents;
        $content->properties = $properties;
        if ($mimeType != '') {
            $content->mimeType = $mimeType;
        }
        $content->includeInSpine = $includeInSpine;
        return $content;
    }

    /**
     * Erstellt ein Inhaltsverzeichnis (in Form einer XHTML-Datei) für alle bisher hinzugefügten Einzelinhalte
     * (Kompatibilität zu ePub3)
     *
     * @param string $title Titel bzw. Überschrift für das Verzeichnis
     * @param bool $includeAttachmentList Gibt an, ob Attachments (Bilder etc.) im Inhaltsverzeichnis aufgeführt werden sollen
     * @param string $filename Der intern zu verwendende Dateiname
     * @return Content Das Inhaltsverzeichnis in Form eines Content-Datenmodells
     */
    public function createTocContent(string $title, bool $includeAttachmentList = false, string $filename = 'toc.xhtml') {
        $content = new Content();
        $content->id =  $this->createContentId('toc-');
        $content->filename = 'toc.xhtml';
        $content->title = $title;
        $content->contents = $this->createToc($title, $includeAttachmentList);
        $content->properties = array('nav', 'toc');
        $content->mimeType = 'application/xhtml+xml';
        $content->includeInSpine = false;
        return $content;
    }

    /**
     * Erstellt ein Inhaltsverzeichnis im NCX-Format (Kompatibilität zu ePub2)
     *
     * @param string $title Titel bzw. Überschrift für das Inhaltsverzeichnis
     * @return Content Das Inhaltsverzeichnis in Form eines Content-Datenmodells
     */
    public function createNcxContent(string $title) {
        $content = new Content();
        $content->id = 'ncx';
        $content->filename = 'toc.ncx';
        $content->title = $title;
        $content->contents = $this->createNcx($title);
        $content->mimeType = 'application/x-dtbncx+xml';
        $content->includeInSpine = false;
        return $content;
    }

    /**
     * Überprüft, ob bereits eine NCX-Datei vorhanden ist.
     * Wenn ein Inhaltsverzeichnis im NCX-Format vorhanden ist, so hat dieses über die
     * eundeutige ID "ncx" gekennzeichnet zu werden.
     *
     * @return bool true, wenn das Dokument bereits einen NCX-Inhalt enthält
     */
    public function isNcxExisting() : bool {
        foreach ($this->contents as $content) {
            if ($content->id == 'ncx') return true;
        }
        return false;
    }

    /**
     * Gibt alle Inhalte zurück, die die angegebene Property besitzen
     *
     * @param string $property Die gesuchte Property
     * @return array Ein Array mit allen zutreffenden Inhalten
     */
    public function getContentsWithProperty(string $property) {
        $found = array();
        foreach ($this->contents as $content) {
            if (is_array($content->properties) && in_array(strtolower($property), $content->properties)) {
                $found[] = $content;
            }
        }
        return $found;
    }

    /**
     * Gibt den ersten Content zurück, der die angegebene Property besitzt
     *
     * @param string $property Die gesuchte Property
     * @return Content|null Der erste zutreffende Content oder null
     */
    public function getFirstContentWithProperty(string $property) {
        $temp = $this->getContentsWithProperty($property);
        if (is_array($temp) && count($temp) >= 1) return $temp[0];
        return null;
    }

    public function getFirstNonSpecialContent() {
        foreach ($this->contents as $content) {
            if (!is_array($content->properties)) return $content;
        }
        return null;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Erstellt anhand der bisher hinzugefügten Inhaltsdateien ein automatisches
     * Inhaltsverzeichnis in Form eines XHTML-Strings (Kompitibilität zu ePub3).
     *
     * Das generierte Inhaltsverzeichnis wird nicht automatisch als Inhaltsseite in
     * das Buch eingebunden. Stattdessen muss es auf Wunsch mit addToc() hinzugefügt werden.
     *
     * @param string $title
     * @param bool $includeAttachmentList
     * @return string Ein generiertes Inhaltsverzeichnis als XHTML-Body-Inhalt
     */
    private function createToc(string $title, bool $includeAttachmentList = false) {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $rootNode = $dom->appendChild($dom->createElementNS('http://www.w3.org/1999/xhtml', 'html'));
        $rootNode->appendChild($dom->createAttribute('xmlns:epub'))->nodeValue = 'http://www.idpf.org/2007/ops';
        $rootNode->appendChild($dom->createAttribute('xml:lang'))->nodeValue = $this->language;

        $headNode = $dom->createElement('head');
        $headNode->appendChild($dom->createElement('title'))->nodeValue = $title;
        $rootNode->appendChild($headNode);

        $bodyNode = $rootNode->appendChild($dom->createElement('body'));
        $navNode = $bodyNode->appendChild($dom->createElement('nav'));
        $navNode->appendChild($dom->createAttribute('epub:type'))->nodeValue = 'toc';
        $navNode->appendChild($dom->createElement('h1'))->nodeValue = $title;

        $olNode = $navNode->appendChild($dom->createElement('ol'));
        $olNode->appendChild($dom->createAttribute('epub:type'))->nodeValue = 'list';

        foreach ($this->contents as $content) {
            if ($content->includeInSpine || $includeAttachmentList) {
                $liNode = $olNode->appendChild($dom->createElement('li'));
                $aNode = $liNode->appendChild($dom->createElement('a'));
                $aNode->appendChild($dom->createAttribute('href'))->nodeValue = $content->filename;
                $aNode->nodeValue = $content->title;
            }
        }

        return $dom->saveXML();
    }

    /**
     * Erstellt anhand der bereits vorhandenen Inhalte ein Inhaltsverzeichnis im NCX-Format
     * (Kompatibilität zu ePub2)
     *
     * @return string Die generierte NCX-Datei
     */
    private function createNcx() {
        $counter = 1;
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $rootNode = $dom->appendChild($dom->createElement('ncx'));
        $rootNode->appendChild($dom->createAttribute('version'))->nodeValue = '2005-1';
        $rootNode->appendChild($dom->createAttribute('xmlns'))->nodeValue = 'http://www.daisy.org/z3986/2005/ncx/';

        $headNode = $rootNode->appendChild($dom->createElement('head'));
        $metaNode = $headNode->appendChild($dom->createElement('meta'));
        $metaNode->appendChild($dom->createAttribute('name'))->nodeValue = 'dtb:uid';
        $metaNode->appendChild($dom->createAttribute('content'))->nodeValue = $this->identifier;

        $metaNode = $headNode->appendChild($dom->createElement('meta'));
        $metaNode->appendChild($dom->createAttribute('name'))->nodeValue = 'dtb:depth';
        $metaNode->appendChild($dom->createAttribute('content'))->nodeValue = '0';

        $metaNode = $headNode->appendChild($dom->createElement('meta'));
        $metaNode->appendChild($dom->createAttribute('name'))->nodeValue = 'dtb:totalPageCount';
        $metaNode->appendChild($dom->createAttribute('content'))->nodeValue = $this->totalPageCount;

        $metaNode = $headNode->appendChild($dom->createElement('meta'));
        $metaNode->appendChild($dom->createAttribute('name'))->nodeValue = 'dtb:maxPageCount';
        $metaNode->appendChild($dom->createAttribute('content'))->nodeValue = $this->maxPageNumber;

        $docTitleNode = $rootNode->appendChild($dom->createElement('docTitle'));
        $docTitleNode->appendChild($dom->createElement('text'))->nodeValue = $this->title;

        $navMapNode = $rootNode->appendChild($dom->createElement('navMap'));
        foreach ($this->contents as $content) {
            if ($content->includeInSpine) {
                $navPointNode = $dom->createElement('navPoint');
                $navPointNode->appendChild($dom->createAttribute('id'))->nodeValue = 'navPoint-' . $counter;
                $navPointNode->appendChild($dom->createAttribute('playOrder'))->nodeValue = $counter;

                $navLabelNode = $navPointNode->appendChild($dom->createElement('navLabel'));
                $navLabelNode->appendChild($dom->createElement('text'))->nodeValue = $content->title;

                $contentNode = $navPointNode->appendChild($dom->createElement('content'));
                $contentNode->appendChild($dom->createAttribute('src'))->nodeValue = $content->filename;

                $navMapNode->appendChild($navPointNode);
                $counter++;
            }
        }

        return $dom->saveXML();
    }

    /**
     * Übersetzt einen von außen gegebenen Dateinamen in den entsprechenden innerhalb
     * des ePub-Archivs genutzten Dateinames
     *
     * @param string $filename Der ursprüngliche Dateiname
     * @return string Der innerhalb des Archivs verwendete Dateiname
     */
    protected function translateFilename(string $filename) {
        return $this->contentPrefix . $filename;
    }

    /**
     * Gibt ein Array mit den (bisher) vergebenen Content-IDs zurück
     * @return string[] Eine Array mit den verwendeten Content-IDs
     */
    protected function getContentIds() : array {
        $ids = array();
        if (is_array($this->contents)) {
            foreach ($this->contents as $content) {
                $ids[] = $content->id;
            }
        }
        $ids = array_unique($ids);
        return $ids;
    }

    /**
     * Überprüft, ob eine bestimmte Content-ID bereits vergeben worden ist
     * @param string $id
     * @return bool true, wenn die angegebene Content-ID bereits verwendet wird
     */
    protected function isContentIdExisting(string $id) : bool {
        return in_array($id, $this->getContentIds());
    }

    /**
     * Erstellt eine neue, eindeutige Content-ID
     * @param string $prefix Optionales Präfix für die Bezeichnung der ID
     * @return string Die generierte Content-ID
     */
    protected function createContentId(string $prefix = 'content-') {
        $counter = 1;
        do {
            $id = "$prefix$counter";
            $counter++;
        } while ($this->isContentIdExisting($id));
        return $id;
    }

    // </editor-fold>

}