<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2019 André Gewert <agewert@ubergeek.de>
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

/**
 * Created by PhpStorm.
 * User: agewert
 * Date: 20.01.19
 * Time: 17:25
 */

namespace Ubergeek\Feed;

/**
 * Bildet einen Link für die Verwendung in einem Feed ab
 *
 * Die Definition lehnt sich im Moment klar an den Atom-Spezifikationen an, kann bei Bedarf aber auch noch erweitert
 * werden.
 *
 * @package Ubergeek\Feed
 * @created 2019-01-20
 * @author André Gewert <agewert@ubergeek.de>
 */
class Link
{
    /**
     * URI der verlinkten Ressource
     *
     * @var string
     */
    public $href;

    /**
     * Beziehungstyp zur verlinkten Ressource
     *
     * Mögliche Werte:
     * - alternate: alternative Darstellung des Feed-Eintrages, bspw. die HTML-Version auf der generierenden Seite
     * - enclosure: zusätzlicher Inhalt, der zu diesem Eintrag gehört, aber nicht eingebettet wird.
     * - related: eine verwandte / weiterführende Ressource
     * - self: Link auf den Feed selbst
     * - via: Quellenangabe für einen Feed oder Eintrag
     *
     * @var string
     */
    public $relation;

    /**
     * Content type der verlinkten Ressource
     *
     * @var string
     */
    public $type;

    /**
     * Sprache der verlinkten Ressource
     *
     * @var string
     */
    public $hrefLang;

    /**
     * Titel der verlinkten Ressource
     *
     * @var string
     */
    public $title;

    /**
     * Länge / Größe der verlinkten Ressource in Bytes
     *
     * @var int
     */
    public $length;
}