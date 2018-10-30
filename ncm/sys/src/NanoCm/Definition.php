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

namespace Ubergeek\NanoCm;

/**
 * Bildet eine der frei definierbaren Definitionen ab
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-10-30
 */
class Definition {

    // <editor-fold desc="Properties">

    /**
     * @var string Definitionstyp
     */
    public $definitiontype;

    /**
     * @var string Schlüssel der Definition innerhalb des Definitionstyps
     */
    public $key;

    /**
     * @var string Titel bzw. Anzeigename der konkreten Definition
     */
    public $title;

    /**
     * @var string Optionaler zusätzlicher Wert zur Definition
     */
    public $value;

    /**
     * @var string Optionale zusätzliche Parameter zur Definition
     */
    public $parameters;

    // </editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Erstellt ein Definition-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return Definition
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        if (($def = $stmt->fetchObject(__CLASS__)) !== false) {
            return $def;
        }
        return null;
    }

    // </editor-fold>
}