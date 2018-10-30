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
            $this->ncm->log->debug("Setup durchführen!");
        }

        $moduleName = null;
        switch ($this->getRelativeUrlPart(0)) {
            
            // Admin-Modul (Web-Interface)
            case 'admin':
                switch ($this->getRelativeUrlPart(1)) {
                    case 'articles':
                    case 'comments':
                    case 'pages':
                    case 'lists':
                    case 'users':
                    case 'media':
                    case 'stats':
                    case 'settings':
                    case 'definitions':
                    case 'articleseries':
                        $moduleName = 'Admin' . ucfirst(strtolower($this->getRelativeUrlPart(1))) . 'Module';
                        break;

                    default:
                        $moduleName = 'AdminDashboardModule';
                        break;
                }
                break;

            // SOAP-Schnittstelle (für Remote-Administration)
            case 'soap':
                break;
        }

        // Im Standardfall im auf das Kernmodul gehen
        if ($moduleName == null) {
            $moduleName = 'CoreModule';
        }

        // TODO Exceptions besser / vernünftig darstellen!
        try {
            /* @var $module \Ubergeek\NanoCm\Module\AbstractModule */
            $moduleName = '\Ubergeek\\NanoCm\\Module\\' . $moduleName;
            $module = new $moduleName($this);
            $module->execute();
        } catch (\Exception $ex) {
            http_response_code(500);
            $this->setTitle($this->ncm->orm->getSiteTitle() . ' - Systemfehler!');
            var_dump($ex);
        }
        
        $this->ncm->log->flushWriters();
        $this->ncm->log->closeWriters();
    }

    /**
     * Gibt den zur NanoCM-Installation relativen Teil der
     * angeforderten URL zurück
     * @return string
     */
    public function getRelativeUrl() : string {
        $abs = $this->getHttpRequest()->requestUri->getBaseDocument();
        $rel = $this->ncm->relativeBaseUrl;
        return substr($abs, strlen($rel));
    }
    
    /**
     * Zerlegt den aktuellen HTTP-Request in seine Pfad-Bestandteile
     * @return string[]
     */
    public function getRelativeUrlParts() : array {
        $res = $this->getRelativeUrl();
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