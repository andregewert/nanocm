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
    
    public function run() {
        // Fehlerbehandlung
        
        // Request parsen
        
        // Passendes Modul ausführen
        
        // Content generieren
        
        // Content in Template einfügen
        
        // Content ausgeben
        
        $this->setContent('Hallo Welt!');
    }
    
    protected function renderUserTemplate(string $tpl) {
        // Prüfen: benutzerdefiniertes Template vorhanden?
        // Dann: dieses rendern
        // Andernfalls: System-Vorgabe rendern
    }

}