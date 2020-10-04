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

use Ubergeek\Controller\HttpController;
use Ubergeek\NanoCm\Module\AbstractModule;

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
class FrontController extends HttpController {
    
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
     * @TODO Generisches Mapping von URL-Strings auf Modulnamen
     */
    public function run() {
        $moduleName = null;

        // Gegebenenfalls Setup-Modul ausführen
        if (!$this->ncm->isNanoCmConfigured()) {
            $moduleName = 'SetupModule';
        }

        // Ansonsten: Standard-Ausführung für benannte Module
        else {
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
                        case 'basicsettings':
                        case 'definitions':
                        case 'articleseries':
                        case 'installation':
                        case 'meta':
                        case 'terms':
                            $moduleName = 'Admin' . ucfirst(strtolower($this->getRelativeUrlPart(1))) . 'Module';
                            break;

                        default:
                            $moduleName = 'AdminDashboardModule';
                            break;
                    }
                    break;

                // SOAP-Schnittstelle (für Remote-Administration)
                case 'soap':
                    // TODO implementieren
                    break;
            }

            // Im Standardfall auf das Kernmodul gehen
            if ($moduleName == null) {
                $moduleName = 'CoreModule';
            }
        }

        // Modul ausführen
        try {
            /* @var $module AbstractModule */
            $moduleName = '\Ubergeek\\NanoCm\\Module\\' . $moduleName;
            $module = new $moduleName($this);
            $module->execute();
        } catch (\Exception $ex) {
            http_response_code(500);
            $this->setTitle('Systemfehler!');
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

    public function createAbsoluteSiteLink(string $relativeLink) : string {
        $url = $this->getHttpRequest()->requestUri->protocol . '://';
        $url .= $this->getHttpRequest()->requestUri->host;
        if ($this->ncm->relativeBaseUrl != '/') {
            $url .= $this->ncm->relativeBaseUrl;
        }
        if (substr($url, -1) != '/' && substr($relativeLink, 0, 1) != '/') {
            $url .= '/';
        }
        $url .= $relativeLink;
        return $url;
    }
}