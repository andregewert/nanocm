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

namespace Ubergeek\Feed;

/**
 * Bildet einen Feed-Eintrag ab
 *
 * Die Definition lehnt sich im Moment klar an den Atom-Spezifikationen an, kann bei Bedarf aber auch noch erweitert
 * werden.
 *
 * @package Ubergeek\Feed
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-09
 */
class Entry {

    /**
     * Eindeutige ID für diesen Eintrag. In der Regel sollte der Permalink zum Artikel genutzt werden.
     *
     * @var string
     */
    public $id;

    /**
     * Eintragstitel
     *
     * @var string
     */
    public $title;

    /**
     * Zeitpunkt der letzten Änderung.
     *
     * @var \DateTime
     */
    public $updated;

    /**
     * Veröffentlichungszeitpunkt des Eintrags / Artikels
     *
     * @var \DateTime
     */
    public $published;

    /**
     * Autor / Autorin des Eintrages
     *
     * @var Person
     */
    public $author;

    /**
     * Eigentlicher Inhalt des Eintrages
     *
     * @var string
     */
    public $content = '';

    /**
     * Inhaltsstyp des Eintrages
     *
     * @var string
     */
    public $contentType = 'text';

    /**
     * Optionale Links zu diesem Eintrag
     *
     * @var Link[]
     */
    public $links;

    /**
     * Optionale Kurzzusammenfassung (plain text, kein HTML)
     *
     * @var string
     */
    public $summary;

    /**
     * Optionale Kategorien, denen dieser Eintrag zugeordnet werden kann
     *
     * @var string[]
     */
    public $categories;

    /**
     * Optionale Auflistung von zu diesem Eintrag / Artikel Beitragenden Personen
     *
     * @var Person[]
     */
    public $contributors;

    /**
     * Optionaler rechtlicher Hinweis / Copyright-Hinweis
     *
     * @var string
     */
    public $rights;

}