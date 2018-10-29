<?php

/**
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Ubergeek\NanoCm;

/**
 * Definiert die verschiedenen Statuscodes für die unterschiedlichen Datensätze
 * 
 * Verwendet werden die Statuscodes beispielsweise für den Freischaltungscode
 * von Artikeln oder den Status von Benutzerkonten. Generell gilt: der
 * Statuscode 0 bedeutet: "aktiv".
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-19
 */
final class StatusCode {
    
    /**
     * Der Datensatz ist aktiv und nutzbar
     * @var integer
     */
    const ACTIVE = 0;

    /**
     * Der Datensatz erwartet noch einen Review (durch einen privilegierten)
     * Benutzer, bevor er freigeschaltet werden kann
     * @var integer
     */
    const REVIEW_REQUIRED = 300;
    
    /**
     * Der Datensatz muss moderiert werden, bevor er freigeschaltet wird.
     * @var integer
     */
    const MODERATION_REQUIRED = 400;
    
    /**
     * Der Datensatz wurde als Junk markiert und nicht freigeschaltet-
     * @var integer
     */
    const MARKED_AS_JUNK = 450;
    
    /**
     * Der Datensatz wurde gesperrt
     * @var integer
     */
    const LOCKED = 500;

    /**
     * Gibt eine Statusbeschreibung für den übergebenen numerischen Statuscode zurück
     * @param $statusId
     * @return string
     * @todo Lokalisierung
     */
    public static function convertStatusId($statusId) : string {
        switch ($statusId) {
            case self::ACTIVE:
                return 'Freigegeben';
            case self::MARKED_AS_JUNK:
                return 'Spam';
            case self::REVIEW_REQUIRED:
                return 'Review notwendig';
            case self::MODERATION_REQUIRED:
                return 'Moderation notwendig';
            case self::LOCKED:
                return 'Gesperrt';
        }
        return 'Unbekannt';
    }
}