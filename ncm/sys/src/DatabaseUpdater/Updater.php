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

namespace Ubergeek\DatabaseUpdater;

use Ubergeek\Log\Logger;
use Ubergeek\Log\LoggerInterface;
use Ubergeek\Log\Writer\NullWriter;

/**
 * Implementiert einen einfachen Einrichtungs- und Update-Mechanismus für SQL-Datenbanken
 *
 * Diese Klasse implementiert die grundlegenden Mechanismen. Für alles Datenbank-spezifische müssen
 * entsprechende Klassen das Interface DatabaseInterface implementieren. Da NanoCM mit SQLite verwendet,
 * existiert aktuell ausschließlich eine konkrete Interface-Implementierung für ebendieses SQLite.
 *
 * @package Ubergeek\DatabaseUpdater
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-06-26
 */
class Updater {

    // <editor-fold desc="Constants">

    /**
     * Script- bzw. Aktionstyp: Create
     * @var string
     */
    const TYPE_CREATE = 'create';

    /**
     * Script- bzw. Aktionstyp: Update
     * @var string
     */
    const TYPE_UPDATE = 'update';

    // </editor-fold>


    // <editor-fold desc="Properties">

    /**
     * Datenbankspezifische Schnittstellen-Implementierungen
     *
     * @var DatabaseInterface
     */
    private $databaseInterface;

    /**
     * Pfad zu den DDL-Dateien (SQL)
     *
     * @var string
     */
    private $ddlPath;

    /**
     * Optional zu verwendende Logger-Instanz
     * @var LoggerInterface
     */
    private $log;

    // </editor-fold>


    // <editor-fold desc="Constructors">

    /**
     * Dem Konstruktor werden alle benötigten Abhängigkeiten direkt übergeben
     *
     * @param string $ddlPath Pfad zu den DDL-Scripten
     * @param DatabaseInterface $databaseInterface Referenz auf die datenbankspezifischen Schnittstellen-Klasse
     * @param null|LoggerInterface $log Optionale Referenz auf einen zu verwendenden Logger
     */
    public function __construct($ddlPath, $databaseInterface, $log = null) {
        $this->ddlPath = $ddlPath;
        $this->databaseInterface = $databaseInterface;
        if ($log == null) {
            $log = new Logger(new NullWriter());
        }
        $this->log = $log;
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Führt ein automatisches Update der Datenbankstrukturen auf die aktuellste verfügbare Version durch
     * @return integer Die neue Versionsnummer der Datenbank
     * @todo Bessere Fehlerbehandlung implementieren!
     */
    public function updateDatabaseToLatestVersion() {

        if ($this->areUpdatesAvailable()) {
            $current = $this->getCurrentDatabaseVersion();
            $latest = $this->getLatestAvailableVersion();
            $this->log->info("Current version is: $current");
            $this->log->info("Latest version is: $latest");

            // Auszuführende Scripts ermitteln
            if ($current == 0) {
                $scripts = $this->getScriptsSinceVersion($latest -1, self::TYPE_CREATE);
            }
            else {
                $scripts = $this->getScriptsSinceVersion($current, self::TYPE_UPDATE);
            }

            // Scripts ausführen
            foreach ($scripts as $script) {
                try {
                    $this->executeScript($script);
                } catch (\Exception $ex) {
                    $this->log->err("Fehler beim Ausführen des SQL-Scripts");
                    $this->log->err($ex);
                }
            }

            // Neue Versionsnummer schreiben
            $this->saveCurrentDatabaseVersion($latest);
            if ($current == 0) {
                $this->log->info("Created database with version $latest");
            } else {
                $this->log->info("Updated database to version $latest");
            }
        } else {
            $this->log->info("No database updates available");
        }

        return $latest;
    }

    /**
     * Ermittelt die Versionsnummer der neuesten verfügbaren Datenbank-Definition.
     * Wenn keine Datenbank-Definitionsscripte gefgunden werden kann, wird null zurückgegeben.
     * @return int|null Versionsnummer oder null
     */
    public function getLatestAvailableVersion() {
        $latest = null;
        if (($dh = opendir($this->ddlPath)) !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname != '.' && $fname != '..' && preg_match("/^(\d+)\-(update|create)\-.+\.sql$/i", $fname, $matches) !== false) {
                    $fileVersion = intval($matches[1]);
                    if ($latest === null || $fileVersion > $latest) {
                        $latest = $fileVersion;
                    }
                }
            }
        }
        return $latest;
    }

    /**
     * Ermittelt, ob für die installierte bzw. eingesetzte Datenbankstruktur Update verfügbar sind.
     * @return bool true, wenn Datenbank-Updates verfügbar sind
     */
    public function areUpdatesAvailable() {
        $current = $this->getCurrentDatabaseVersion();
        $latest = $this->getLatestAvailableVersion();
        if ($latest === null) return false;
        return $latest > $current;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function getVersionFilename() {
        return $this->ddlPath . DIRECTORY_SEPARATOR . 'version';
    }

    /**
     * Ermittelt die Versionsnummer für die aktuell installierte bzw. genutzte Datenbank
     * @return integer Die aktuell genutzte Datenbank-Version
     */
    private function getCurrentDatabaseVersion() {
        $versionFile = $this->getVersionFilename();
        if (file_exists($versionFile)) {
            $version = (int)file_get_contents($versionFile);
        } else {
            $version = 0;
        }
        return $version;
    }

    /**
     * Speichert die angegebene Versionsnummer in der dafür vorgesehenen Datei
     *
     * @param integer $version Versionsnummer
     * @return void
     */
    private function saveCurrentDatabaseVersion($version) {
        $versionFile = $this->getVersionFilename();
        file_put_contents($versionFile, $version);
    }

    /**
     * Ermittelt die SQL-Scripte für einen bestimmten Aktionstyp seit (exklusive) einer bestimmten Version
     *
     * @param integer $version
     * @param string $type
     * @return Script[]
     */
    private function getScriptsSinceVersion($version, $type = self::TYPE_CREATE) {
        $version = intval($version);
        $scripts = array();
        if ($type != self::TYPE_CREATE && $type != self::TYPE_UPDATE) return $scripts;

        if (($dh = opendir($this->ddlPath)) !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname != '.' && $fname != '..' && preg_match("/^(\d+)\-$type\-.+\.sql$/i", $fname, $matches) !== false) {
                    $fileVersion = (int)$matches[1];
                    if ($fileVersion > $version) {
                        $scripts[] = new Script($this->ddlPath . DIRECTORY_SEPARATOR . $fname);
                    }
                }
            }
        }

        return $scripts;
    }

    /**
     * Führt das angegebene Script aus
     * @param Script $script
     * @return void
     */
    private function executeScript($script) {
        $this->log->info("Running script $script->filename");
        $pdo = $this->databaseInterface->createDatabaseConnection($script->databaseName);
        $pdo->exec($script->contents);
    }

    // </editor-fold>

}