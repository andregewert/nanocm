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
use Ubergeek\NanoCm\Setting;

/**
 * Verwaltung von Systemeinstellungen
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminSettingsModule extends AbstractAdminModule {

    /** @var string Generierter Content */
    private $content;

    /** @var \Ubergeek\NanoCm\Setting */
    protected $setting;

    /** @var \Ubergeek\NanoCm\Setting[] */
    protected $settings;

    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Einstellungen verwalten');

        switch ($this->getRelativeUrlPart(2)) {
            case 'index.php':
            case '':
                $filter = new Setting();
                $this->settings = $this->orm->searchSettings($filter, 20);
                $this->content = $this->renderUserTemplate('content-settings.phtml');
                break;
        }

        $this->setContent($this->content);
    }

}
