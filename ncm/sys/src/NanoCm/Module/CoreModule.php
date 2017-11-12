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
 * Kern(-Ausgabe-)modul des NanoCM.
 * 
 * Dieses Modul implementiert die Kern-Ausgabe-Funktionen des Content Managers.
 * Dazu gehören insbesondere die Startseite, die Darstellung einzelner Artikel
 * sowie das CMS-Archiv.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-12
 */
class CoreModule extends AbstractModule {
    
    public function run() {
        $parts = $this->getRelativeUrlParts();
        $content = null;
        
        switch ($parts[0]) {
            // Artikelansicht oder Archiv
            case 'weblog':
                if ($parts[1] == 'article') {
                    $content = $this->renderUserTemplate('content-weblog-article.phtml');
                } elseif ($parts[1] == 'archive') {
                    $content = $this->renderUserTemplate('content-weblog-archive.phtml');
                }
                break;
            
            // Startseite
            case 'index.php';
                $content = $this->renderUserTemplate('content-start.phtml');
                break;
        }
        
        if ($content == null) {
            http_response_code(404);
            $this->setContent($this->renderUserTemplate('error-404.phtml'));
        } else {
            $this->setContent($content);
        }
    }
    
}