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
 * Basis-Logikklasse für das CMS
 * @author agewert@ubergeek.de
 */
class NanoCm {
    
    // <editor-fold desc="Internal properties">
    
    /**
     * Beinhaltet die ContentManager-Instanz
     * @var \Ubergeek\NanoCm
     */
    private static $ncm = null;
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public properties">
    
    /**
     * Handle für die Basis-Datenbank
     * @var \PDO
     */
    public $basedb = null;
    
    /**
     * Referenz auf eine Instanz der ORM-Klasse
     * @var Orm
     */
    public $orm = null;

    /**
     * Log-Instanz
     * @var Log\LoggerInterface
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
    
    /**
     * Relative Basis-URL zur NanoCM-Installation
     * @var string
     */
    public $relativeBaseUrl;
    
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
        $this->relativeBaseUrl = substr($this->pubdir, strlen($_SERVER['DOCUMENT_ROOT']));
        
        if (empty($this->relativeBaseUrl)) {
            $this->relativeBaseUrl = '/';
        }

        // Ein (leerer) Logger wird immer instanziiert
        $this->log = new Log\Logger();
        
        // Zugriff auf die Datenbank herstellen
        $this->orm = new Orm($this->getDbHandle(), $this->log);
        
        // TODO Instanziierung nur, wenn Logging eingeschaltet        
        $this->log->addWriter(
            new Log\Writer\ChromeLoggerWriter(
                new Log\Filter\PriorityFilter(\Ubergeek\Log\Logger::DEBUG, Log\Filter\PriorityFilter::OPERATOR_MIN)
            )
        );
    }
    
    /**
     * Gibt den absoluten Namen der Site-spezifischen Datenbank-Datei zurück
     * @return string Datenbank-Dateiname
     */
    private function getSiteDbFilename() : string {
        $fname = $this->createPath(array(
            $this->sysdir,
            'db',
            'site.sqlite'
        ));
        
        $this->log->debug($fname);
        return $fname;
    }
    
    /**
     * Gibt das Datenbank-Handle für die Standard-System-Datenbank zurück
     * @return \PDO
     */
    private function getDbHandle() : \PDO {
        if ($this->basedb == null) {
            $this->basedb = new \PDO(
                'sqlite:' . $this->getSiteDbFilename()
            );
            $this->basedb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return $this->basedb;
    }
    
    /**
     * Überprüft, ob in einer SQLite-Datenbank eine bestimmte Tabelle vorhanden
     * ist
     * @param \PDO $pdo Datenbank-Handle
     * @param string $tableName Zu prüfender Tabellenname
     * @return boolean true, wenn die genannte Tabelle vorhanden ist, ansonsten
     *      false
     */
    private function isTableExisting(\PDO $pdo, string $tableName) {
        $stmt = $pdo->prepare('SELECT name FROM sqlite_master WHERE type=\'table\' AND name=:name ');
        $stmt->bindValue('name', $tableName);
        $stmt->execute();
        
        if (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $this->log->debug("Table $tableName is existing");
            return true;
        }
        return false;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public methods">
    
    /**
     * Gibt die (einzige) CM-Instanz zurück bzw erzeugt sie bei Bedarf
     * @param string $basepath
     * @return \Ubergeek\NanoCm\ContentManager
     */
    public static function createInstance(string $basepath) : NanoCm {
        self::$ncm = new NanoCm($basepath);
        return self::$ncm;
    }
    
    /**
     * Gibt die Referenz auf den verwendeten Logger zurück
     * @return \Ubergeek\Log\Logger
     */
    public function getLog() : Log\LoggerInterface {
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
        if (!file_exists($this->getSiteDbFilename())) return false;
        
        // Wenn Datenbank(-datei) vorhanden: prüfen, ob geforderte Tabellen
        // vorhanden
        $pdo = $this->getDbHandle();
        if (!$this->isTableExisting($pdo, 'setting')) return false;

        // Basiseinstellungen validieren
        // ...
        
        // Eventuell auch die Datenbank-Version überprüfen?
        // ...
        
        return true;
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
    
    /**
     * Kodiert einen String für die HTML-Ausgabe.
     * Der Eingabestring muss UTF8-kodiert sein.
     * @param string $string
     * @return HTML-kodierter String
     */
    public function htmlEncode($string) : string {
        return htmlentities($string, ENT_HTML5, 'utf-8');
    }
    
    /**
     * Konvertiert einen Eingabestring mit Formatierungs-Auszeichnungen in das
     * angegebene Zielformat
     * 
     * Die Konvertierung soll modular aufgebaut und konfigurierbar sein.
     * Das Eingabeformat orientiert sich an Markdown, weicht aber in einigen
     * Punkten davon ab. So ist beispielsweise kein eingebetteter HTML-Code
     * erlaubt.
     * 
     * @param string $input Eingabestring
     * @param string $targetFormat Das Zielformat
     * @return string Der ins Ausgabeformat konvertierte String
     */
    public function convertFormattedText(string $input, string $targetFormat = Constants::FORMAT_HTML) : string {
        $classname = 'Ubergeek\NanoCm\ContentConverter\\' . ucfirst($targetFormat) . 'Converter';
        $converter = new $classname();
        return $converter->convertFormattedText($this, $input);
    }
    
    // </editor-fold>
}
