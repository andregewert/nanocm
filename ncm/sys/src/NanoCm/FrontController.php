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
        // Wenn Installation noch nicht konfiguriert: Einrichtungsassistenten
        // ausführen
        
        // Datenbankverbindung herstellen (und testen!)
        
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
        
        $this->ncm->getLog()->debug('Testnachricht 1');
        $this->ncm->getLog()->flushWriters();
        $this->ncm->getLog()->closeWriters();
    }
    
    /**
     * Rendert ein Template, das installations-spezifisch überschrieben werden
     * kann.
     * @param string $file Das zu rendernde Template (ohne Pfadangabe)
     * @return string Inhalt des gerenderten Templates
     * @throws \Exception Exceptions, die bei der Ausführung des Templates
     *      geworfen werden, werden weitergeworfen
     * @todo Möglichkeit, ein spezifisches Template-Verzeichnis zu konfigurieren
     */
    public function renderUserTemplate(string $file) : string {
        
        // TODO Kontext-Objekt erstellen / deklarieren
        
        // TODO Prüfen, ob ein spezielles Template-Verzeichnis konfiguriert ist
        
        $fname = $this->ncm->createPath(array(
            $this->ncm->pubdir,
            'tpl',
            $file
        ));
        
        if (!file_exists($fname)) {
            $fname = $this->ncm->createPath(array(
                $this->ncm->sysdir,
                'tpl',
                $file
            ));
        }
        
        if (!file_exists($fname)) {
            throw new Exception("Template file not found: $file");
        }
        
        // Ermitteltes Template einbinden
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
    
    /**
     * Bindet an Ort und Stelle ein Template ein
     * @param string $file Relativer Pfad zum betreffenden Template
     */
    public function includeUserTemplate(string $file) {
        echo $this->renderUserTemplate($file);
    }
    
    /**
     * Kodiert einen String für die HTML-Ausgabe.
     * Der Eingabestring muss UTF8-kodiert sein.
     * @param string $string
     * @return HTML-kodierter String
     */
    public function html($string) : string {
        return htmlentities($string, ENT_HTML5, 'utf-8');
    }
}