<?php
/**
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm;

/**
 * Bildet ein Tag (ein Schlagwort) ab
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-02-27
 */
class Tag {

    // <editor-fold desc="Properties">

    /**
     * Datensatz-ID
     * @var integer
     */
    public $id;

    /**
     * Eigentliches Schlagwort
     * @var string
     */
    public $title;

    // </editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Erstellt ein Tag-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return Tag|null
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        if (($tag = $stmt->fetchObject(__CLASS__)) !== false) {
            return $tag;
        }
        return null;
    }

    // </editor-fold>
}