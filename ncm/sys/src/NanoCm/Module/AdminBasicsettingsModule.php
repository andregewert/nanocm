<?php

// NanoCM
// Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

namespace Ubergeek\NanoCm\Module;
use Ubergeek\NanoCm\Setting;

/**
 * Management of basic settings
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2020-07-25
 */
class AdminBasicsettingsModule extends AbstractAdminModule {

    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Grundlegende Einstellungen verwalten');

        switch ($this->getRelativeUrlPart(2)) {
            // Übersichtsseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-basicsettings.phtml');
                break;
        }

        $this->setContent($content);
    }

}
