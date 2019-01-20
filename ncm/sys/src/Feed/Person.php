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

namespace Ubergeek\Feed;

/**
 * Bildet einen Personendatensatz (etwa mit Autoreninformationen für einen Artikel oder Kommentar) für Feeds ab
 *
 * Die Definition lehnt sich im Moment klar an den Atom-Spezifikationen an, kann bei Bedarf aber auch noch erweitert
 * werden.
 *
 * @package Ubergeek\Feed
 * @created 2019-01-20
 * @author André Gewert <agewert@ubergeek.de>
 */
class Person
{
    /**
     * Vollständiger bzw. anzuzeigender Name der Person
     *
     * @var string
     */
    public $name;

    /**
     * Optionaler Link zu einer Homepage o. ä. für diese Person
     *
     * @var string
     */
    public $uri;

    /**
     * Optionale, öffentliche E-Mail-Adresse der Person
     *
     * @var string
     */
    public $email;
}