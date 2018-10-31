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
 * Bildet den Eintrag in einer benutzerdefinierten Liste ab
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-02-27
 */
class UserListItem {

    // <editor-fold desc="Properties">

    /**
     * Datensatz-ID
     * @var integer
     */
    public $id;

    /**
     * ID der übergeordneten benutzerdefinierten Liste
     * @var integer
     */
    public $userlist_id;

    /**
     * Optionale Verküpfung auf einen übergeordneten Listeneintrag
     * @var integer|null
     */
    public $parent_id;

    /**
     * Statuscode (Freischaltungscode) für diesen Listeneintrag
     * @var integer
     */
    public $status_code;

    /**
     * Erstellungszeitpunkt für diesen Listeneintrag
     * @var \DateTime
     */
    public $creation_timestamp;

    /**
     * Zeitpunkt der letzten Änderung für diesen Listeneintrag
     * @var \DateTime
     */
    public $modification_timestamp;

    /**
     * Titel bzw. Name dieses Listeneinrags
     * @var string
     */
    public $title;

    /**
     * Inhalt des Listeneintrags
     * @var string
     */
    public $content;

    /**
     * Optionale(r) Parameter für diesen Listeneintrag
     * @var string
     */
    public $parameters;

    /**
     * Numerischer Sortiercode innerhalb der Liste bzw. innerhalb der aktuellen Hierarchie
     * @var integer
     */
    public $sorting_code;

    // </editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Erstellt ein UserList-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return UserListItem|null
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $userListItem \Ubergeek\NanoCm\UserListItem */
        if (($userListItem = $stmt->fetchObject(__CLASS__)) !== false) {
            $userListItem->creation_timestamp = new \DateTime($userListItem->creation_timestamp);
            $userListItem->modification_timestamp = new \DateTime($userListItem->modification_timestamp);
            return $userListItem;
        }
        return null;
    }

    // </editor-fold>

}