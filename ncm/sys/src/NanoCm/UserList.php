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
 * Bildet eine benutzerdefinierte Liste ab
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-02-27
 */
class UserList {

    // <editor-fold desc="Properties">

    /**
     * Eindeutige Datensatz-ID
     * @var integer
     */
    public $id;

    /**
     * Titel bzw. Name der Liste
     * @var string
     */
    public $title;

    /**
     * Statuscode des Datensatzes
     * @var integer
     */
    public $status_code;

    /**
     * Erstellungszeitpunkt des Datensatzes
     * @var \DateTime
     */
    public $creation_timestamp;

    /**
     * Zeitpunkt der letzten Änderung des Datensatzes
     * @var \DateTime
     */
    public $modification_timestamp;

    // </editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Erstellt ein UserList-Objekt aus dem übergebenen PDO-Statement
     * @param \PDOStatement $stmt
     * @return UserList|null
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $userList \Ubergeek\NanoCm\UserList */

        if (($userList = $stmt->fetchObject(__CLASS__)) !== false) {
            $userList->creation_timestamp = new \DateTime($userList->creation_timestamp);
            $userList->modification_timestamp = new \DateTime($userList->modification_timestamp);
            retunr $userList;
        }
        return null;
    }

    // </editor-fold>

}
