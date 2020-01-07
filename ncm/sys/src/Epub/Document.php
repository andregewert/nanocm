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
     * @var string Eindeutige Identifikation für dieses Dokument, bspw. eine ISBN
     */
    public $identifier = '';

    /**
     * Kurzbeschreibung oder Anrisstext / Klappentext o. ä.
     * @var string
     */
    public $description;

    /**
     * use ZipArchive;
     * @var string
     */
    public $language = 'en';

    /**
     * Optionale Hinweise zum Urheberrecht
     * @var null|string
     */
    public $rights = null;

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

    //public $contentPrefix = 'contents';

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Fügt den Dokument den angegebenen Inhalt hinzu
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

    public function createContentFromString(string $filename, string $contents, $properties = array(), string $id = '') : Content {
        return $this->createContentFromStringWithType($filename, $contents, '', $properties, $id);
    }

    public function createContentFromStringWithType(string $filename, string $contents, string $type, $properties = array(), string $id = '') : Content {
        $content = new Content();
        $content->id = ($id == '')? $this->createContentId() : $id;
        $content->filename = $filename;
        $content->contents = $contents;
        $content->properties = $properties;
        if ($type != '') {
            $content->type = $type;
        }
        return $content;
    }

    /**
     * Erstellt anhand der bisher hinzugefügten Inhaltsdateien ein automatisches
     * Inhaltsverzeichnis in Form eines XHTML-Strings.
     *
     * Das generierte Inhaltsverzeichnis wird nicht automatisch als Inhaltsseite in
     * das Buch eingebunden. Stattdessen muss es auf Wunsch mit addToc() hinzugefügt werden.
     *
     * @return string Ein generiertes Inhaltsverzeichnis als XHTML-Body-Inhalt
     */
    public function createToc(bool $includeAttachmentList = false) {
        // TODO implementieren
    }

//    /**
//     * Fügt dem Dokument eine Dateianlage hinzu
//     *
//     * Die Methode gibt den intern generierten Dateinamen zurück.
//     *
//     * @param string $content
//     * @param string $filename
//     */
//    public function addAttachment(string $content, string $filename) {
//        // TODO implementieren
//    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Gibt ein Array mit den (bisher) vergebenen Content-IDs zurück
     *
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
     *
     * @param string $id
     * @return bool true, wenn die angegebene Content-ID bereits verwendet wird
     */
    protected function isContentIdExisting(string $id) : bool {
        return in_array($id, $this->getContentIds());
    }

    /**
     * Erstellt eine neue, eindeutige Content-ID
     *
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