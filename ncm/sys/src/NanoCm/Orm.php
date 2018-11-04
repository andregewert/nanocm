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

use Ubergeek\NanoCm\Exception\InvalidDataException;

/**
 * Kapselt alle system-internen Datenbank-Funktionen in einer Klasse.
 * 
 * Alle Object-Relation-Mapping-Methoden für integrale Bestandteile des NanoCM
 * werden in dieser Klasse zur Verfügung gestellt. Eine Instanz dieses ORM ist
 * über den jeweiligen Controller in jedem Template zugänglich.
 * 
 * Optionale Zusatzmodule können ihre eigene Datenbank-Funktionalitäten über
 * eigene Klassen-Instanzen bereitstellen.
 * 
 * Zu den Grundfunktionen des NanoCM gehören:
 * 
 * - Artikelverwaltung
 * - Benutzerverwaltung
 * - Verwaltung von Kommentaren und Trackbacks
 * - Grundlegende Statistiken
 * - Systemeinstellungen
 * 
 * Zu den optionalen Modulen gehört beispielsweise die Medienverwaltung.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @todo Caching / Converting eventuell in die Controller-Klassen verschieben
 */
class Orm {

    // <editor-fold desc="Properties">

    /**
     * Handle für die Basis-Datenbank
     *
     * @var \PDO
     */
    private $basedb;

    /**
     * PDO-Handle für die Statistik-Datenbank
     *
     * @var \PDO
     */
    private $statsdb;
    
    /**
     * Optionale Log-Instanz
     *
     * @var \Ubergeek\Log\LoggerInterface
     */
    private $log;

    /**
     * Seitenlänge für Suchergebnisse
     *
     * @var int
     */
    public $pageLength = 5;

    /**
     * Cache für den User-ID-Converter
     *
     * @var User[]
     */
    private static $userCache;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * Dem Konstruktor muss das Datenbank-Handle für die Basis-Systemdatenbank
     * übergeben werden.
     *
     * @param \PDO $dbhandle PDO-Handle für die Site-Datenbank
     * @param \PDO $statshandle PDO-Handle für die Statistik-Datenbank
     * @param \Ubergeek\Log\LoggerInterface|null $log
     */
    public function __construct(\PDO $dbhandle, \PDO $statshandle, \Ubergeek\Log\LoggerInterface $log = null) {
        $this->basedb = $dbhandle;
        $this->statsdb = $statshandle;
        $this->log = $log;
        
        if ($this->log == null) {
            $this->log = new \Ubergeek\Log\Logger();
        }
    }

    // </editor-fold>


    // <editor-fold desc="Statistics">

    /**
     * Durchsucht das AccessLog
     *
     * @param int $year Jahreszahl (vierstellig)
     * @param int $month Monatszahl (1-12)
     * @param bool $countOnly
     * @param null|int $page
     * @param null|int $limit
     * @return array|mixed
     */
    public function searchAccessLog($year, $month, $countOnly = false, $page = null, $limit = null) {
        $stats = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= 'FROM accesslog WHERE
                    strftime(\'%Y\', accesstime) = :year 
                    AND strftime(\'%m\', accesstime) = :month ';

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= ' ORDER BY accesstime DESC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter setzen
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $year, \PDO::PARAM_STR);
        $stmt->bindValue('month', $month, \PDO::PARAM_STR);
        $stmt->execute();

        // Ergebnis auslesen
        if ($countOnly) return $stmt->fetchColumn();
        while (($entry = AccessLogEntry::fetchFromPdoStatement($stmt)) !== null) {
            $stats[] = $entry;
        }

        return $stats;
    }

    /**
     * Ermittelt die Monatsstatistiken zu den verwendeten Browsern für den angegebenen Monat
     *
     * @param int $year Jahreszahl (vierstellig)
     * @param int $month Monatszahl (1-12)
     * @return array Zugriffszahlen für den angegebenen Monat
     */
    public function getMonthlyBrowserStats($year, $month) {
        $sql = 'SELECT year, month, browsername, sum(count) AS sumcount
                FROM monthlybrowser
                WHERE year = :year AND month = :month
                GROUP BY year, month, browsername
                ORDER BY sumcount DESC, browsername ASC ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $year);
        $stmt->bindValue('month', $month);
        $stmt->execute();

        $stats = array();
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $stats[] = $row;
        }
        return $stats;
    }

    /**
     * Ermittelt die Monatsstatistiken zu den verwendeten Betriebssystemen für den angegebenen Monat
     *
     * @param int $year Jahreszahl (viertstellig)
     * @param int $month Monatszahl (1-12)
     * @return array Zugriffszahlen für den angegebenen Monat
     */
    public function getMonthlyOsStats($year, $month) {
        $sql = 'SELECT year, month, osname, sum(count) AS sumcount
                FROM monthlyos
                WHERE year = :year AND month = :month
                GROUP BY year, month, osname
                ORDER BY sumcount DESC, osname ASC ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $year);
        $stmt->bindValue('month', $month);
        $stmt->execute();

        $stats = array();
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $stats[] = $row;
        }
        return $stats;
    }

    /**
     * Ermittelt die Monatssatistiken zu den Herkunftsregionen für den angegebenen Monat
     *
     * @param int $year Jahreszahl (vierstellig)
     * @param int $month Monatszahl (1-12)
     * @return array Zugriffszahlen für den angegebenen Monat
     */
    public function getMonthlyRegionStats($year, $month) {
        $sql = 'SELECT year, month, country, regionname, sum(count) AS sumcount
                FROM monthlyregion
                WHERE year = :year AND month = :month
                GROUP BY year, month, country, regionname
                ORDER BY sumcount DESC, country ASC, regionname ASC ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $year);
        $stmt->bindValue('month', $month);
        $stmt->execute();

        $stats = array();
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $stats[] = $row;
        }
        return $stats;
    }

    /**
     * Ermittelt die Monatsstatistiken zu den abgerufenen URLs für den angegebenen Monat
     *
     * @param int $year Jahreszahl (vierstellig)
     * @param int $month Monatszahl (1-12)
     * @return array Zugriffszahlen für den angegebenen Monat
     */
    public function getMonthlyUrlStats($year, $month) {
        $sql = 'SELECT year, month, url, sum(count) AS sumcount
                FROM monthlyurl
                WHERE year = :year AND month = :month
                GROUP BY year, month, url
                ORDER BY sumcount DESC, url ASC ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $year);
        $stmt->bindValue('month', $month);
        $stmt->execute();

        $stats = array();
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $stats[] = $row;
        }
        return $stats;
    }

    /**
     * Ermittelt die Anzahl eindeutiger Session-ID für den angegebenen Monat.
     * Die Session-IDs werden ausschließlich im Accesslog mitgeschrieben. Statistiken zu den Sessions funktionieren also
     * nur dann, wenn das ausführliche Accesslog eingeschaltet ist!
     *
     * @param int $year Jahreszahl (vierstellig)
     * @param int $month Monatszahl (1-12)
     * @return array Zugriffszahlen für den angegebenen Monat
     */
    public function countUniqueSessionIds($year, $month) {
        $sql = 'SELECT COUNT(DISTINCT sessionid) AS c
                FROM accesslog
                WHERE  strftime(\'%Y\', accesstime) = :year 
                AND strftime(\'%m\', accesstime) = :month ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $year, \PDO::PARAM_STR);
        $stmt->bindValue('month', $month, \PDO::PARAM_STR);
        $stmt->execute();

        $stats = array();
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $stats[] = $row;
        }
        return $stats;
    }

    /**
     * Protokolliert einen Seitenzugriff in einem ausführlichen Format in der Accesslog-Tabelle.
     * Hinweis: Das Führen eines ausführlichen Accesslogs kann die Geschwindigkeit der Site negativ beeinflussen und
     * sehr viel Platz auf dem Webspace beanspruchen. Über die Einstellungen des NanoCM kann diese Funktionalität auch
     * ausgeschaltet werden. Die etwas platzsparenderen und vereinfachten monatlichen Statistiken können unabhängig
     * davon erfasst werden.
     *
     * Das Accesslog wird auf einen Zeitraum von einem Jahr begrenzt, was durch eine automatische Garbage Collection
     * erreicht wird.
     *
     * @param AccessLogEntry $entry Der zu speichernde Accesslog-Eintrag
     * @return void
     */
    public function logHttpRequest(AccessLogEntry $entry) {
        $this->log->debug('logHttpRequest');

        if (rand(0, 99) %97 <= 3) {
            $this->removeOldStatistics();
        } else {
            $this->log->debug("Skipping garbage collection");
        }

        try {
            $this->saveAccesslog($entry);
        } catch (\Exception $ex) {
            $this->log->err($ex);
        }
    }

    /**
     * Speichert die vereinfachten Zugriffsstatistiken.
     * Diese Methode schreibt keinen ausführlichen Accesslog-Eintrag, sondern führt nur die monatlichen Statistiken zu
     * Browser, Betriebssystem, Region und aufgerufener URL.
     *
     * @param AccessLogEntry $entry
     * @param bool $enableGeolocation
     * @return void
     */
    public function logSimplifiedStats(AccessLogEntry $entry, bool $enableGeolocation = false) {
        $this->log->debug('logSimplifiedStats');

        if (rand(0, 99) %97 <= 3) {
            //$this->removeOldStatistics();
            // TODO Evtl. auch auf den vereinfachten Statistiken eine automatische Garbage Collection durchführen
        } else {
            //$this->log->debug("Skipping garbage collection");
        }

        try {
            $this->countMonthlyBrowserStats($entry->accesstime, $entry->browsername, $entry->browserversion);
            $this->countMonthlyOsStats($entry->accesstime, $entry->osname, $entry->osversion);
            $this->countMonthlyUrlStats($entry->accesstime, $entry->url);
            if ($enableGeolocation) {
                $this->countMonthlyRegionStats($entry->accesstime, $entry->country, $entry->countrycode, $entry->regionname);
            }
        } catch (\Exception $ex) {
            $this->log->err($ex);
        }
    }

    /**
     * Löscht Einträge aus der Tabelle accesslog, die älter sind als 365 Tage
     *
     * @return void
     */
    protected function removeOldStatistics() {
        $this->log->debug("Running garbage collection on accesslog");
        $sql = 'DELETE FROM accesslog WHERE JULIANDAY(\'now\') -JULIANDAY(accesstime) > 365 ';
        $this->statsdb->exec($sql);
    }

    /**
     * Aktualisiert die Monatsstatistiken für die angegebene URL
     *
     * @param \DateTime $accessDateTime Zugriffszeitpunkt für die URL
     * @param string $url Abgerufene URL
     * @return void
     */
    protected function countMonthlyUrlStats(\DateTime $accessDateTime, $url) {
        $sql = 'SELECT COUNT(*) FROM monthlyurl WHERE
                year = :year AND month = :month AND url = :url ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('url', $url);
        $stmt->execute();
        $existing = $stmt->fetchColumn() > 0;

        if ($existing) {
            $sql = 'UPDATE monthlyurl SET count = count +1
                    WHERE year = :year AND month = :month AND url = :url ';
        } else {
            $sql = 'INSERT INTO monthlyurl (year, month, url, count)
                    VALUES (:year, :month, :url, 1)';
        }
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('url', $url);
        $stmt->execute();
    }

    /**
     * Aktualisiert die Monatsstatistiken für die übergebenen Regionsinformationen
     *
     * @param \DateTime $accessDateTime Zeitpunkt des Zugriffs
     * @param string $country Ländername
     * @param string $countrycode Ländercode (ISO)
     * @param string $regionname Name der Region
     * @return void
     */
    protected function countMonthlyRegionStats(\DateTime $accessDateTime, $country, $countrycode, $regionname) {
        $sql = 'SELECT COUNT(*) FROM monthlyregion WHERE
                year = :year AND month = :month AND country = :country AND countrycode = :countrycode AND regionname = :regionname ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('country', $country);
        $stmt->bindValue('countrycode', $countrycode);
        $stmt->bindValue('regionname', $regionname);
        $stmt->execute();
        $existing = $stmt->fetchColumn() > 0;

        if ($existing) {
            $sql = 'UPDATE monthlyregion SET count = count +1
                    WHERE year = :year AND month = :month AND country = :country AND countrycode = :countrycode AND regionname = :regionname';
        } else {
            $sql = 'INSERT INTO monthlyregion (year, month, country, countrycode, regionname, count)
                    VALUES (:year, :month, :country, :countrycode, :regionname, 1)';
        }
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('country', $country);
        $stmt->bindValue('countrycode', $countrycode);
        $stmt->bindValue('regionname', $regionname);
        $stmt->execute();
    }

    /**
     * Aktualisiert die Monatsstatistiken für die übergebenen Betriebssysteminformationen
     *
     * @param \DateTime $accessDateTime Zeitpunkt des Zugriffs
     * @param string $osname Name des Betriebssystems (bspw. "Windows" oder "Linux")
     * @param string $osversion Versionsnummer des Betriebssystems
     * @return void
     */
    protected function countMonthlyOsStats(\DateTime $accessDateTime, $osname, $osversion) {
        $sql = 'SELECT COUNT(*) FROM monthlyos WHERE
                year = :year AND month = :month AND osname = :osname AND osversion = :osversion ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('osname', $osname);
        $stmt->bindValue('osversion', $osversion);
        $stmt->execute();
        $existing = $stmt->fetchColumn() > 0;

        if ($existing) {
            $sql = 'UPDATE monthlyos SET count = count +1
                    WHERE year = :year AND month = :month AND osname = :osname AND osversion = :osversion';
        } else {
            $sql = 'INSERT INTO monthlyos (year, month, osname, osversion, count)
                    VALUES (:year, :month, :osname, :osversion, 1)';
        }
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('osname', $osname);
        $stmt->bindValue('osversion', $osversion);
        $stmt->execute();
    }

    /**
     * Aktualisiert die Monatsstatistiken für die übergebenen Browser-Informationen
     *
     * @param \DateTime $accessDateTime Zeitpunkt des Zugriffs
     * @param string $browser Browsername (bspw. "Firefox")
     * @param string $version Versionsangabe zum Browser
     * @return void
     */
    protected function countMonthlyBrowserStats(\DateTime $accessDateTime, $browser, $version) {
        $sql = 'SELECT COUNT(*) FROM monthlybrowser WHERE
                year = :year AND month = :month AND browsername = :browsername and browserversion = :browserversion ';
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('browsername', $browser);
        $stmt->bindValue('browserversion', $version);
        $stmt->execute();
        $existing = $stmt->fetchColumn() > 0;

        if ($existing) {
            $sql = 'UPDATE monthlybrowser SET count = count +1
                    WHERE year = :year AND month = :month AND browsername = :browsername AND browserversion = :browserversion';
        } else {
            $sql = 'INSERT INTO monthlybrowser (year, month, browsername, browserversion, count)
                    VALUES (:year, :month, :browsername, :browserversion, 1)';
        }
        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('year', $accessDateTime->format('Y'));
        $stmt->bindValue('month', $accessDateTime->format('m'));
        $stmt->bindValue('browsername', $browser);
        $stmt->bindValue('browserversion', $version);
        $stmt->execute();
    }

    /**
     * Speichert die übergebenen Informationen als Eintrag in der Accesslog-Tabelle in der Statistik-Datenbank
     *
     * @param AccessLogEntry $entry Der zu speichernder Accesslog-Eintrag
     * @return void
     */
    protected function saveAccesslog(AccessLogEntry $entry) {
        $sql = 'INSERT INTO accesslog (
                    sessionid, method, url, fullurl, useragent, osname, osversion, browsername,
                    browserversion, country, countrycode, region, regionname, city, zip, timezone,
                    latitude, longitude
                ) VALUES (
                    :sessionid, :method, :url, :fullurl, :useragent, :osname, :osversion, :browsername,
                    :browserversion, :country, :countrycode, :region, :regionname, :city, :zip, :timezone,
                    :latitude, :longitude
                )';

        $stmt = $this->statsdb->prepare($sql);
        $stmt->bindValue('sessionid', $entry->sessionid);
        $stmt->bindValue('method', $entry->method);
        $stmt->bindValue('url', $entry->url);
        $stmt->bindValue('fullurl', $entry->fullurl);
        $stmt->bindValue('useragent', $entry->useragent);
        $stmt->bindValue('osname', $entry->osname);
        $stmt->bindValue('osversion', $entry->osversion);
        $stmt->bindValue('browsername', $entry->browsername);
        $stmt->bindValue('browserversion', $entry->browserversion);
        $stmt->bindValue('country', $entry->country);
        $stmt->bindValue('countrycode', $entry->countrycode);
        $stmt->bindValue('region', $entry->region);
        $stmt->bindValue('regionname', $entry->regionname);
        $stmt->bindValue('city', $entry->city);
        $stmt->bindValue('zip', $entry->zip);
        $stmt->bindValue('timezone', $entry->timezone);
        $stmt->bindValue('latitude', $entry->latitude);
        $stmt->bindValue('longitude', $entry->longitude);
        $stmt->execute();
    }

    // </editor-fold>


    // <editor-fold desc="Definitions">

    /**
     * Speichert den übergebenen Definitionsdatensatz in der Datenbank
     * @param Definition $definition Die zu speichernde Definition
     */
    public function saveDefinition(Definition $definition) {
        $sql = 'REPLACE INTO definition (
                definitiontype, key, title, value, parameters) VALUES (
                :type, :key, :title, :value, :parameters
                ) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('type', $definition->definitiontype);
        $stmt->bindValue('key', $definition->key);
        $stmt->bindValue('title', $definition->title);
        $stmt->bindValue('value', $definition->value);
        $stmt->bindValue('parameters', $definition->parameters);
        $stmt->execute();
    }

    /**
     * Ermittelt alle Definitionsdatensätze, die zu dem angegebenen Definitionstyp gehören
     *
     * @param string $type Definitionstyp
     * @return Definition[] Die gefundenen Definitionsdatensätze
     */
    public function getDefinitionsByType(string $type) {
        $sql = 'SELECT * FROM definition WHERE definitiontype = :type ORDER BY title ASC, value ASC, parameters ASC ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('type', $type);
        $stmt->execute();

        $definitions = array();
        while (($definition = Definition::fetchFromPdoStatement($stmt)) !== null) {
            $definitions[$definition->key] = $definition;
        }
        return $definitions;
    }

    /**
     * Ermittelt einen Definitionsdatensatz anhand von Definitionstyp und Schlüssel aus
     * @param string $type Definitionstyp
     * @param string $key Schlüssel
     * @return Definition Definitionsdatensatz oder null
     */
    public function getDefinitionByTypeAndKey(string $type, string $key) {
        $sql = 'SELECT * FROM definition WHERE definitiontype = :type AND key = :key ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('type', $type);
        $stmt->bindValue('key', $key);
        $stmt->execute();
        return Definition::fetchFromPdoStatement($stmt);
    }

    /**
     * Löscht einen Definitionsdatensatz anhand von Typ und Key
     * @param string $type Definitionstyp
     * @param string $key Schlüssel
     * @return void
     */
    public function deleteDefinitionByTypeAndKey(string $type, string $key) {
        $sql = 'DELETE FROM definition WHERE definitiontype = :type AND key = :key ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('type', $type);
        $stmt->bindValue('key', $key);
        $stmt->execute();
    }

    /**
     * Durchsucht die Definitionstabelle nach verschiedenen Kriterien
     * @param string $type Definitionstyp
     * @param string|null $searchterm Freier Suchbegriff
     * @param bool $countOnly Auf true setzen, um nur die Anzahl der Suchtreffer zu ermitteln
     * @param int|null $page Optionale Seitenangabe (bei 1 beginnend)
     * @param int|null $limit Optionales Limit für die Ergebnismenge
     * @return array|int Gefundene Datensätze oder Anzahl der Treffer
     */
    public function searchDefinitions($type = null, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $definitions = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= 'FROM definition WHERE 1 = 1 ';

        if (!empty($type)) {
            $sql .= ' AND definitiontype = :type ';
            $params['type'] = $type;
        }

        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= ' AND (title LIKE :search_title OR value LIKE :search_value OR parameters LIKE :search_parameters) ';
            $params['search_title'] = $like;
            $params['search_value'] = $like;
            $params['search_parameters'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= ' ORDER BY definitiontype ASC, key ASC, title ASC, value ASC, parameters ASC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter setzen und Query ausführen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        // Ergebnis auslesen
        if ($countOnly) return $stmt->fetchColumn();
        while (($definition = Definition::fetchFromPdoStatement($stmt)) !== null) {
            $definitions[] = $definition;
        }
        return $definitions;
    }

    // </editor-fold>


    // <editor-fold desc="Settings">

    /**
     * Speichert einen Setting-Datensatz in der Datenbank
     * @param Setting $setting Der zu speichernde Setting-Datensatz
     * @return string
     */
    public function saveSetting(Setting $setting) {
        $sql = 'REPLACE INTO setting (name, setting, params) VALUES (:name, :settings, :params) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('name', $setting->key);
        $stmt->bindValue('settings', $setting->value);
        $stmt->bindValue('params', $setting->params);
        $stmt->execute();
        return $this->basedb->lastInsertId('key');
    }

    /**
     * Liest einen Systemeinstellungs-Datensatz aus
     * @param string $name Name der gesuchten Einstellung
     * @return \Ubergeek\NanoCm\Setting Die gesuchte Einstellung oder null
     */
    public function getSetting(string $name) {
        $sql = 'SELECT * FROM setting WHERE name = :name ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('name', $name);
        $stmt->execute();
        
        if (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $setting = new Setting();
            $setting->key = $row['name'];
            $setting->value = $row['setting'];
            $setting->params = $row['params'];
            return $setting;
        }
        
        return null;
    }
    
    /**
     * Gibt (nur) den String-Wert einer Systemeinstellung zurück.
     * Ist die angeforderte Einstellung nicht definiert, kann über den optionalen
     * zweiten Parameter bestimmt werden, welcher Wert in diesem Fall zurück
     * gegeben werden soll.
     *
     * Diese Methode ist "fail safe" gestaltet; möglicherweise auftretende
     * Fehler und Exceptions sollten abgefangen werden und gegebenenfalls der
     * gewünschte Standardwert zurückgegeben werden.
     *
     * @param string $name Name der gesuchten Einstellung
     * @param mixed $default Optionaler Standard-Rückgabewert
     * @return mixed Der gesuchte Wert oder der vorgegebene Standard-Wert
     */
    public function getSettingValue(string $name, $default = null) {
        try {
            $setting = $this->getSetting($name);
            if ($setting == null) return $default;
            return $setting->value;
        } catch (\Exception $ex) {
            // Fehler ignorieren
        }
        return $default;
    }
    
    /**
     * Gibt (nur) die Parameter einer Systemeinstellung zurück.
     * Ist die angeforderte Einstellung nicht definiert, kann über den optionalen
     * zweiten Parameter bestimmt werden, welcher Wert in diesem Fall zurück
     * gegeben werden soll.
     * @param string $name Name der gesuchten Einstellung
     * @param mixed $default Optionaler Standard-Rückgabewert
     * @return mixed Der gesuchte Wert oder der vorgegebene Standard-Wert
     */
    public function getSettingParams(string $name, $default = null) {
        $setting = $this->getSetting($name);
        if ($setting == null) return $default;
        return $setting->params;
    }

    /**
     * Löscht die Einstellung mit dem angegebenen Key
     * @param $key Key der zu löschenden Einstellung
     * @return bool
     */
    public function deleteSettingByKey($key) {
        try {
            $sql = 'DELETE FROM setting WHERE name = :key ';
            $stmt = $this->basedb->prepare($sql);
            $stmt->bindValue('key', $key);
            $stmt->execute();
        } catch (\Exception $ex) {
            $this->log->err('Fehler beim Löschen der Einstellung', $ex);
            return false;
        }
        return true;
    }

    /**
     * Löscht mehrere Einstellungen anhand ihrer Keys
     * @param array $keys Keys der zu löschenden Einstellungen
     * @return void
     */
    public function deleteSettingsByKey(array $keys) {
        foreach ($keys as $key) {
            $this->deleteSettingByKey($key);
        }
    }

    /**
     * Durchsucht die Systemeinstellungen und gibt ein Array mit den gefundenen
     * Datensätzen zurück
     * @param Setting|null $filter Filterkriterien
     * @param string|null $searchterm Optionaler Suchbegriff
     * @param bool $countOnly
     * @param int $page = null
     * @param int $limit = null
     * @return Setting[]
     */
    public function searchSettings(Setting $filter = null, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $settings = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        // SQL zusammenstellen
        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= 'FROM setting WHERE 1 = 1 ';

        // Filterbedingungen einfügen
        if ($filter instanceof Setting) {
            // TODO implementieren
        }

        // Feier Suchbegriff
        if (!empty($searchterm)) {
            $sql .= ' AND name LIKE :name ';
            $params['name'] = "%$searchterm%";
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= ' ORDER BY name ASC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter setzen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        if ($countOnly) {
            return $stmt->fetchColumn();
        }

        // Ergebnis auslesen
        while (($setting = Setting::fetchFromPdoStatement($stmt)) !== null) {
            $settings[] = $setting;
        }

        return $settings;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="User">

    /**
     * Aktualisiert den Zeitpunkt des letzten Logins für das angegebene Benutzerkonto auf den aktuellen Zeitpunkt
     * @param int $id Datensatz-ID des zu ändernden Benutzerkontos
     * @return void
     */
    public function updateLoginTimestampByUserId(int $id) {
        $sql = 'UPDATE user SET last_login_timestamp = DATETIME(CURRENT_TIMESTAMP, \'localtime\') WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    /**
     * Setzt das Passwort für einen bestimmten Benutzer
     * @param int $id Benutzer-ID
     * @param string $password Neues Passwort
     * @return bool true bei Erfolg
     */
    public function setUserPasswordById(int $id, string $password) : bool {
        $stmt = $this->basedb->prepare('
            UPDATE user SET password = :password, modification_timestamp = datetime(CURRENT_TIMESTAMP, \'localtime\') WHERE id = :id
        ');
        $stmt->bindValue('password', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Setzt das Passwort für einen bestimmten Benutzer
     * @param string $username Benutzername
     * @param string $password Neues Passwort
     * @return bool true bei Erfolg
     */
    public function setUserPasswordByUsername(string $username, string $password) : bool {
        $stmt = $this->basedb->prepare('
            UPDATE user SET password = :password, modification_timestamp = datetime(CURRENT_TIMESTAMP, \'localtime\') WHERE username = :username
        ');
        $stmt->bindValue('password', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindValue('username', $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Durchsucht die Benutzerdatenbank nach flexiblen Filterkriterien
     * @param User $filter = null
     * @param string $searchterm
     * @param bool $countOnly
     * @param int $page
     * @param int $limit
     * @return User[] Liste der gefundenen Benutzerdatensätze
     */
    public function searchUsers(User $filter = null, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= ' FROM user WHERE 1 = 1 ';

        // Filterbedingungen
        if ($filter instanceof User) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Suchbegriff
        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= ' AND (
                        firstname LIKE :search_firstname
                        OR lastname LIKE :search_lastname
                        OR username LIKE :search_username
                        OR email LIKE :search_email
                    ) ';
            $params['search_firstname'] = $like;
            $params['search_lastname'] = $like;
            $params['search_username'] = $like;
            $params['search_email'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= ' ORDER BY username ASC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter füllen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        // Ergebnis auslesen
        if ($countOnly) return $stmt->fetchColumn();
        $users = array();
        while (($user = User::fetchFromPdoStmt($stmt)) !== null) {
            $users[] = $user;
        }
        return $users;
    }

    /**
     * Gibt - falls vorhanden - den Benutzer-Datensatz mit der angegebenen ID
     * zurück
     * 
     * Kann der angefordrte Benutzer-Datensatz nicht gefunden werden, so wird
     * NULL zurück gegeben.
     * @param int $id Benutzer-ID
     * @param bool $includeInactive Auf true setzen, wenn auch inaktive Konten
     *  berücksichtigt werden sollen
     * @return User Gesuchter Benutzer-Datensatz oder NULL
     */
    public function getUserById(int $id, bool $includeInactive) {
        $sql = 'SELECT * FROM User WHERE id = :userid ';
        if (!$includeInactive) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('userid', $id);
        $stmt->execute();
        
        return User::fetchFromPdoStmt($stmt);
    }
    
    /**
     * Gibt - falls vorhanden - den Benutzer-Datensatz mit dem angegebenen
     * Benutzernamen zurück
     * 
     * Kann der angeforderte Benutzer-Datensatz nicht gefunden werden, so wird
     * NULL zurück gegeben.
     * @param string $username Benutzername
     * @param bool $includeInactive Auf true setzen, wenn auch inaktive Konten
     *  berücksichtigt werden sollen
     * @return User Gesuchter Benutzer-Datensatz oder NULL
     */
    public function getUserByUsername(string $username, bool $includeInactive) {
        $sql = 'SELECT * FROM user WHERE username = :username ';
        if (!$includeInactive) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('username', $username);
        $stmt->execute();
        
        return User::fetchFromPdoStmt($stmt);
    }

    /**
     * Gibt - sofern Benutzername und Passwort mit den Werten in der Datenbank
     * übereinstimmen - den gesuchten Benutzer-Datensatz zurück
     * 
     * Kann der Datensatz nicht gefunden werden oder stimmen übergebenes und
     * gespeichertes Passwort nicht überein, so wird NULL zurück gegeben. Der
     * Grund für eine nicht erfolgreiche Abfrage wird nicht mitgeteilt.
     * 
     * @param string $username Gesuchter Benutzername
     * @param string $passwd Eingegebenes bzw. bekanntes Passwort
     * @return User Gesuchter Benutzer-Datensatz oder NULL
     */
    public function getUserByCredentials(string $username, string $passwd) {
        $user = $this->getUserByUsername($username, false);
        if ($user != null) {
            if (password_verify($passwd, $user->password)) return $user;
        }
        return null;
    }
    
    /**
     * Speichert den übergebenen Benutzer-Datensatz in der Datenbank.
     * ACHTUNG: Bei diesem Aufruf muss im Password-Feld bereits das gehashte Passwort stehen!
     * @param \Ubergeek\NanoCm\User $user Der zu speichernde Benutzerkonten-Datensatz
     * @return int Die Datensatz-ID des angelegten oder aktualisierten Benutzerkonten-Datensatzes
     */
    public function saveUser(User $user) {
        if ($user->id < 1 && empty($user->password)) $user->password = '';

        $sql = 'REPLACE INTO user (
                    id, status_code, creation_timestamp, firstname, lastname, username, password, email, usertype
                ) VALUES (
                    :id, :status_code, :creation_timestamp, :firstname, :lastname, :username, :password, :email, :usertype
                ) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $user->id);
        $stmt->bindValue('status_code', $user->status_code);
        $stmt->bindValue('creation_timestamp', ($user->creation_timestamp == null)? null : $user->creation_timestamp->format('Y-m-d H:i:s'));
        $stmt->bindValue('firstname', $user->firstname);
        $stmt->bindValue('lastname', $user->lastname);
        $stmt->bindValue('username', $user->username);
        $stmt->bindValue('password', $user->password);
        $stmt->bindValue('email', $user->email);
        $stmt->bindValue('usertype', $user->usertype);
        $stmt->execute();
        return $this->basedb->lastInsertId('id');
    }

    /**
     * Setzt den Status-Code für ein bestimmtes Benutzerkonto auf den angegebenen Wert
     * @param $id Datensatz-ID des zu ändernden Benutzerkontos
     * @param $statusCode Neuer Status-Code
     * @return void
     */
    public function setUserStatusCodeById($id, $statusCode) {
        $sql = 'UPDATE user SET status_code = :status_code WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $statusCode);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    /**
     * Setzt den Status-Code mehrerer Benutzerkonten auf den angegebenen Wert
     * @param int[] $ids Datensatz-IDs der zu ändernden Benutzerkonten
     * @param $statusCode Neuer Status-Code
     */
    public function setUserStatusCodeByIds(array $ids, $statusCode) {
        foreach ($ids as $id) {
            $this->setUserStatusCodeById($id, $statusCode);
        }
    }

    /**
     * Löscht das Benutzerkonto mit der angegebenen ID
     * @param $id Datensatz-ID des zu löschenden Benutzerkontos
     * @return void
     */
    public function deleteUserById($id) {
        $sql = 'DELETE FROM user WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    /**
     * Löscht die Benutzerkonten mit den angegebenen IDs
     * @param int[] $ids Datensatz-IDs der zu löschenden Benutzerkonten
     * @return void
     */
    public function deleteUsersByIds(array $ids) {
        foreach ($ids as $id) {
            $this->deleteUserById($id);
        }
    }
    
    // </editor-fold>


    // <editor-fold desc="Tag">

    /**
     * Gibt eine Liste aller definierten Schlagworte zurück
     * @return Tag[]
     */
    public function getTags() {
        $tags = array();

        $sql = 'SELECT * FROM tag ORDER BY title ASC';
        $stmt = $this->basedb->prepare($sql);
        $stmt->execute();

        while (($tag = Tag::fetchFromPdoStatement($stmt)) !== null) {
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Gibt ein Array mit den Tags (in Form von Strings) zurück,
     * die einem bestimmten Artikel zugeordnet sind
     * @param $articleId Artikel-ID
     * @return string[] Array der zugewiesenen Tags
     */
    public function getTagsByArticleId($articleId) {
        $tags = array();

        $sql = 'SELECT tag.title FROM tag_article LEFT JOIN tag
                ON tag.id = tag_article.tag_id
                WHERE tag_article.article_id = :article_id';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->execute();

        while (($tag = $stmt->fetchColumn()) !== false) {
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Weist die übergebenen Tags einem bestimmten Artikel zu
     *
     * Die zuzuweisenden Tags werden lediglich als Strings übergeben,
     * nicht etwa als Tag-Objekte.
     * @param $articleId Artikel-ID
     * @param string[] $tags Liste der zuzuweisenden Tags
     * @return void
     */
    public function assignTagsToArticle($articleId, array $tags) {
        $existingTags = $this->getTagsByArticleId($articleId);
        $toInsert = array_diff($tags, $existingTags);
        $toDelete = array_diff($existingTags, $tags);

        foreach ($toInsert as $insert) {
            $this->assignTagToArticle($articleId, $insert);
        }

        foreach ($toDelete as $delete) {
            $this->unassignTagFromArticle($articleId, $delete);
        }
    }

    /**
     * Weist das übergebene Schlagwort einem bestimmten Artikel hinzu
     * @param int $articleId Artikel-ID
     * @param string $title Zuzuweisendes Schlagwort
     * @return void
     */
    public function assignTagToArticle(int $articleId, string $title) {
        if (!empty(trim($title))) {
            $tagId = $this->saveTag($title);
            if ($tagId > 0) {
                $this->assignTagIdToArticle($articleId, $tagId);
            }
        }
    }

    /**
     * Ordnet das Schlagwort mit der angegebenen ID einem Artikel hinzu
     * @param int $articleId Artikel-ID
     * @param int $tagId Tag-ID
     * @return void
     */
    public function assignTagIdToArticle(int $articleId, int $tagId) {
        $sql = 'REPLACE INTO tag_article (article_id, tag_id) VALUES (:article_id, :tag_id)';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->bindValue('tag_id', $tagId);
        $stmt->execute();
    }

    /**
     * Entfernt (falls vorhanden) die Zuweisung eines Schlagwortes von einem
     * bestimmten Artikel
     * @param int $articleId Artikel-ID
     * @param string $title Schlagwort
     * @return void
     */
    public function unassignTagFromArticle(int $articleId, string $title) {
        $tagId = $this->getTagIdByTitle($title);
        $this->unassignTagIdFromArticle($articleId, $tagId);
    }

    /**
     * Entfernt (falls vorhanden) die Zuweisung des Schlagwortes mit der angegebenen ID
     * von einem bestimmten Artikel
     * @param int $articleId Artikel-ID
     * @param int $tagId Tag-ID
     * @return void
     */
    public function unassignTagIdFromArticle(int $articleId, int $tagId) {
        $sql = 'DELETE FROM tag_article WHERE article_id = :article_id AND tag_id = :tag_id';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->bindValue('tag_id', $tagId);
        $stmt->execute();
    }

    /**
     * Entfernt alle Tag-Zuweisungen von einem bestimmten Artikel
     * @param int $articleId Datensatz-ID des betreffenden Artikels
     */
    public function unassignTagsFromArticle(int $articleId) {
        $sql = 'DELETE FROM tag_article WHERE article_id = :article_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->execute();
    }

    /**
     * Speichert ein Schlagwort in der Datenbank und gibt die Datensatz-ID zurück
     *
     * Wenn die Datensatz-ID nicht korrekt ermittelt werden kann
     * (mögliche Gründe?), so wird 0 zurück gegeben.
     * @param string $title
     * @return int
     */
    public function saveTag(string $title) {
        $sql = 'REPLACE INTO tag (title) VALUES (:title) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('title', $title);
        $stmt->execute();

        return $this->getTagIdByTitle($title);
    }

    /**
     * Ermittelt die Datensatz-ID für das angegebene Schlagwort.
     *
     * Wenn die ID nicht ermittelt werden kann (etwa weil das Schlagwort
     * nicht in der Datenbank vorhanden ist), so wird null zurück
     * gegeben.
     * @param string $title Gesuchtes Schlagwort
     * @return int|null
     */
    public function getTagIdByTitle(string $title) {
        $sql = 'SELECT id FROM tag WHERE LOWER(title) = :title ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('title', mb_strtolower($title));
        $stmt->execute();
        $id = $stmt->fetchColumn();

        if ($id === false) return null;
        return $id;
    }

    // </editor-fold>


    // <editor-fold desc="Comments">

    /**
     * Gibt die Anzahl der Kommentare zurück, die den angegebenen Status-Code besitzen
     * @param int $statusCode Der gesuchte Status-Code
     * @return int Die Anzahl der Kommentare mit diesem Status-Code
     */
    public function countCommentsByStatusCode($statusCode) {
        $sql = 'SELECT COUNT(*) FROM comment WHERE status_code = :status_code ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $statusCode);
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }

    /**
     * Gibt die Anzahl aller nicht freigeschalteten Kommentare zurück
     * @return int Anzahl der nicht freigeschalteten Kommentare
     */
    public function countInactiveComments() {
        $sql = 'SELECT COUNT(*) FROM comment WHERE status_code <> :status_code ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', StatusCode::ACTIVE);
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }

    /**
     * Durchsucht die Kommentare
     *
     * @param Comment|null $filter Optionale Filterkriterien
     * @param null $searchterm Optionaler Suchbegriff
     * @param bool $countOnly Auf true setzen, um lediglich die Anzahl der Suchtreffer zu ermitteln
     * @param null $page Anzuzeigende Seite (bei 1 beginnend)
     * @param null $limit Maximale Anzahl Datensätze bzw. Seitenlänge
     * @return int|Comment[] Anzahl Datensätze oder Array mit gefundenen Kommentaren
     */
    public function searchComments(Comment $filter = null, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $comments = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        // Ergebnis oder Anzahl Ergebnisse
        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= ' FROM comment WHERE 1 = 1 ';

        // Filterbedingungen einfügen
        if ($filter instanceof Comment) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Suchbegriff
        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= ' AND (headline LIKE :search_headline
                        OR content LIKE :search_content 
                        OR username LIKE :search_username) ';
            $params['search_headline'] = $like;
            $params['search_content'] = $like;
            $params['search_username'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= ' ORDER BY creation_timestamp DESC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter füllen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        if ($countOnly) return $stmt->fetchColumn();

        while (($comment = Comment::fetchFromPdoStatement($stmt)) !== null) {
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Gibt den Kommentar mit der angegebenen Datensatz-ID zurück
     * @param integer $id Datensatz-ID des angeforderten Kommentares
     * @return null|Comment Der gesuchte Kommentar oder null
     */
    public function getCommentById($id) {
        $sql = 'SELECT * FROM comment WHERE id = :id';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return Comment::fetchFromPdoStatement($stmt);
    }

    /**
     * Löscht einen Kommentar anhand seiner Datensatz-ID
     * @param $commentId ID des zu löschenden Kommentares
     * @return void
     */
    public function deleteCommentById($commentId) {
        $sql = 'DELETE FROM comment WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $commentId);
        $stmt->execute();
    }

    /**
     * Löscht mehrere Kommentare anhand ihrer IDs
     * @param array $commentIds IDs der zu löschenden Kommentare
     * @return void
     */
    public function deleteCommentsById(array $commentIds) {
        foreach ($commentIds as $id) {
            $this->deleteCommentById($id);
        }
    }

    /**
     * Setzt den Status-Code für einen bestimmten Kommentar
     * @param int $commentId ID des Kommentar-Datensatzes
     * @param int $statusCode Neuer Status-Code
     * @return void
     */
    public function setCommentStatusCodeById($commentId, $statusCode) {
        $sql = 'UPDATE comment SET status_code = :status_code WHERE id = :comment_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $statusCode);
        $stmt->bindValue('comment_id', $commentId);
        $stmt->execute();
    }

    /**
     * Setzt den Status-Code für mehrere Kommentar-Datensätze anhand ihrer IDs
     * @param array $commentIds Datensatz-IDs der zu ändernden Kommentare
     * @param int $statusCode Neuer Status-Code
     * @return void
     */
    public function setCommentStatusCodeByIds(array $commentIds, $statusCode) {
        foreach ($commentIds as $id) {
            $this->setCommentStatusCodeById($id, $statusCode);
        }
    }

    /**
     * Speichert den übergebenen Kommentar in der Datenbank
     * @param Comment $comment Der zu speichernde Datensatz
     * @return int Die Datensatz-ID
     */
    public function saveComment(Comment $comment) {
        if ($comment->id == 0) $comment->id = null;
        $sql = 'REPLACE INTO comment (id, article_id, creation_timestamp, status_code, spam_status, username, email, headline, content)
                VALUES (:id, :article_id, :creation_timestamp, :status_code, :spam_status, :username, :email, :headline, :content) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $comment->id);
        $stmt->bindValue('article_id', $comment->article_id);
        if ($comment->creation_timestamp instanceof \DateTime) {
            $stmt->bindValue('creation_timestamp', $comment->creation_timestamp->format('Y-m-d H:i:s'));
        } else {
            $stmt->bindValue('creation_timestamp', null);
        }
        $stmt->bindValue('status_code', $comment->status_code);
        $stmt->bindValue('spam_status', $comment->spam_status);
        $stmt->bindValue('username', $comment->username);
        $stmt->bindValue('email', $comment->email);
        $stmt->bindValue('headline', $comment->headline);
        $stmt->bindValue('content', $comment->content);
        $stmt->execute();
        return $this->basedb->lastInsertId('id');
    }

    // </editor-fold>


    // <editor-fold desc="Articleseries">

    /**
     * Gibt alle Artikelserien zurück
     *
     * @param bool $releasedOnly
     * @return Articleseries[]
     */
    public function getArticleseries($releasedOnly = true) {
        $params = array();

        $sql = 'SELECT * FROM articleseries WHERE 1 = 1 ';
        if ($releasedOnly) {
            $sql .= ' AND status_code = :status_code ';
            $params[status_code] = StatusCode::ACTIVE;
        }

        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        $articleseries = array();
        while (($row = Articleseries::fetchFromPdoStatement($stmt)) !== null) {
            $articleseries[$row->id] = $row;
        }

        return $articleseries;
    }

    public function searchArticleseries(Articleseries $filter = null, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= 'FROM articleseries WHERE 1 = 1 ';

        // Optionaler Filter
        if ($filter instanceof Articleseries) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Freier Suchbegriff
        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= 'AND (title LIKE :search_title OR description LIKE :search_description) ';
            $params['search_title'] = $like;
            $params['search_description'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= ' ORDER BY title ASC, description ASC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter füllen und Query ausführen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        // Ergebnis auslesen
        if ($countOnly) return $stmt->fetchColumn();
        $articleseries = array();
        while (($series = Articleseries::fetchFromPdoStatement($stmt)) !== null) {
            $articleseries[] = $series;
        }
        return $articleseries;
    }

    /**
     * @param Articleseries $articleseries
     * @return int Datensatz-ID
     */
    public function saveArticleseries($articleseries) {
        if ($articleseries->id == 0) $articleseries->id = null;

        $sql = 'REPLACE INTO articleseries (
                id, creation_timestamp, status_code, title, description, sorting_key
                ) VALUES (
                :id, :creation_timestamp, :status_code, :title, :description, :sorting_key
                ) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $articleseries->id);
        $stmt->bindValue('creation_timestamp', ($articleseries->creation_timestamp == null)? null : $articleseries->creation_timestamp->format('Y-m-d H:i:s'));
        $stmt->bindValue('status_code', $articleseries->status_code);
        $stmt->bindValue('title', $articleseries->title);
        $stmt->bindValue('description', $articleseries->description);
        $stmt->bindValue('sorting_key', $articleseries->sorting_key);
        $stmt->execute();
        return $this->basedb->lastInsertId('id');
    }

    public function getArticleseriesById($id) {
        $sql = 'SELECT * FROM articleseries WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return Articleseries::fetchFromPdoStatement($stmt);
    }

    public function deleteArticleseriesById($id) {
        $sql = 'DELETE FROM articleseries WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    public function deleteArticleseriesByIds(array $ids) {
        foreach ($ids as $id) {
            $this->deleteArticleseriesById($id);
        }
    }

    public function setArticleseriesStatusCodeById($id, $statusCode) {
        $sql = 'UPDATE articleseries SET status_code = :status_code WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $statusCode);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    public function setArticleseriesStatusCodeByIds($ids, $statusCode) {
        foreach ($ids as $id) {
            $this->setArticleseriesStatusCodeById($id, $statusCode);
        }
    }

    // </editor-fold>

    
    // <editor-fold desc="Article">

    /**
     * Ermittelt die Headline eines bestimmten Artikels
     * @param int $articleId Artikel-ID
     * @return string|null
     */
    public function getArticleHeadlineById($articleId) {
        $sql = 'SELECT headline FROM article WHERE id = :article_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Gibt die Anzahl der aktuell freigeschalteten Artikel zurück
     * @return int Anzahl der aktuell freigeschalteten Artikel
     */
    public function countReleasedArticles() {
        $sql = 'SELECT COUNT(*) FROM article WHERE (
                    start_timestamp <= datetime(CURRENT_TIMESTAMP, \'localtime\')
                    AND (stop_timestamp IS NULL OR stop_timestamp >= datetime(CURRENT_TIMESTAMP, \'localtime\'))
                )
                AND status_code = :status_code
        ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', StatusCode::ACTIVE);
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }

    /**
     * Gibt die Anzahl der gespeicherten aber nicht freigeschalteten Artikel zurück
     * @return int Anzahl der nicht freigeschalteten Artikel
     */
    public function countUnreleasedArticles() {
        $sql = 'SELECT COUNT(*) FROM article WHERE NOT (
                    start_timestamp <= datetime(CURRENT_TIMESTAMP, \'localtime\')
                    AND (stop_timestamp IS NULL OR stop_timestamp >= datetime(CURRENT_TIMESTAMP, \'localtime\'))
                )
                OR status_code <> :status_code
        ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', StatusCode::ACTIVE);
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }

    /**
     * Durchsucht die Artikel nach bestimmten Filterkriterien
     *
     * Wenn der Parameter $countonly auf true gesetzt wird, werden die Parameter $page und $limit nicht mehr
     * berücksichtigt.
     *
     * @param \Ubergeek\NanoCm\Article $filter Optionale Suchfilter
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Artikel
     * berücksichtig werden sollen
     * @param string $searchterm Freier Suchbegriff
     * @param bool $countOnly Gibt an, ob das Suchergebnis oder die Antahl der Treffer zurückgegeben werden sollen
     * @param int|null $page Angeforderte Seite
     * @param int|null $limit Maximale Anzahl der zurück zu gebenden Artikel
     * @return Article[]|int Ein Array mit den gefundenen Artikeln
     */
    public function searchArticles(Article $filter = null, $releasedOnly = true, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $articles = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        // Ergebnis oder Anzahl Ergebnisse
        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= ' FROM article WHERE 1 = 1 ';

        // Nur veröffentlichte Artikel berücksichtigen
        if ($releasedOnly) {
            $sql .= '
                AND (
                    start_timestamp <= datetime(CURRENT_TIMESTAMP, \'localtime\')
                    AND (stop_timestamp IS NULL OR stop_timestamp >= datetime(CURRENT_TIMESTAMP, \'localtime\'))
                )
                AND status_code = ' . StatusCode::ACTIVE . ' ';
        }
        
        // Filterbedingungen einfügen
        if ($filter instanceof Article) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Suchbegriff
        if (!empty($searchterm)) {
            $like = '%' . $searchterm . '%';
            $sql .= ' AND (headline LIKE :search_headline
                        OR content LIKE :search_content) ';
            $params['search_headline'] = $like;
            $params['search_content'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= 'ORDER BY start_timestamp DESC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter füllen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        if ($countOnly) {
            return $stmt->fetchColumn();
        }

        // Ergerbnis auslesen
        while (($article = Article::fetchFromPdoStatement($stmt)) !== null) {
            $article->tags = $this->getTagsByArticleId($article->id);
            $article->articleType = $this->getDefinitionByTypeAndKey(Definition::TYPE_ARTICLE_TYPE, $article->articletype_key);
            $articles[] = $article;
        }
        return $articles;
    }

    /**
     * Liest den Artikel mit der angegebenen ID aus und gibt ein entsprechendes Objekt
     * oder null zurück.
     * @param int $id ID des angeforderten Artikels
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Artikel berücksichtigt werden sollen
     * @return Article|null
     * @todo Verknüpfte komplexe Daten (Autor etc.) müssen auch ausgelesen werden
     */
    public function getArticleById(int $id, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM article WHERE id = :id ';
        if ($releasedOnly) {
            $sql .= ' AND status_code = ' . StatusCode::ACTIVE;
        }

        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();

        if (($article = Article::fetchFromPdoStatement($stmt)) !== null) {
            $article->tags = $this->getTagsByArticleId($article->id);
            $article->articleType = $this->getDefinitionByTypeAndKey(Definition::TYPE_ARTICLE_TYPE, $article->articletype_key);
        }
        return $article;
    }
    
    /**
     * Gibt die neuesten freigeschalteten Artikel zurück
     * @param int $limit Maximale Anzahl zurückzugebender Artikel
     * @return Article[]
     */
    public function getLatestArticles(int $limit = 5) {
        return $this->searchArticles(null, true, null, false, 0, $limit);
    }

    /**
     * Speichert einen Artikel in der Datenbank
     * @param Article $article Artikeldaten
     * @return int Datensatz-ID
     * @todo Zugriffsrechte prüfen
     */
    public function saveArticle(Article $article) {
        // Artikel aktualisieren
        if ($article->id > 0) {
            $this->updateArticle($article);
        }

        // Artikel hinzufügen
        else {
            $article->id = $this->insertArticle($article);
        }

        return $article->id;
    }

    /**
     * Löscht den Artikel mit der angegebenen ID
     * @param $id Datensatz-ID des zu löschenden Artikels
     * @return bool
     */
    public function deleteArticleById($id) {
        try {
            $this->unassignTagsFromArticle($id);
            $sql = 'DELETE FROM article WHERE id = :article_id ';
            $stmt = $this->basedb->prepare($sql);
            $stmt->bindValue('article_id', $id);
            $stmt->execute();
        } catch (\Exception $ex) {
            $this->log->err('Fehler beim Löschen des Artikels', $ex);
            return false;
        }
        return true;
    }

    /**
     * Löscht mehrere Artikel anhand ihrer Datensatz-IDs
     * @param array $ids IDs der zu löschenden Artikel
     * @return void
     */
    public function deleteArticlesById(array $ids) {
        foreach ($ids as $id) {
            $this->deleteArticleById($id);
        }
    }

    /**
     * Sperrt die Artikel mit den übergebenen IDs
     * @param array $ids IDs der zu sperrenden Artikel
     * @return void
     */
    public function lockArticlesById(array $ids) {
        $sql = 'UPDATE article SET status_code = :status_code WHERE id = :article_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', StatusCode::LOCKED);

        foreach ($ids as $article_id) {
            $stmt->bindParam('article_id', $article_id);
            $stmt->execute();
        }
    }

    /**
     * Aktualisiert einen Artikel-Datensatz.
     * @param Article $article
     */
    private function updateArticle(Article $article) {
        $article->modification_timestamp = new \DateTime();
        $templatevars = '';
        if (is_array($article->templatevars) && count($article->templatevars) > 0) {
            $templatevars = json_encode($article->templatevars);
        }

        $sql = 'UPDATE article SET 
                    modification_timestamp = datetime(CURRENT_TIMESTAMP, \'localtime\'),
                    author_id = :author_id,
                    status_code = :status_code,
                    headline = :headline,
                    teaser = :teaser,
                    content = :content,
                    start_timestamp = :start_timestamp,
                    stop_timestamp = :stop_timestamp,
                    publishing_timestamp = :publishing_timestamp,
                    enable_trackbacks = :enable_trackbacks,
                    enable_comments = :enable_comments,
                    articletype_key = :articletype_key,
                    templatevars = :templatevars,
                    series_id = :series_id
                WHERE
                    id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('author_id', $article->author_id);
        $stmt->bindValue('status_code', $article->status_code);
        $stmt->bindValue('headline', $article->headline);
        $stmt->bindValue('teaser', $article->teaser);
        $stmt->bindValue('content', $article->content);
        if ($article->start_timestamp != null) {
            $stmt->bindValue('start_timestamp', $article->start_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('start_timestamp', null);
        }
        if ($article->stop_timestamp != null) {
            $stmt->bindValue('stop_timestamp', $article->stop_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('stop_timestamp', null);
        }
        if ($article->publishing_timestamp != null) {
            $stmt->bindValue('publishing_timestamp', $article->publishing_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('publishing_timestamp', null);
        }
        $stmt->bindValue('enable_trackbacks', $article->enable_trackbacks);
        $stmt->bindValue('enable_comments', $article->enable_comments);
        $stmt->bindValue('articletype_key', $article->articletype_key);
        $stmt->bindValue('templatevars', $templatevars);
        $stmt->bindValue('series_id', $article->series_id);
        $stmt->bindValue('id', $article->id);
        $stmt->execute();

        // Verknüpfte Daten speichern
        $this->assignTagsToArticle($article->id, $article->tags);
    }

    /**
     * Speichert den übergebenen Artikel als neuen Datensatz
     * @param Article $article Der zu speichernde Artikel
     * @return int Die generierte Artikel-ID
     */
    private function insertArticle(Article $article) {
        // Grundlegende Validierung
        if ($article->start_timestamp == null) {
            $article->start_timestamp = new \DateTime();
        }
        $article->creation_timestamp = new \DateTime();
        $article->modification_timestamp = new \DateTime();

        $templatevars = '';
        if (is_array($article->templatevars) && count($article->templatevars) > 0) {
            $templatevars = json_encode($article->templatevars);
        }

        $sql = 'INSERT INTO article (
                  creation_timestamp, modification_timestamp, author_id,
                  status_code, headline, teaser, content, start_timestamp,
                  stop_timestamp, publishing_timestamp, enable_trackbacks,
                  enable_comments, articletype_key, templatevars, series_id
              ) VALUES (
                  datetime(CURRENT_TIMESTAMP, \'localtime\'), datetime(CURRENT_TIMESTAMP, \'localtime\'), :author_id,
                  :status_code, :headline, :teaser, :content, :start_timestamp,
                  :stop_timestamp, :publishing_timestamp, :enable_trackbacks,
                  :enable_comments, :articletype_key, :templatevars, :series_id
              ) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('author_id', $article->author_id);
        $stmt->bindValue('status_code', $article->status_code);
        $stmt->bindValue('headline', $article->headline);
        $stmt->bindValue('teaser', $article->teaser);
        $stmt->bindValue('content', $article->content);
        $stmt->bindValue('start_timestamp', $article->start_timestamp->format('Y-m-d H:i'));
        if ($article->stop_timestamp != null) {
            $stmt->bindValue('stop_timestamp', $article->stop_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('stop_timestamp', null);
        }
        if ($article->publishing_timestamp != null) {
            $stmt->bindValue('publishing_timestamp', $article->publishing_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('publishing_timestamp', null);
        }
        $stmt->bindValue('enable_trackbacks', ($article->enable_trackbacks)? 1 : 0);
        $stmt->bindValue('enable_comments', ($article->enable_comments)? 1 : 0);
        $stmt->bindValue('articletype_key', $article->articletype_key);
        $stmt->bindValue('templatevars', $templatevars);
        $stmt->bindValue('series_id', $article->series_id);
        $stmt->execute();

        $article->id = $this->basedb->lastInsertId('id');

        // Verknüpfte Daten speichern
        $this->assignTagsToArticle($article->id, $article->tags);

        return $article->id;
    }
    
    // </editor-fold>


    // <editor-fold desc="Listen">

    /**
     * Ermittelt UserList-Eintrag mit der angegebenen Datensatz-ID
     * @param int $id Datensatz-ID
     * @return null|UserList Die gesuchte UserList
     */
    public function getUserListById($id) {
        $sql = 'SELECT * FROM userlist WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return UserList::fetchFromPdoStatement($stmt);
    }

    /**
     * Ermittelt die zu einer UserList gehörenden UserListItem-Einträge
     * @param int $userListId Die ID der übergeordneten UserList
     * @param boolean $statusCode
     * @param string $searchterm
     * @return UserListItem[] Die zugehörigen UserListItem-Einträge
     */
    public function getUserListItemsByUserListId($userListId, $statusCode = null, $searchterm = null) {
        $params = array();
        $params['userlistid'] = $userListId;

        $sql = 'SELECT * FROM userlistitem WHERE userlist_id = :userlistid ';
        if ($statusCode != null) {
            $sql .= ' AND status_code = :status_code ';
            $params['status_code'] = $statusCode;
        }
        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= ' AND (title LIKE :search_title OR content LIKE :search_content OR parameters LIKE :search_parameters) ';
            $params['search_title'] = $like;
            $params['search_content'] = $like;
            $params['search_parameters'] = $like;
        }
        $sql .= ' ORDER BY sorting_code ASC, title ASC ';
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        $userListItems = array();
        while (($userListItem = UserListItem::fetchFromPdoStatement($stmt)) !== null) {
            $userListItems[] = $userListItem;
        }
        return $userListItems;
    }

    public function setUserListItemStatusCodeById($id, $status_code) {
        $sql = 'UPDATE userlistitem SET status_code = :status_code WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $status_code);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    public function setUserListItemsStatusCodeById(array $ids, $status_code) {
        foreach ($ids as $id) {
            $this->setUserListItemStatusCodeById($id, $status_code);
        }
    }

    public function getUserListItemById($id) {
        $sql = 'SELECT * FROM userlistitem WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return UserListItem::fetchFromPdoStatement($stmt);
    }

    /**
     * Speichert den übergebenen UserList-Datensatz in der Datenbank
     * @param UserList $list Die zu speichernde UserList
     * @return int Die Datensatz-ID
     */
    public function saveUserList(UserList $list) {
        if ($list->id == 0) $list->id = null;

        $sql = 'REPLACE INTO userlist (id, title, status_code, creation_timestamp)
                VALUES (:id, :title, :status_code, :creation_timestamp)';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $list->id);
        $stmt->bindValue('title', $list->title);
        $stmt->bindValue('status_code', $list->status_code);
        $stmt->bindValue('creation_timestamp', ($list->creation_timestamp == null)? null : $list->creation_timestamp->format('Y-m-d H:i:s'));
        $stmt->execute();

        return $this->basedb->lastInsertId('id');
    }

    /**
     * Speichert den übergebenen UserListItem-Eintrag in der Datenbank
     * @param UserListItem $item Der zu speichernde UserListItem-Eintrag
     * @return int Die Datensatz-ID
     */
    public function saveUserListItem(UserListItem $item) {
        if ($item->id == 0) $item->id = null;

        $sql = 'REPLACE INTO userlistitem (id, userlist_id, parent_id, status_code, creation_timestamp, title, content, parameters, sorting_code)
                VALUES (:id, :userlist_id, :parent_id, :status_code, :creation_timestamp, :title, :content, :parameters, :sorting_code) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $item->id);
        $stmt->bindValue('userlist_id', $item->userlist_id);
        $stmt->bindValue('parent_id', $item->parent_id);
        $stmt->bindValue('status_code', $item->status_code);
        $stmt->bindValue('creation_timestamp', ($item->creation_timestamp == null)? null : $item->creation_timestamp->format('Y-m-d H:i:s'));
        $stmt->bindValue('title', $item->title);
        $stmt->bindValue('content', $item->content);
        $stmt->bindValue('parameters', $item->parameters);
        $stmt->bindValue('sorting_code', $item->sorting_code);
        $stmt->execute();
        return $this->basedb->lastInsertId('id');
    }

    /**
     * Setzt des Status-Code für einen UserList-Eintrag auf den angegebenen Wert
     * @param int $id Datensatz-ID des zu ändernden UserList-Eintrags
     * @param int $status_code Der neue Status-Code
     * @return void
     */
    public function setUserListStatusCodeById($id, $status_code) {
        $sql = 'UPDATE userlist SET status_code = :status_code WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $status_code);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    /**
     * Setzt den Status-Codes mehrerer UserList-Einträge auf den angegebenen Wert
     * @param array $ids Die Datensatz-IDs der zu ändernden UserList-Einträge
     * @param int $status_code Der neue Status-Code
     * @return void
     */
    public function setUserListStatusCodesById(array $ids, $status_code) {
        foreach ($ids as $id) {
            $this->setUserListStatusCodeById($id, $status_code);
        }
    }

    /**
     * Löscht einen bestimmten UserList-Eintrag anhand seiner Datensatz-ID
     *
     * Hinweis: auch die zugehörigen UserListItem-Einträge werden bei diesem
     * Vorgang gelöscht!
     * @param int $id Datensatz-ID des zu löschenden UserList-Eintrags
     * @return void
     */
    public function deleteUserListById($id) {
        $this->deleteUserListItemsByUserListId($id);
        $sql = 'DELETE FROM userlist WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    /**
     * Löscht mehrere UserList-Einträge anhand ihrer IDs
     * @param array $ids Datensatz-IDs der zu löschenden UserList-Einträge
     */
    public function deleteUserListsById(array $ids) {
        foreach ($ids as $id) {
            $this->deleteUserListById($id);
        }
    }

    /**
     * Löscht einen UserListItem-Datensatz anhand seiner Datensatz-ID
     * @param int $id Datensatz-ID des zu löschenden UserListItem-Eintrags
     */
    public function deleteUserListItemById($id) {
        $sql = 'DELETE FROM userlistitem WHERE id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
    }

    /**
     * Löscht mehrere UserListItem-Datensätze anhand ihrer Datensatz-IDs
     * @param array $ids Datensatz-IDs der zu löschenden UserListItems
     * @return void
     */
    public function deleteUserListItemsById(array $ids) {
        foreach ($ids as $id) {
            $this->deleteUserListItemById($id);
        }
    }

    /**
     * Löscht alle UserListItem-Einträge, die zu der angegebenen UserList gehören
     * @param int $userListId Datensatz-ID der zugehörigen UserList
     * @return void
     */
    public function deleteUserListItemsByUserListId($userListId) {
        $sql = 'DELETE FROM userlistitem WHERE userlist_id = :userlistid ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('userlistid', $userListId);
        $stmt->execute();
    }

    /**
     * Durchsucht die UserListem anhand verschiedener Suchkriterien
     * @param UserList|null $filter
     * @param string $searchterm
     * @param bool $countOnly
     * @param int $page
     * @param int $limit
     * @return UserList[]|int
     */
    public function searchUserLists(UserList $filter = null, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $userLists = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        // SQL zusammenstellen
        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= ' FROM userlist WHERE 1 = 1 ';

        // Filterbedingungen
        if ($filter instanceof UserList) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Suchbegriff
        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= ' AND title LIKE :search_title ';
            $params['search_title'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= 'ORDER BY title ASC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter füllen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        if ($countOnly) {
            return $stmt->fetchColumn();
        }

        // Ergebnis auslesen
        while (($userList = UserList::fetchFromPdoStatement($stmt)) !== null) {
            $userLists[] = $userList;
        }
        return $userLists;
    }

    // </editor-fold>


    // <editor-fold desc="Page">

    /**
     * Durchsucht die Pages nach verschiedenen Filterkriterien
     *
     * @param Page $filter
     * @param bool $releasedOnly
     * @param string $searchterm
     * @param bool $countOnly
     * @param int|null $page
     * @param int|null $limit
     * @return Page[]|int
     */
    public function searchPages(Page $filter = null, bool $releasedOnly = true, $searchterm = null, $countOnly = false, $page = null, $limit = null) {
        $pages = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        // SQL zusammenstellen
        if ($countOnly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= ' FROM page WHERE 1 = 1 ' ;

        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }

        // Filterbedingungen
        if ($filter instanceof Page) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Suchbegriff
        if (!empty($searchterm)) {
            $like = "%$searchterm%";
            $sql .= ' AND (headline LIKE :search_headline OR url LIKE :search_url OR content LIKE :search_content) ';
            $params['search_headline'] = $like;
            $params['search_url'] = $like;
            $params['search_content'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countOnly) {
            $sql .= 'ORDER BY headline, url ASC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = $page *$this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        // Parameter füllen
        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();

        if ($countOnly) {
            return $stmt->fetchColumn();
        }

        // Ergebnis auslesen
        while (($page = Page::fetchFromPdoStatement($stmt)) !== null) {
            $pages[] = $page;
        }
        return $pages;
    }

    /**
     * Liest die Page mit der angegebenen ID aus und gibt ein entsprechendes Objekt zurück
     * @param int $id ID der gesuchten Page
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Pages berücksichtigt werden sollen
     * @return Page|null
     */
    public function getPageById(int $id, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM page WHERE id = :id ';
        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return Page::fetchFromPdoStatement($stmt);
    }

    /**
     * Liest die Page mit der angegebenen URL aus und gibt ein entsprechendes Objekt zurück
     * @param string $url URL der gesuchten Page
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Pages berücksichtigt werden sollen
     * @return Page|null
     */
    public function getPageByUrl(string $url, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM page WHERE url = :url ';
        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('url', $url);
        $stmt->execute();
        return Page::fetchFromPdoStatement($stmt);
    }

    /**
     * Überprüft, ob die angegebene Page-URL bereits vergeben ist
     * @param string $url Die zu überprüfende Seite
     * @param null $id Optional eine nicht zu berücksichtigende Page-ID (bei Updates)
     * @return bool true, wenn die genannte URL bereits vergeben ist
     */
    public function isPageUrlAlreadyExisting(string $url, $id = null) {
        $params = array();

        $sql = 'SELECT COUNT(*) FROM page WHERE url = :url ';
        $params['url'] = $url;

        if ($id != null) {
            $sql .= ' AND id <> :id ';
            $params['id'] = $id;
        }

        $stmt = $this->basedb->prepare($sql);
        $this->bindValues($stmt, $params);
        $stmt->execute();
        return $stmt->fetchColumn() >= 1;
    }

    /**
     * Löscht die Page mit der angegebenen ID
     * @param int $id Datensatz-ID der zu löschenden Page
     * @return bool true bei Erfolg, ansonsten false
     */
    public function deletePageById(int $id) {
        try {
            // TODO Evtl. verknüpfte Daten löschen
            $sql = 'DELETE FROM page WHERE id = :page_id';
            $stmt = $this->basedb->prepare($sql);
            $stmt->bindValue('page_id', $id);
            $stmt->execute();
        } catch (\Exception $ex) {
            $this->log->err('Fehler beim Löschen der Page', $ex);
            return false;
        }
        return true;
    }

    /**
     * Löscht die Pages mit den übergebenen Datensatz-IDs
     * @param array $ids IDs der zu löschenden Pages
     * @return void
     */
    public function deletePagesById(array $ids) {
        foreach ($ids as $id) {
            $this->deletePageById($id);
        }
    }

    /**
     * Setzt den Status der Seiten mit den übergebenen IDs auf "gesperrt".
     * @param array $ids IDs der zu sperrenden Pages
     * @return void
     */
    public function lockPagesById(array $ids) {
        $sql = 'UPDATE page SET status_code = :status_code WHERE id = :page_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', StatusCode::LOCKED);

        foreach ($ids as $page_id) {
            $stmt->bindParam('page_id', $page_id);
            $stmt->execute();
        }
    }

    /**
     * Speichert eine Page in der Datenbank
     * @param Page $page Die zu speichernde Page
     * @return int Datensatz-ID
     * @todo Zugriffsrechte prüfen!
     */
    public function savePage(Page $page) {
        if ($page->id > 0) {
            $this->updatePage($page);
        } else {
            $page->id = $this->insertPage($page);
        }
        return $page->id;
    }

    /**
     * Aktualisiert einen Page-Datensatz
     * @param Page $page Die zu aktualisierende Page
     * @return void
     */
    private function updatePage(Page $page) {
        $page->modification_timestamp = new \DateTime();

        $sql = 'UPDATE page SET
                    modification_timestamp = DATETIME(CURRENT_TIMESTAMP, \'localtime\'),
                    author_id = :author_id,
                    status_code = :status_code,
                    url = :url,
                    headline = :headline,
                    content = :content,
                    publishing_timestamp = :publishing_timestamp
                WHERE
                    id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('author_id', $page->author_id);
        $stmt->bindValue('status_code', $page->status_code);
        $stmt->bindValue('url', $page->url);
        $stmt->bindValue('headline', $page->headline);
        $stmt->bindValue('content', $page->content);
        if ($page->publishing_timestamp != null) {
            $stmt->bindValue('publishing_timestamp', $page->publishing_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('publishing_timestamp', null);
        }
        $stmt->bindValue('id', $page->id);
        $stmt->execute();
    }

    /**
     * Speichert eine Page in einem neuen Datensatz
     * @param Page $page Die zu speichernde Page
     * @return int Die generierte Datensatz-ID
     */
    private function insertPage(Page $page) {
        if ($this->isPageUrlAlreadyExisting($page->url)) {
            throw new InvalidDataException("URL bereits vergeben: $page->url");
        }
        if (empty($page->url)) {
            throw new InvalidDataException("Es muss eine URL angegeben werden!");
        }

        $page->creation_timestamp = new \DateTime();
        $page->modification_timestamp = new \DateTime();

        $sql = '
            INSERT INTO page (
                creation_timestamp, modification_timestamp, author_id, status_code,
                url, headline, content, publishing_timestamp
            ) VALUES (
                DATETIME(CURRENT_TIMESTAMP, \'localtime\'), DATETIME(CURRENT_TIMESTAMP, \'localtime\'), :author_id, :status_code,
                :url, :headline, :content, :publishing_timestamp
            ) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('author_id', $page->author_id);
        $stmt->bindValue('status_code', $page->status_code);
        $stmt->bindValue('url', $page->url);
        $stmt->bindValue('headline', $page->headline);
        $stmt->bindValue('content', $page->content);

        if ($page->publishing_timestamp != null) {
            $stmt->bindValue('publishing_timestamp', $page->publishing_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('publishing_timestamp', null);
        }
        $stmt->execute();

        $page->id = $this->basedb->lastInsertId('id');
        return $page->id;
    }

    // </editor-fold>
    
    
    // <editor-fold desc="Shortcut methods">
    
    /**
     * Gibt den Copyright-Hinweis / die Footer-Notiz für die Website zurück
     * @return string
     */
    public function getCopyrightNotice() {
        return $this->getSettingValue(Setting::SYSTEM_COPYRIGHTNOTICE, '');
    }
    
    /**
     * Gibt den Standard-Seitentitel zurück.
     * 
     * Wenn der Seitentitel nicht ermittelt werden kann (weil beispielsweise
     * noch keine Datenbank vorhanden ist), so wird ein Vorgabetitel zurück
     * gegeben.
     * @return string Seitentitel
     */
    public function getSiteTitle() : string {
        $title = 'NanoCM';
        try {
            $title = $this->getSettingValue(Setting::SYSTEM_SITETITLE);
        } catch (\Exception $ex) {
            $this->log->warn($ex);
        }
        return $title;
    }
    
    // </editor-fold>


    // <editor-fold desc="Converter methods">

    /**
     * Konvertiert eine Benutzer-ID in den zugehörigen Anzeigenamen
     * @param int $userId
     * @return string
     */
    public function convertUserIdToName(int $userId) : string {
        $user = $this->getCachedUser($userId);
        if ($user == null) return '';
        return $user->getFullName();
    }

    /**
     * Gibt einen ggf. gecachten Benutzer-Datensatz zurück.
     *
     * Dieser Cache wird für Konvertierungen im User Interface verwendet und berücksichtigt auch immer alle
     * Benutzer-Datensätze unabhängig von ihrem Status.
     * Alle anderen Funktionen sollten Benutzerdatensätze grundsätzlich in "Echtzeit" überprüfen,
     * d. h. immer aktuelle Informationen aus der Datenbank beziehen.
     * @param int $userId
     * @return User|null
     */
    public function getCachedUser(int $userId) {
        if (!is_array(self::$userCache)) {
            self::$userCache = array();
        }
        if (array_key_exists($userId, self::$userCache)) {
            return self::$userCache[$userId];
        }

        $user = $this->getUserById($userId, true);
        if ($user == null) return null;

        self::$userCache[$userId] = $user;
        return $user;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function bindValues(\PDOStatement $stmt, array $params) {
        foreach ($params as $key => $value) {
            $this->log->debug($key . ': ' . $value);
            $stmt->bindValue($key, $value);
        }
    }

    // </editor-fold>
}