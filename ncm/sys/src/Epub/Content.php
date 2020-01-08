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

namespace Ubergeek\Epub;

/**
 * Bildet einen einzelnen Inhalt(sabschnitt) innerhalb des EPub-Dokumentes ab
 * @package Ubergeek\Epub
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-01-07
 */
class Content {

    /**
     * Eindeutige, interne ID für diesen Inhalt
     * @var string
     */
    public $id = '';

    /**
     * Der Dateiname, wie er innerhalb des ePub-Archivs verwendet werden soll
     * @var string
     */
    public $filename = '';

    /**
     * Der MIME-Type dieses Inhalts. Wird vorbelegt mit 'application/xhtml+xml'
     * @var string
     */
    public $type = 'application/xhtml+xml';

    /**
     * Lesbarer / anzuzeigender Titel für diesen Inhalt
     * @var string Inhaltstitel
     */
    public $title = '';

    /**
     * Der eigentliche Dateiinhalt in Form eines einzelnen Strings
     * @var string
     */
    public $contents = '';

    /**
     * Gibt an, ob dieser Inhalt im "Spine" aufgelistet werden soll
     * @var bool
     */
    public $includeInSpine = true;

    /**
     * Optionale Property-Angaben (zur Rolle dieses Inhaltes innerhalb des EPub-Dokumentes)
     * @var string[]
     */
    public $properties = array();
}