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
 * Basis-Anwendung für das Nano CM
 */
class NanoCmController extends \Ubergeek\Controller\HttpController {
    
    /**
     * Enthält eine Referenz auf den ContentManager
     * @var ContentManager
     */
    private $cm;
    
    /**
     * Verweis auf das System-Verzeichnis des NanoCM
     * @var string
     */
    private $sysdir;
    
    /**
     * Verweis auf das öffentliche Verzeichnis (HTTP-ROOT)
     * @var string
     */
    private $pubdir;
    
    /**
     * Name des Seiten-Templates, in das der Inhalt eingeschlossen werden soll
     * @var string
     */
    private $frametpl;
    
    /**
     * Absoluter Pfad zum Verzeichnis mit Site-spezifischen Dateien
     * @var string
     */
    private $sitedir;
    
    /**
     * Optionale Logger-Instanz
     * @var \Ubergeek\Log\Logger
     */
    private $logger;
    
    
    /**
     * Dem Konstruktor wird lediglich der absolute Pfad zum öffentlichen
     * Verzeichnis übergeben, also üblicherweise der Pfad, in dem die zentrale
     * index.php liegt.
     * @param string $pubdir
     */
    public function __construct(string $pubdir) {
        $this->cm = ContentManager::getInstance($pubdir);
        
        $this->pubdir = $pubdir;
        $this->sysdir = $this->createPath(array($pubdir, 'ncm', 'sys'));
        $this->sitedir = $this->createPath(array($pubdir, 'site'));
        
        // Datenbankverbindung herstellen (und testen!)
        
        // Optional: Wenn DB nicht vorhanden, auf Installer umleiten
        
        // Logging initialisieren

    }
    
    /**
     * Arbeitet den aktuellen Request ab und erzeugt eine entsprechende Antwort
     * (Response)
     */
    public function run() {
        // Fehlerbehandlung
        
        // Request parsen
        
        // Passendes Modul ausführen
        
        // Content generieren
        
        // Content in Template einfügen
        
        // Content ausgeben
        
        // Eigentlichen Inhalt rendern
        try {
            $content = $this->renderUserTemplate('test.phtml');
        } catch (\Exception $ex) {
            $content = $this->renderUserTemplate('exception.phtml');
        }
        $this->setContent($content);
        
        // Äußeres Template rendern
        $this->setContent($this->renderUserTemplate('page-standard.phtml'));
        
        $this->cm->getLog()->debug('Testnachricht 1');
        $this->cm->getLog()->flushWriters();
        $this->cm->getLog()->closeWriters();
    }
    
    protected function parseRequestUri() {
        // ...
        // Anhand der Request-URI aufzurufendes Modul etc. ermitteln
    }
    
    /**
     * Rendert ein Template, das installations-spezifisch überschrieben werden
     * kann.
     * @param string $file Das zu rendernde Template (ohne Pfadangabe)
     * @return string Inhalt des gerenderten Templates
     * @throws \Exception Exceptions, die bei der Ausführung des Templates
     *      geworfen werden, werden weitergeworfen
     */
    protected function renderUserTemplate(string $file) : string {
        $fname = $this->createPath(array(
            $this->sitedir,
            'tpl',
            $file
        ));
        
        if (!file_exists($fname)) {
            $fname = $this->createPath(array(
                $this->sysdir,
                'tpl',
                $file
            ));
        }
        
        if (!file_exists($fname)) {
            throw new Exception("Template file not found: $category/$file");
        }
        
        ob_start();
        try {
            include($fname);
            $c = ob_get_contents();
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            ob_end_clean();
        }
        return $c;
    }
    
    // <editor-fold desc="Interne Methoden">
    
    private function createPath(array $parts) : string {
        return join(DIRECTORY_SEPARATOR, $parts);
    }
    
    // </editor-fold>
}