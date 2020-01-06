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
     * Ein Array der verfügbaren Inhalte bzw. Inhaltsabschnitte
     * @var array
     */
    public $contents;

    /**
     * Ein Array mit den einzubettenden Inhalten, einschließlich Bilddateien,
     * Style Sheets und ähnlichem
     * @var array
     */
    public $attachments;

    // </editor-fold>


    // <editor-fold desc="Public methods">

    public function addContent() {
        // TODO implementieren
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

    /**
     * Fügt dem Dokument eine Dateianlage hinzu
     *
     * Die Methode gibt den intern generierten Dateinamen zurück.
     *
     * @param string $content
     * @param string $filename
     */
    public function addAttachment(string $content, string $filename) {
        // TODO implementieren
    }

    // </editor-fold>
}