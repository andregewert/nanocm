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
 * Bildet die Basisdaten ("Kopfdaten") für eine Feed-Quelle ab (Titel der Website etc.)
 *
 * Die Definition lehnt sich im Moment klar an den Atom-Spezifikationen an, kann bei Bedarf aber auch noch erweitert
 * werden.
 *
 * @package Ubergeek\Feed
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-09
 */
class Feed {

    /**
     * Titel des Feeds
     *
     * @var string
     */
    public $title;

    /**
     * Optionaler Untertitel für den Feed
     *
     * @var string
     */
    public $subtitle;

    /**
     * Gibt den Zeitpunkt der letzten Änderung des Feeds an
     *
     * @var \DateTime
     */
    public $updated;

    /**
     * Eindeutige ID für den Feed. Es kann bspw. die Domain der zugehörigen Website verwendet werden.
     *
     * @var string
     */
    public $id;

    /**
     * Optionale weiterführende Links
     *
     * @var Link[]
     */
    public $links;

    /**
     * Autor bzw. Hauptautor der in diesem Feed dargestellten Inhalte
     *
     * @var Person
     */
    public $author;

    /**
     * Eine Optionale Liste von Inhaltskategorien, denen der Feed zugeordnet werden soll
     *
     * @var string[]
     */
    public $categories;

    /**
     * Optionale Beitragende zu diesem Feed
     *
     * @var Person[]
     */
    public $contributors;

    /**
     * Optionaler Link zu einem Icon für diesen Feed
     *
     * @var string
     */
    public $icon;

    /**
     * Optionaler Link zu einer Logo-Grafik für diesen Feed
     *
     * @var string
     */
    public $logo;

    /**
     * Optionale rechtliche Hinweise / Copyright-Hinweis für den Feed
     *
     * @var string
     */
    public $rights;

    /**
     * Die Inhaltseinträge des Feeds
     *
     * @var Entry[]
     */
    public $entries;

}