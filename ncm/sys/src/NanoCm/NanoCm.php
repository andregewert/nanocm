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
use Ubergeek\Cache\FileCache;
use Ubergeek\Controller\HttpRequest;
use Ubergeek\Log;
use Ubergeek\Log\Logger;
use Ubergeek\Net\GeolocationService;
use Ubergeek\Net\UserAgentInfo;

/**
 * Basis-Logikklasse für das CMS
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @todo Lokalisierungsoptionen implementieren
 */
class NanoCm {
    
    // <editor-fold desc="Internal properties">
    
    /**
     * Beinhaltet die ContentManager-Instanz
     * @var \Ubergeek\NanoCm\NanoCm
     */
    private static $ncm = null;

    /**
     * FileCache für IP-Geolocation-Abfragen
     *
     * @var FileCache
     */
    private $ipcache;
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public properties">
    
    /**
     * Handle für die Basis-Datenbank
     *
     * @var \PDO
     */
    public $basedb = null;

    /**
     * PDO-Handle für die Statistik-Datenbank
     *
     * @var \PDO
     */
    public $statsdb = null;
    
    /**
     * Referenz auf eine Instanz der ORM-Klasse
     *
     * @var Orm
     */
    public $orm = null;
    
    /**
     * Session-Manager
     *
     * @var \Ubergeek\Session\SessionInterface
     */
    public $session = null;

    /**
     * Log-Instanz
     *
     * @var Log\LoggerInterface
     */
    public $log;
    
    /**
     * Absoluter Dateipfad zum öffentlichen Verzeichnis der Installationsbasis
     * (in der Regel das Document Root)
     *
     * @var string
     */
    public $pubdir;
    
    /**
     * Absoluter Dateipfad zum user-spezifischen Template-Pfad.
     * Achtung: Dieses Verzeichnis kann optional über die Systemeinstellungen um
     * einen weiteren Verzeichnisbestandteil ergänzt werden, um ohne Kopieren
     * und Löschen von Dateien zwischen Templates hin- und hergeschaltet werden
     * kann.
     *
     * @var string
     */
    public $tpldir;
    
    /**
     * Absoluter Dateipfad zum NCM-Untervezeichnis
     *
     * @var string 
     */
    public $ncmdir;
    
    /**
     * Absoluter Dateipfad zum Verzeichnis mit den Systemdateien des NCM
     *
     * @var string
     */
    public $sysdir;

    /**
     * Absoluter Dateipfad für den Cache
     *
     * @var string
     */
    public $cachedir;

    /**
     * Absoluter Dateipfad für die Ablage von Mediendateien
     *
     * @var string
     */
    public $mediadir;

    /**
     * Absoluter Dateipfad zur browscap-Datenbank
     *
     * @var string
     */
    public $browscappath;

    /**
     * Relative Basis-URL zur NanoCM-Installation
     *
     * @var string
     */
    public $relativeBaseUrl;

    /**
     * Gibt an, ob auf Fehlerseiten Informationen zu abgefangenen Exceptions detailliert ausgegeben werden sollen.
     * Achtung: Diese Funktion sollte in Produktivumgebungen unbedingt ausgeschaltet werden!
     *
     * @var bool true, wenn auf Fehlerseiten detaillierte Informatione zu abgefangenen Exceptions ausgegeben werden sollen
     */
    public $showExceptions = false;

    // </editor-fold>


    // <editor-fold desc="Contructor">

    /**
     * Dem Konstruktur muss der Pfad zur Installationsbasis übergeben werden.
     * Der Konstruktor ist als private deklariert, da die Klasse als Singleton
     * implementiert ist.
     * @param string $basepath
     */
    private function __construct($basepath) {

        // Pfade konfigurieren
        $this->pubdir = $basepath;
        $this->ncmdir = Util::createPath($this->pubdir, 'ncm');
        $this->sysdir = Util::createPath($this->pubdir, 'ncm', 'sys');
        $this->mediadir = Util::createPath($this->pubdir, 'ncm', 'sys', 'media');
        $this->cachedir = Util::createPath($this->pubdir, 'ncm', 'sys', 'cache');
        $this->relativeBaseUrl = substr($this->pubdir, strlen($_SERVER['DOCUMENT_ROOT']));
        $this->browscappath = Util::createPath($this->sysdir, 'db', 'lite_php_browscap.ini');
        if (empty($this->relativeBaseUrl)) {
            $this->relativeBaseUrl = '/';
        }

        // Ein (leerer) Logger wird immer instanziiert
        $this->log = new Log\Logger();

        // Zugriff auf die Datenbank herstellen
        $this->orm = new Orm(
            $this->getDbHandle(),
            $this->getStatsDbHandle(),
            $this->mediadir,
            $this->log
        );

        // Template-Verzeichnis konfigurieren
        $tpl = $this->orm->getSettingValue(Setting::SYSTEM_TEMPLATE_PATH);
        if (empty($tpl)) {
            $tpl = 'default';
        }
        $this->tpldir = Util::createPath($this->pubdir, 'tpl', $tpl);

        // ChromeLoggerWriter instanziieren, wenn eingeschaltet
        if ($this->orm->getSettingValue(Setting::SYSTEM_DEBUG_ENABLECHROMELOGGER) == 1) {
            $this->log->addWriter(
                new Log\Writer\ChromeLoggerWriter(
                    new Log\Filter\PriorityFilter(Logger::DEBUG, Log\Filter\PriorityFilter::OPERATOR_MIN)
                )
            );
        }

        // Ausgabe von Informationen zu abgefangenen Exceptions ist vom Seitenbetreuer konfigurierbar
        $this->showExceptions = $this->orm->getSettingValue(Setting::SYSTEM_DEBUG_SHOWEXCEPTIONS) == '1';

        // Seitenlänge im Administrationsbereich
        $this->orm->pageLength = intval($this->orm->getSettingValue(Setting::SYSTEM_ADMIN_PAGELENGTH));
        if ($this->orm->pageLength == 0) {
            $this->orm->pageLength = 20;
            $this->log->debug("Fehlerhafte Konfiguration Seitenlänge! Benutze Standardwert.");
        }

        // Session-Initialisierung
        $this->session = new \Ubergeek\Session\SimpleSession('ncm');
        $this->session->start();

        // Caches initialisieren
        $this->ipcache = new FileCache($this->cachedir, 60 *60 *24, 'ip-', $this->log);
    }

    // </editor-fold>

    
    // <editor-fold desc="Internal methods">
    
    /**
     * Gibt das Datenbank-Handle für die Standard-System-Datenbank zurück
     *
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
     * Gibt das PDO-Handle für die Statistik-Datenbank zurück
     *
     * @return \PDO
     */
    private function getStatsDbHandle() : \PDO {
        if ($this->statsdb == null) {
            $this->statsdb = new \PDO(
                'sqlite:' . $this->getStatsDbFilename()
            );
            $this->statsdb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return $this->statsdb;
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
            return true;
        }
        return false;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public methods">

    /**
     * Gibt die (einzige) CM-Instanz zurück bzw erzeugt sie bei Bedarf
     *
     * @param string $basepath
     * @return \Ubergeek\NanoCm\NanoCm
     */
    public static function createInstance(string $basepath) : NanoCm {
        self::$ncm = new NanoCm($basepath);
        return self::$ncm;
    }

    /**
     * Gibt den absoluten Namen der Site-spezifischen Datenbank-Datei zurück
     *
     * @return string Datenbank-Dateiname
     */
    public function getSiteDbFilename() : string {
        $fname = Util::createPath(
            $this->sysdir,
            'db',
            'site.sqlite'
        );
        return $fname;
    }

    /**
     * Gibt den absoluten Dateipfad zur Statistik-Datenbankdatei zurück
     *
     * @return string
     */
    public function getStatsDbFilename() : string {
        $fname = Util::createPath(
            $this->sysdir,
            'db',
            'stats.sqlite'
        );
        return $fname;
    }

    /**
     * Gibt true zurück, wenn an der aktuellen NCM-Session ein Benutzer
     * angemeldet ist.
     *
     * @return bool
     */
    public function isUserLoggedIn() : bool {
        return $this->getLoggedInUser() != null;
    }

    /**
     * Gibt - falls vorhanden - den angemeldeten Benutzer zurück.
     * Ist aktuell kein Benutzer angemeldet, wird false zurück gegeben.
     *
     * @return User|null
     */
    public function getLoggedInUser() {
        return $this->session->getVar('loggedInUser');
    }
    
    /**
     * Versucht, den angegebenen Benutzer mit einem bestimmten Passwort im
     * Klartext anzumelden.
     *
     * @param string $username Benutzername
     * @param string $passwdClear Das eingegebene Passwort im Klartext
     * @return bool true, wenn der Anmeldevorgang erfolgreich war, ansonsten
     *  false
     */
    public function tryToLoginUser(string $username, string $passwdClear) : bool {
        $user = $this->orm->getUserByCredentials($username, $passwdClear);
        $this->session->setVar('loggedInUser', $user);
        if ($user != null) {
            $this->log->debug('Update login timestamp');
            $this->orm->updateLoginTimestampByUserId($user->id);
        } else {
            $this->log->debug('Dont update login timestamp');
        }
        return $user != null;
    }
    
    /**
     * Meldet den aktuellen Benutzer von der Session ab
     */
    public function logoutUser() {
        $this->session->setVar('loggedInUser', null);
    }

    /**
     * Überprüft, ob die aktuelle NanoCM-Installation bereits korrekt
     * konfiguriert ist. Wenn dies nicht der Fall ist, wird der Controller einen
     * einfachen Konfigurations-Assistenten starten und die Datenbanken
     * initialisieren.
     *
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
     *
     * @param array $parts Bestandteile
     * @param bool $absolute Gibt an, ob die URL absolut (inklusive Protokoll
     * und Hostname) sein soll
     * @return string
     */
    public function createUrl(array $parts, bool $absolute = false) : string {
        // TODO Implementieren
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

        if (class_exists($classname)
            && array_key_exists('Ubergeek\NanoCm\ContentConverter\ContentConverterInterface', class_implements($classname))) {
            /* @var $converter \Ubergeek\NanoCm\ContentConverter\ContentConverterInterface */
            $converter = new $classname();
            return $converter->convertFormattedText($this, $input);
        }
        return '';
    }

    /**
     * Erstellt aus dem aktuellen Request ein AccessLogEntry-Objekt mit erweiterten Informationen
     *
     * @param HttpRequest $request Der aktuelle HTTP-Request
     * @return AccessLogEntry
     */
    public function createAccessLogEntry(HttpRequest $request) : AccessLogEntry {

        $ip = $_SERVER['REMOTE_ADDR'];                  // TODO Sollte aus $request ermittelt werden
        $useragent = $_SERVER['HTTP_USER_AGENT'];       // TODO Sollte aus $request ermittelt werden
        $enableBrowscap = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEBROWSCAP) == '1';
        $enableGeolocation = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEGEOLOCATION) == '1';
        $geolocationservice = new GeolocationService($this->ipcache);

        $entry = new AccessLogEntry();
        $entry->accesstime = new \DateTime();
        $entry->useragent = $useragent;
        $entry->method = $_SERVER['REQUEST_METHOD'];    // TODO Sollte aus $request ermittelt werden
        $entry->url = $request->requestUri->document;
        $entry->fullurl = $request->requestUri->getRequestUrl();
        $entry->sessionid = $this->session->getSessionId();

        // Browser- und Betriebssystem-Informationen abrufen
        $browser = null;
        if ($enableBrowscap && $this->browscappath != null) {
            try {
                $browser = get_browser($useragent);
            } catch (\Exception $ex) {
                $this->log->err("Fehler beim Aufruf von get_browser(): " . $ex->getMessage());
                $this->log->err($ex->getTrace());
            }
        }
        if ($browser != null) {
            $entry->osname = $browser->platform;
            $entry->osversion = '';
            $entry->browsername = $browser->browser;
            $entry->browserversion = $browser->version;
        }

        // Fallback: Informationen über UserAgentInfo() abrufen
        if ($browser == null) {
            $ua = new UserAgentInfo($useragent);
            $entry->osname = $ua->osName;
            $entry->osversion = $ua->osVersion;
            $entry->browsername = $ua->browserName;
            $entry->browserversion = $ua->browserVersion;
        }

        // Optional: Geolocation-Informationen abrufen
        if ($enableGeolocation) {
            $geolocation = null;
            try {
                $geolocation = $geolocationservice->getGeolocationForIpAddress($ip);
                $this->log->debug($geolocation);
            } catch (\Exception $ex) {
                $this->log->err("Fehler beim Aufruf von Geolocation-Informationen: " . $ex->getMessage());
                $this->log->err($ex->getTrace());
            }
            if ($geolocation != null) {
                $entry->country = $geolocation->country;
                $entry->countrycode = $geolocation->countryCode;
                $entry->region = $geolocation->region;
                $entry->regionname = $geolocation->regionName;
                $entry->city = $geolocation->city;
                $entry->zip = $geolocation->zip;
                $entry->timezone = $geolocation->timezone;
                $entry->latitude = $geolocation->latitude;
                $entry->longitude = $geolocation->longitude;
            } else {
                $entry->country = 'Unknown';
                $entry->regionname = 'Unknown';
            }
        }

        return $entry;
    }
    
    // </editor-fold>
}
