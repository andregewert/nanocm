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
 * Bildet einen Eintrag in der Mediendatenbank ab
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-04
 */
class Medium
{
    // <editor-fold desc="Constants">

    /**
     * Type-ID für Verzeichnisse
     * @var int
     */
    public const TYPE_FOLDER = 0;

    /**
     * Type-ID für Mediendateien
     * @var int
     */
    public const TYPE_FILE = 1;

    // </editor-fold>


    // <editor-fold desc="Properties">

    /**
     * @var int ID des Mediendatensatzes
     */
    public $id;

    /**
     * Gibt die Art des Eintrages an. Ein Wert von 0 steht für einen Ordner; eine 1 steht für eine Mediendatei.
     *
     * @var string
     */
    public $entrytype;

    /**
     * @var int ID der übergeordneten Verzeichnisses
     */
    public $parent_id;

    /**
     * @var \DateTime Zeitpunkt der Datensatz-Erstellung
     */
    public $creation_timestamp;

    /**
     * @var \DateTime Zeitpunkt der letzten Änderung
     */
    public $modification_timestamp;

    /**
     * @var int Status-Code für den Mediendatensatz
     */
    public $status_code;

    /**
     * @var string Dateiname
     */
    public $filename;

    /**
     * @var string Datei-Endung
     */
    public $extension;

    /**
     * @var string Mime-Type (für Dateien) oder 'folder' (für Verzeichnisse)
     */
    public $type;

    /**
     * @var string Titel für die Mediendatei
     */
    public $title;

    /**
     * @var string Ausführlicher Beschreibungstext für die Mediendatei
     */
    public $description;

    /**
     * @var string Attribution / rechtlicher Hinweis für die Mediendatei
     */
    public $attribution;

    /**
     * @var array Verknüpfte Tags
     */
    public $tags;

    // </editor-fold>


    // <editor-fold desc="Public Methods">

    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $medium \Ubergeek\NanoCm\Medium */
        if (($medium = $stmt->fetchObject(__CLASS__)) !== false) {
            $medium->creation_timestamp = new \DateTime($medium->creation_timestamp);
            $medium->modification_timestamp = new \DateTime($medium->modification_timestamp);
            return $medium;
        }
        return null;
    }

    // </editor-fold>
}