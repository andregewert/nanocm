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
 * Bildet eine Artikelserie ab
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-10-30
 */
class Articleseries {

    // <editor-fold desc="Properties">

    /**
     * @var int ID der Artikelserie
     */
    public $id;

    /**
     * @var \DateTime Zeitpunkt der Datensatz-Erstellung
     */
    public $creation_timestamp;

    /**
     * @var \DateTime Zeitpunkt der letzten Datensatz-Änderung
     */
    public $modification_timestamp;

    /**
     * @var int Status-Code für diese Artikelserie
     */
    public $status_code;

    /**
     * @var string Titel der Artikelserie
     */
    public $title;

    /**
     * @var string Optionale Beschreibung der Artikelserie
     */
    public $description;

    /**
     * Definitions-Key für die vorgegebene Sortierung von Artikel
     * innerhalb dieser Artikelserie.
     * @var string
     */
    public $sorting_key;

    // </editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Erstellt ein Articleseries-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return Articleseries
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $series \Ubergeek\NanoCm\Articleseries */

        if (($series = $stmt->fetchObject(__CLASS__)) !== false) {
            $series->creation_timestamp = new \DateTime($series->creation_timestamp);
            $series->modification_timestamp = new \DateTime($series->modification_timestamp);
            return $series;
        }
        return null;
    }

    // </editor-fold>

}