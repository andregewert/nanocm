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

/**
 * Basis-Logikklasse für das CMS
 * @author agewert@ubergeek.de
 */
class NanoCm {
    
    // <editor-fold desc="Internal properties">
    
    /**
     * Beinhaltet die ContentManager-Instanz
     * @var \Ubergeek\NanoCm
     */
    private static $cm = null;
    
    /**
     * Handle für die Basis-Datenbank
     * @var \PDO
     */
    public $basedb = null;
    
    /**
     * Referenz auf eine Instanz der ORM-Klasse
     * @var NanoCm\DbMapper
     */
    public $dbMapper = null;
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public properties">
    
    /**
     * Log-Instanz
     * @var Log\Logger
     */
    public $log;
    
    /**
     * Absoluter Dateipfad zum öffentlichen Verzeichnis der Installationsbasis
     * (in der Regel das Document Root)
     * @var string
     */
    public $pubdir;
    
    /**
     * Absoluter Dateipfad zum user-spezifischen Template-Pfad.
     * Achtung: Dieses Verzeichnis kann optional über die Systemeinstellungen um
     * einen weiteren Verzeichnisbestandteil ergänzt werden, um ohne Kopieren
     * und Löschen von Dateien zwischen Templates hin- und hergeschaltet werden
     * kann.
     * @var string
     */
    public $tpldir;
    
    /**
     * Absoluter Dateipfad zum NCM-Untervezeichnis
     * @var string 
     */
    public $ncmdir;
    
    /**
     * Absoluter Dateipfad zum Verzeichnis mit den Systemdateien des NCM
     * @var string
     */
    public $sysdir;
    
    // </editor-fold>
    
    
    // <editor-fold desc="Internal methods">
    
    /**
     * Dem Konstruktur muss der Pfad zur Installationsbasis übergeben werden.
     * Der Konstruktor ist als private deklariert, da die Klasse als Singleton
     * implementiert ist.
     * @param string $basepath
     */
    private function __construct($basepath) {
        // Pfade konfigurieren
        $this->pubdir = $basepath;
        $this->tpldir = $this->createPath(array($this->pubdir, 'tpl'));
        $this->ncmdir = $this->createPath(array($this->pubdir, 'ncm'));
        $this->sysdir = $this->createPath(array($this->pubdir, 'ncm', 'sys'));
        
        // Zugriff auf die Datenbank herstellen
        $this->dbMapper = new NanoCm\DbMapper($this->getDbHandle());
        
        // TODO Instanziierung nur, wenn Logging eingeschaltet
        $this->log = new Log\Logger();
        $this->log->addWriter(
            new Log\Writer\ChromeLoggerWriter()
        );
    }
    
    /**
     * Gibt das Datenbank-Handle für die Standard-System-Datenbank zurück
     * @return \PDO
     */
    protected function getDbHandle() : \PDO {
        if ($this->basedb == null) {
            $this->basedb = new \PDO(
                'sqlite:' . $this->createPath(array(
                    $this->sysdir,
                    'db',
                    'site.sqlite'
                ))
            );
            $this->basedb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return $this->basedb;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public methods">
    
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
    
    /**
     * Fügt eine URL (als String) zusammen
     * @param array $parts Bestandteile
     * @param bool $absolute Gibt an, ob die URL absolut (inklusive Protokoll
     * und Hostname) sein soll
     */
    public function createUrl(array $parts, bool $absolute = false) : string {
        // TODO Implementieren
    }
    
    /**
     * Fügt die übergebenen Pfadbestandteile mit dem System-Verzeichnistrenner
     * zu einer Pfadangabe zusammen
     * @param array $parts Pfadbestandteile
     * @return string Der zusammengesetzte Pfad
     */
    public function createPath(array $parts) : string {
        return join(DIRECTORY_SEPARATOR, $parts);
    }
    
    // </editor-fold>
}
