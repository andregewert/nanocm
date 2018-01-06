<?php
/**
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm\Module;

/**
 * Verwaltung der frei definierbaren Seiten
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-01-07
 */
class AdminPagesModule extends AbstractAdminModule {

    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Seiten verwalten');
        $content = $this->renderUserTemplate('content-pages.phtml');
        //$content = 'test';
        $this->setContent($content);
    }

}