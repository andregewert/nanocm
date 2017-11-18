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
use Ubergeek\NanoCm\Exception;

class AdminModule extends AbstractModule {
    
    public function init() {
        // TODO Benutzertypen festlegen und an dieser Stelle auf "Admin" prüfen
        if ($this->ncm->isUserLoggedIn()) {
            $this->templateDir = 'tpladm';
            $this->allowUserTemplates = false;
        } else {
            throw new Exception\AuthorizationException("Authentifizierung notwendig!");
        }
    }
    
    public function run() {
        $this->setPageTemplate('page-admin.phtml');
        
        $content = null;
        
        $content = $this->renderUserTemplate('content-dashboard.phtml');
        
        if ($content == null) {
            $this->setTitle($this->getPageTitle() . ' - Seite nicht gefunden!');
            http_response_code(404);
            $this->setContent($this->renderUserTemplate('error-404.phtml'));
        } else {
            $this->setContent($content);
        }
    }
    
}