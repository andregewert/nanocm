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
use Couchbase\Exception;
use Ubergeek\NanoCm\Setting;

/**
 * Verwaltung von Systemeinstellungen
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminSettingsModule extends AbstractAdminModule {

    /** @var \Ubergeek\NanoCm\Setting */
    public $setting;

    /** @var \Ubergeek\NanoCm\Setting[] */
    public $settings;

    /**
     * Suchbegriff
     * @var string
     */
    public $searchTerm;

    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Erweiterte Einstellungen verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {
            // AJAX-Anfragen
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {
                    case 'delete':
                        $keys = $this->getParam('keys');
                        $this->orm->deleteSettingsByKey($keys);
                        $content = json_encode(true);
                        break;

                    case 'save':
                        $newSetting = new Setting();
                        $newSetting->key = $this->getParam('key');
                        $newSetting->value = $this->getParam('value');
                        $newSetting->params = $this->getParam('params');
                        $this->orm->saveSetting($newSetting);
                        break;
                }
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {
                    case 'edit':
                        $this->setting = $this->orm->getSetting($this->getParam('key', ''));
                        if ($this->setting == null) {
                            $this->setting = new Setting();
                        }
                        $content = $this->renderUserTemplate('content-settings-edit.phtml');
                        break;

                    case 'list':
                    default:
                        $filter = new Setting();

                        $this->pageCount = ceil($this->orm->searchSettings($filter, $this->searchTerm, true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->settings = $this->orm->searchSettings($filter, $this->searchTerm, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-settings-list.phtml');
                }
                break;

            // Übersichtsseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-settings.phtml');
                break;
        }

        $this->setContent($content);
    }

}
