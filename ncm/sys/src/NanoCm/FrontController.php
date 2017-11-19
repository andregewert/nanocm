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
 * Basis-Anwendung für das Nano CM.
 * 
 * Der FrontController beinhalt nicht viel mehr als ein simples Routing auf
 * passende Module, die jeweils eigene Controller-Implementierungen darstellen.
 * 
 * Die Modul-Struktur soll flexibel sein und die Entwicklung von neuen
 * Funktionen erleichtern. Sie ist aber nicht darauf angelegt, ein
 * Plugin-System darzustellen, bei dem jederzeit Erweiterungen nach Belieben
 * installiert werden können.
 * 
 * @author agewert@ubergeek.de
 */
class FrontController extends \Ubergeek\Controller\HttpController {
    
    /**
     * Enthält eine Referenz auf den ContentManager
     * @var \Ubergeek\NanoCm\NanoCm
     */
    public $ncm;

    /**
     * Dem Konstruktor wird lediglich der absolute Pfad zum öffentlichen
     * Verzeichnis übergeben, also üblicherweise der Pfad, in dem die zentrale
     * index.php liegt.
     * @param string $pubdir
     */
    public function __construct(string $pubdir) {
        parent::__construct();
        $this->ncm = NanoCm::createInstance($pubdir);
    }
    
    /**
     * Arbeitet den aktuellen Request ab und erzeugt eine entsprechende Antwort
     * (Response)
     */
    public function run() {
        // TODO Generisches Mapping von URL-Strings auf Modulnamen

        // TODO Gegebenenfalls Setup-Modul ausführen
        if (!$this->ncm->isNanoCmConfigured()) {
            $this->ncm->getLog()->debug("Setup durchführen!");
        }
        
        $moduleName = null;
        switch ($this->getRelativeUrlPart(0)) {
            // Admin-Modul (Web-Interface)
            case 'admin':
                switch ($this->getRelativeUrlPart(1)) {
                    case 'articles':
                        $moduleName = 'AdminArticlesModule';
                        break;
                    case 'users':
                        $moduleName = 'AdminUsersModule';
                        break;
                    case 'stats':
                        $moduleName = 'AdminStatsModule';
                        break;
                    case 'settings':
                        $moduleName = 'AdminSettingsModule';
                        break;
                    case '':
                    case 'index.php':
                        $moduleName = 'AdminDashboardModule';
                        break;
                }
                break;

            // SOAP-Schnittstelle (für Remote-Administration)
            case 'soap':
                break;

            // Frei definierbare Pages
            case 'page':
                $moduleName = 'PageModule';
                break;
            
            // Reines Testmodul
            case 'test':
                $moduleName = 'TestModule';
                break;

            // Weblog / Kernfunktionen
            case 'weblog':
            default:
                $moduleName = 'CoreModule';
        }

        // TODO Exceptions vernünftig abfangen und darstellen!
        try {
            if ($moduleName !== null) {
                $moduleName = '\Ubergeek\\NanoCm\\Module\\' . $moduleName;
                $module = new $moduleName($this);
                $module->execute();
            }
        } catch (\Exception $ex) {
            var_dump($ex);
        }
        
        $this->ncm->log->flushWriters();
        $this->ncm->log->closeWriters();
    }
    
    /**
     * Zerlegt den aktuellen HTTP-Request in seine Pfad-Bestandteile
     * @return string[]
     */
    public function getRelativeUrlParts() : array {
        $abs = $this->getHttpRequest()->requestUri->getBaseDocument();
        $rel = $this->ncm->relativeBaseUrl;
        $res = substr($abs, strlen($rel));
        $parts = explode('/', $res);
        
        $dummy = array_pop($parts);
        if (!empty($dummy)) {
            array_push($parts, $dummy);
        }
        
        if (preg_match('/\.([^\.]+)$/i', $dummy) == false) {
            array_push($parts, 'index.php');
        }

        return $parts;
    }
    
    public function getRelativeUrlPart(int $idx) : string {
        $parts = $this->getRelativeUrlParts();
        if (count($parts) > $idx) {
            return $parts[$idx];
        }
        return '';
    }
}