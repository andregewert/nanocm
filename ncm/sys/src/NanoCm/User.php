<?php

/* 
 * Copyright (C) 2017 André Gewert <agewert@ubergeek.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm;

/**
 * Bildet einen NCM-Benutzer ab
 * @author agewert@ubergeek.de
 * @created 2017-10-27
 */
class User {
    
    /**
     * Eindeutige ID des Benutzers
     * @var integer
     */
    public $id;

    /**
     * Statuscode
     * @var integer
     */
    public $status_code;
    
    /**
     * Zeitpunkt der Erstellung
     * @var \DateTime
     */
    public $creation_timestamp;
    
    /**
     * Zeitpunkt der letzten Änderung
     * @var \DateTime
     */
    public $modification_timestamp;
    
    /**
     * Vorname der Person
     * @var string
     */
    public $firstname;
    
    /**
     * Nachname der Person
     * @var string
     */
    public $lastname;
    
    /**
     * Benutzername (Login-Name) des Benutzers
     * @var string
     */
    public $username;
    
    /**
     * Einwegverschlüsseltes Benutzer-Passwort
     * @var string
     */
    public $password;
    
    /**
     * Zeitpunkt der letzten Anmeldung am NanoCM
     * @var \DateTime
     */
    public $last_login_timestamp;
    
    /**
     * E-Mail-Adresse des Benutzers
     * @var string
     */
    public $email;
    
    /**
     * Benutzerkontentyp
     * @var integer
     */
    public $usertype;
    
    
    // <editor-fold desc="Public methods">

    /**
     * Gibt den vollständigen Namen im Format Nachname, Vorname zurück
     * @return string
     */
    public function getFullName() : string {
        return $this->lastname . ', ' . $this->firstname;
    }
    
    /**
     * Erstellt ein User-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return User
     */
    public final static function fetchFromPdoStmt(\PDOStatement $stmt) {
        if (($user = $stmt->fetchObject(__CLASS__)) !== false) {
            $user->creation_timestamp = new \DateTime($user->creation_timestamp);
            $user->modification_timestamp = new \DateTime($user->modification_timestamp);
            if ($user->last_login_timestamp == '') {
                $user->last_login_timestamp = null;
            } else {
                $user->last_login_timestamp = new \DateTime($user->last_login_timestamp);
            }
            return $user;
        }
        return null;
    }
    
    // </editor-fold>
}