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

namespace Ubergeek;
use Ubergeek\Log;


class NanoCm {

    // <editor-fold desc="Constants">

    /**
     * Pfad zum Basisverzeichnis der Installation
     */
    const VAR_SYS_BASEDIR = 'sys.basedir';
    
    /**
     * Pfad zum (installations-spezifischen) Template-Pfad.
     * Hier sollten auch zugehörige Images, Scripte etc. liegen
     */
    const VAR_SYS_TPLDIR = 'sys.tpldir';
    
    // </editor-fold>
    
    
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
    public static function getInstance(string $basepath) : NanoCm {
        if (self::$cm == null) {
            self::$cm = new NanoCm($basepath);
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
    
    /**
     * Überprüft, ob die aktuelle NanoCM-Installation bereits korrekt
     * konfiguriert ist. Wenn dies nicht der Fall ist, wird der Controller einen
     * einfachen Konfigurations-Assistenten starten und die Datenbanken
     * initialisieren.
     * @todo Implementieren
     * @return true, wenn die Installation korrekt konfiguriert ist
     */
    public function isNanoCmConfigured() : bool {
        
        // Prüfen, ob Datenbank vorhanden
        
        // Basiseinstellungen validieren
        
        return false;
    }
}
