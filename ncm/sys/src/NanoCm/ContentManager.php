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

use Ubergeek\Log;

/**
 * Zentrale Content-Management-Klasse
 * 
 * Als Singleton implementiert.
 * @author André Gewert <agewert@ubergeek.de>
 * @copyright (c) 2017, André Gewert
 */
class ContentManager {
    
    /**
     * Beinhaltet die ContentManager-Instanz
     * @var ContentManager
     */
    private static $cm = null;
    
    /**
     * Log-Instanz
     * @var Log\Logger
     */
    private $log;
    
    private function __construct($basepath) {
        // TODO Pfade konfigurieren

        // TODO Zugriff auf die Datenbank herstellen
        
        // TODO Instanziierung nur, wenn Logging eingeschaltet
        $this->log = new Log\Logger();
        $this->log->addWriter(
            new Log\Writer\ChromeLoggerWriter()
        );
    }
    
    /**
     * Gibt die (einzige) CM-Instanz zurück bzw erzeugt sie bei Bedarf
     * @param string $basepath
     * @return \Ubergeek\NanoCm\ContentManager
     */
    public static function getInstance(string $basepath) : ContentManager {
        if (self::$cm == null) {
            self::$cm = new ContentManager($basepath);
        }
        return self::$cm;
    }
    
    /**
     * Gibt die Referenz auf den verwendeten Logger zurück
     * @return \Ubergeek\Log\Logger
     */
    public function getLog() : Log\Logger {
        return $this->log;
    }
}