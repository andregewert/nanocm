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
    var $cm;
    
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
    
    private $sitedir;
    
    private $logger;
    
    public function __construct(string $pubdir) {
        $this->cm = ContentManager::getInstance($pubdir);
        
        $this->pubdir = $pubdir;
        $this->sysdir = $this->createPath(array($pubdir, 'ncm', 'sys'));
        $this->sitedir = $this->createPath(array($pubdir, 'site'));
        
        //echo "Pubdir: $this->pubdir<br>";
        //echo "Sysdir: $this->sysdir<br>";
        
        // Datenbankverbindung herstellen
        
        // Optional: Wenn DB nicht vorhanden, auf Installer umleiten
        
        // Logging initialisieren

    }
    
    public function run() {
        // Fehlerbehandlung
        
        // Request parsen
        
        // Passendes Modul ausführen
        
        // Content generieren
        
        // Content in Template einfügen
        
        // Content ausgeben
        
        $content = $this->renderUserTemplate('frame', 'page.phtml');
        $this->setContent($content);
        
        $this->cm->getLog()->debug('Testnachricht 1');
        $this->cm->getLog()->flushWriters();
        $this->cm->getLog()->closeWriters();
        
        //if ($this->cm->getLog()->has)
        //$this->addMeta('X-ChromeLogger-Data', $this->logger->createOutputString());
    }
    
    protected function parseRequestUri() {
        // ...
        // Anhand der Request-URI aufzurufendes Modul etc. ermitteln
    }
    
    protected function renderUserTemplate(string $category, string $file) : string {
        $fname = $this->createPath(array(
            $this->sitedir,
            'tpl',
            $category,
            $file
        ));
        //echo $fname . "<br>";
        
        if (!file_exists($fname)) {
            $fname = $this->createPath(array(
                $this->sysdir,
                'tpl',
                $category,
                $file
            ));
        }
        //echo $fname . "<br>";
        
        if (!file_exists($fname)) {
            throw new Exception("Template file not found: $category/$file");
        }
        
        ob_start();
        include($fname);
        $c = ob_get_contents();
        ob_end_clean();
        return $c;
    }

    /*
    protected function renderUserTemplate(string $tpl) : string {
        // Prüfen: benutzerdefiniertes Template vorhanden?
        // Dann: dieses rendern
        // Andernfalls: System-Vorgabe rendern
        
        if (file_exists($this->createPath(array(
            $this->pubdir, $tpl
        )))) {
            ob_start();
            include $this->createPath(array($this->pubdir, 'tpl', $tpl));
            $c = ob_get_contents();
            ob_end_clean();
            return $c;
        } else if (file_exists($this->createPath(array(
            $this->sysdir, $tpl
        )))) {
            ob_start();
            include $this->createPath(array($this->sysdir, 'tpl', $tpl));
            $c = ob_get_contents();
            ob_end_clean();
            return $c;
        }
        throw new \Exception('Template not found: ' . $tpl);
    }
    */
    
    private function createPath(array $parts) : string {
        return join(DIRECTORY_SEPARATOR, $parts);
    }
}