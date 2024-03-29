<?php

// NanoCM
// Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

namespace Ubergeek\NanoCm;
use PDO;
use Ubergeek\Cache\CacheInterface;
use Ubergeek\Cache\FileCache;
use Ubergeek\Controller\HttpRequest;
use Ubergeek\Log;
use Ubergeek\Log\Logger;
use Ubergeek\NanoCm\Media\MediaManager;
use Ubergeek\Net\GeolocationService;
use Ubergeek\Net\UserAgentInfo;
use Ubergeek\Session\SimpleSession;

/**
 * Includes the base logic for nanoCM
 *
 * This class includes the central business logic for nanoCM.
 * It should be instantiates once only at runtime, so it implemented as a singleton.
 * The constructor initializes all needed dependencies.
 *
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
    private static $ncm;

    // </editor-fold>
    
    
    // <editor-fold desc="Public properties">

    /**
     * A list of php modules which have to be enabled to run nanoCM correctly.
     * This one is used by the setup module.
     * @var string[]
     */
    public static $requiredPhpModules = array(
        'curl',
        'dom',
        'pcre',
        'PDO',
        'pdo_sqlite',
        'SimpleXML',
        'zip'
    );

    /**
     * Handle für die Basis-Datenbank
     *
     * @var PDO
     */
    public $basedb;

    /**
     * PDO-Handle für die Statistik-Datenbank
     *
     * @var PDO
     */
    public $statsdb;
    
    /**
     * Referenz auf eine Instanz der ORM-Klasse
     *
     * @var Orm
     */
    public $orm;
    
    /**
     * Session-Manager
     *
     * @var \Ubergeek\Session\SessionInterface
     */
    public $session;

    /**
     * Referenz auf den Media-Manager (ohne ORM)
     *
     * @var MediaManager
     */
    public $mediaManager;

    /**
     * Reference to the installation manager
     * @var InstallationManager
     */
    public $installationManager;

    /**
     * Log-Instanz
     *
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
     *
     * @var string
     */
    public $tpldir;

    /**
     * @var string Name of the current template dir (relative to ncm/tpl)
     */
    public $tplname;
    
    /**
     * Absoluter Dateipfad zum NCM-Untervezeichnis
     *
     * @var string 
     */
    public $ncmdir;
    
    /**
     * Absoluter Dateipfad zum Verzeichnis mit den Systemdateien des NCM
     * @var string
     */
    public $sysdir;

    /**
     * Absoluter Dateipfad für den Cache
     * @var string
     */
    public $cachedir;

    /**
     * Absoluter Dateipfad für die Ablage von Mediendateien
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

    /**
     * Cache für IP-Geolocation-Abfragen
     *
     * @var CacheInterface
     */
    public $ipCache;

    /**
     * Cache für die Medienbearbeitung
     *
     * @var CacheInterface
     */
    public $mediaCache;

    /**
     * Cache für Captchas
     *
     * @var CacheInterface
     */
    public $captchaCache;

    /**
     * Cache für generierte E-Books
     *
     * @var CacheInterface
     */
    public $ebookCache;

    /**
     * Cache für das Sperren von IP-Adressen für die Kommentar-Funktion
     *
     * @var CacheInterface
     */
    public $commentIpCache;

    /**
     * Einfaches PDO mit Basisinformationen zur nanocm-Installation
     *
     * @var object
     */
    public $versionInfo;

    /**
     * Kürzel für die zu verwendende Sprache (z. B. de)
     *
     * @var string
     */
    public $lang;

    /**
     * Vollständiges Locale-Kürzel für die Konfiguration von PHP (z. B. de_DE.utf8)
     *
     * @var string
     */
    public $locale;

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
        } else if (substr($this->relativeBaseUrl, -1) != '/') {
            $this->relativeBaseUrl .= '/';
        }

        // Basisinformationen zur aktuellen Installation auslesen
        try {
            $this->versionInfo = json_decode(file_get_contents(Util::createPath($this->sysdir, 'version.json')));
        } catch (\Throwable $th) {
            // TODO Fehler erst einmal ignorieren?
            // TODO version.json erst bei Ersteinrichtung erzeugen???
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
        $this->tplname = $tpl;
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

        // Sprache konfigurieren
        $this->lang = $this->orm->getSettingValue(Setting::SYSTEM_LANG);
        $this->locale = $this->orm->getSettingValue(Setting::SYSTEM_LOCALE);
        if (!empty($this->locale)) {
            setlocale(LC_ALL, $this->locale);
            $this->log->debug("Setting locale to $this->locale");
        }

        // Session-Initialisierung
        session_cache_limiter('public');
        if ($this->hasCurrentUserAcceptedPrivacyPolicy()) {
            $this->session = new SimpleSession('ncm');
            $this->session->start();
        }

        // Caches initialisieren
        $ttl = (int)$this->orm->getSettingValue(Setting::SYSTEM_CACHE_GEOLOCATION_TTL, 24);
        $this->ipCache = new FileCache($this->cachedir, 60 *60 *$ttl, 'ip-', $this->log);
        $ttl = (int)$this->orm->getSettingValue(Setting::SYSTEM_CACHE_MEDIA_TTL, 24 * 100);
        $this->mediaCache = new FileCache($this->cachedir, 60 *60 *$ttl, 'media-', $this->log);
        $ttl = (int)$this->orm->getSettingValue(Setting::SYSTEM_CACHE_COMMENTS_TTL, 1);
        $this->commentIpCache = new FileCache($this->cachedir, 60 *60 *$ttl, 'cmt-', $this->log);
        $ttl = (int)$this->orm->getSettingValue(Setting::SYSTEM_CACHE_EBOOKS_TTL, 24 * 7);
        $this->ebookCache = new FileCache($this->cachedir, 60 *60 *$ttl, 'ebook-', $this->log);
        $this->captchaCache = new FileCache($this->cachedir, 60 *60 *4, 'cpt-', $this->log);

        // Installation manager
        $this->installationManager = new InstallationManager($this);

        $this->mediaManager = new MediaManager($this->mediaCache, $this->log);
        $this->log->debug($this->versionInfo);
    }

    // </editor-fold>

    
    // <editor-fold desc="Internal methods">

    /**
     * Gibt das Datenbank-Handle für die Standard-System-Datenbank zurück
     *
     * @return PDO
     */
    private function getDbHandle() : PDO {
        if ($this->basedb == null) {
            $this->basedb = new PDO(
                'sqlite:' . $this->getSiteDbFilename()
            );
            $this->basedb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->basedb;
    }

    /**
     * Gibt das PDO-Handle für die Statistik-Datenbank zurück
     *
     * @return PDO
     */
    private function getStatsDbHandle() : PDO {
        if ($this->statsdb == null) {
            $this->statsdb = new PDO(
                'sqlite:' . $this->getStatsDbFilename()
            );
            $this->statsdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->statsdb;
    }
    
    /**
     * Überprüft, ob in einer SQLite-Datenbank eine bestimmte Tabelle vorhanden
     * ist
     * @param PDO $pdo Datenbank-Handle
     * @param string $tableName Zu prüfender Tabellenname
     * @return boolean true, wenn die genannte Tabelle vorhanden ist, ansonsten
     *      false
     */
    private function isTableExisting(PDO $pdo, string $tableName) {
        $stmt = $pdo->prepare('SELECT name FROM sqlite_master WHERE type=\'table\' AND name=:name ');
        $stmt->bindValue('name', $tableName);
        $stmt->execute();
        
        if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            return true;
        }
        return false;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="Public methods">

    /**
     * Clears all caches used by nano|cm
     *
     * At the moment there are caches for:
     * - Geolocation requests
     * - Automatically scaled image files
     * - IP addresses that already commented
     * - Generated e-books
     * - Captchas generated for the commenting function
     *
     * Clearing the caches can be useful after chaging templates
     * that affect e-book generation or if image format definitions
     * have changed. At the moment, the media cache is cleared every
     * time a format definition changes, so the only reason to clear
     * caches manually seems to be problems related to changes on the
     * templates for e-book generation.
     */
    public function clearAllCaches() {
        $this->ipCache->clear();
        $this->mediaCache->clear();
        $this->commentIpCache->clear();
        $this->ebookCache->clear();
        $this->captchaCache->clear();
    }

    /**
     * Returns true if the current user (which runs the current request) has accepted the
     * privacy policy.
     *
     * This methods just uses the superglobal $_COOKIE array. This should be replaced by
     * some accessors in the Request interfaces.
     *
     * @return bool
     */
    public function hasCurrentUserAcceptedPrivacyPolicy() {
        if (!array_key_exists('privacypolicy_accepted', $_COOKIE)) return false;
        return $_COOKIE['privacypolicy_accepted'] == 1;
    }

    public function setPrivacyPolicyCookie() {
        setcookie(
            'privacypolicy_accepted',
            '1',
            time() + (60 *60 *24 *365),
            $this->relativeBaseUrl
        );
    }

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
        if ($this->session === null) return null;
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
        // Login is not possible / allowed when user didnt accept privacy policy
        if ($this->session == null) return false;

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
        if ($this->session !== null) {
            $this->session->setVar('loggedInUser', null);
        }
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
        
        // Wenn Datenbank(-datei) vorhanden: prüfen, ob geforderte Tabellen vorhanden
        $pdo = $this->getDbHandle();
        if (!$this->isTableExisting($pdo, 'setting')) return false;

        return true;
    }

    /**
     * Erstellt aus dem aktuellen Request ein AccessLogEntry-Objekt mit erweiterten Informationen
     *
     * @param HttpRequest $request Der aktuelle HTTP-Request
     * @return AccessLogEntry
     * @throws \Exception
     */
    public function createAccessLogEntry(HttpRequest $request) : AccessLogEntry {

        $ip = $_SERVER['REMOTE_ADDR'];                  // TODO Sollte aus $request ermittelt werden
        $useragent = $_SERVER['HTTP_USER_AGENT'];       // TODO Sollte aus $request ermittelt werden
        $enableBrowscap = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEBROWSCAP) == '1';
        $enableGeolocation = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEGEOLOCATION) == '1';
        $geolocationservice = new GeolocationService($this->ipCache);

        $entry = new AccessLogEntry();
        $entry->accesstime = new \DateTime();
        $entry->useragent = $useragent;
        $entry->method = $_SERVER['REQUEST_METHOD'];    // TODO Sollte aus $request ermittelt werden
        $entry->url = $request->requestUri->document;
        $entry->fullurl = $request->requestUri->getRequestUrl();
        $entry->sessionid = ($this->session !== null)? $this->session->getSessionId() : '';

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


    // <editor-fold desc="Captcha and anti spam methods">

    /**
     * Erstellt ein zufälliges Captcha, legt es unter seiner ID im Cache ab und gibt das Captcha zurück
     *
     * @return Captcha
     */
    public function createCaptcha() : Captcha {
        $captcha = new Captcha();
        $this->captchaCache->put($captcha->captchaId, $captcha);
        return $captcha;
    }

    /**
     * Überprüft, die Lösung eines Captchas
     *
     * @param string $captchaId ID des zu prüfenden Captchas
     * @param string $userInput Benutzereingabe (Lösung)
     * @return bool true, wenn die Benutzereingabe die korrekte Lösung des Captchas enthält
     */
    public function isCaptchaSolved(string $captchaId, $userInput) {
        /* @var $captcha \Ubergeek\NanoCm\Captcha */

        $this->log->debug("Checking captcha with id $captchaId / user input: $userInput");

        if (preg_match('/^[a-z0-9]{32}$/i', $captchaId) !== 1) return false;
        if (intval($userInput) != $userInput) return false;

        $captcha = $this->captchaCache->get($captchaId);
        if ($captcha == null) {
            $this->log->debug("Captcha with id $captchaId not found!");
            return false;
        }

        if ($captcha->operator == '-') {
            $this->log->debug("Solution should be " . ($captcha->valueA -$captcha->valueB));
            return $userInput == $captcha->valueA -$captcha->valueB;
        }
        $this->log->debug("Solution should be " . ($captcha->valueA +$captcha->valueB));
        return $userInput == $captcha->valueA +$captcha->valueB;
    }

    /**
     * Sperrt die angegebene Adresse temporär für die Kommentarfunktion
     *
     * IP-Adressen, die erfolgreich einen Kommentar abgesetzt haben, sollen in der Folge mindestens zwei Minuten
     * lang keine weiteren Kommentare abgeben können. Dieser Mechanismus soll in erster Linie massenhaft abgesetzten
     * Junk-Kommentaren entgegenwirken.
     *
     * @param $ip Zu sperrende IP-Adresse
     * @return void
     */
    public function blockIpForComments($ip) {
        $this->commentIpCache->put($ip, true);
    }

    /**
     * Überprüft, ob die angegebene IP-Adresse (temporär) für die Kommentarfunktion gesperrt ist
     *
     * @param $ip Zu überprüfende IP-Adresse
     * @return bool true, wenn die übergebene IP-Adresse aktuell für die Kommentarfunktion gesperrt ist
     */
    public function isIpBlockedForComments($ip) {
        return $this->commentIpCache->get($ip) != null;
    }

    // </editor-fold>
}
