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
use Ubergeek\NanoCm\UserType;
use Ubergeek\NanoCm\Exception;

/**
 * Basisklasse für Administrations-Module
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
abstract class AbstractAdminModule extends AbstractModule {

    /**
     * Gibt die Anzahl der anzuzeigenden Elemente an
     * @var integer
     */
    public $pageCount;

    /**
     * Gibt die aktuell angezeigte Seite an
     * @var integer
     */
    public $page;

    /**
     * Überprüft auf grundlegende Zugriffsberechtigung auf den
     * Administrationsbereiche
     * @throws Exception\AuthorizationException
     */
    public function init() {
        if ($this->ncm->isUserLoggedIn() && $this->ncm->getLoggedInUser()->usertype >= UserType::EDITOR) {
            $this->templateDir = 'tpladm';
            $this->allowUserTemplates = false;
            $this->setPageTemplate('page-admin.phtml');
        } else {
            throw new Exception\AuthorizationException("Authentifizierung notwendig!");
        }
    }
    
}