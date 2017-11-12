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

namespace Ubergeek\NanoCm\Module;

/**
 * Kapselt ein einfaches Setup für die Ersteinrichtung des NanoCM.
 * 
 * Der zentrale FrontController prüft bei seiner Ausführung, ob bereits eine
 * konfigurierte Datenbank vorhanden ist. Wenn das nicht der Fall ist, wird
 * immer das SetupModule ausgeführt. Das Setup besteht aus einem simplen
 * Formular, in das die grundlegendsten Einstellungen vorgenommen werden müssen.
 * Das Setup erstreckt über lediglich eine einzelne Seite. Werden die Eingaben
 * bestätigt, wird sofort die Datenbank erstellt und mit den gemachten Eingaben
 * gefüllt. Ab diesem Zeitpunkt sollte das SetupModule nicht wieder aufgerufen
 * werden (können).
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-12
 */
class SetupModule extends AbstractModule {
    
    public function run() {
        // TODO implementieren
        $this->setPageTemplate(self::PAGE_SETUP);
        $content = null;
        
        if ($this->getAction() == 'save') {
            // Datenbank erstellen
            
            // Grundlegende Konfiguration speichern
            
            // Redirect auf Startseite oder Anzeige einer Erfolgsmeldung
            
            $content = '';
            
        } else {
            // TODO Dieses Template sollte im SYS-Verzeichnis liegen
            $content = $this->renderUserTemplate('page-setup');
        }
        
        $this->setContent($content);
    }

}