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
use Ubergeek\NanoCm\AjaxResponse;
use Ubergeek\NanoCm\Setting;

/**
 * Verwaltung von Systemeinstellungen
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminSettingsModule extends AbstractAdminModule {

    // <editor-fold desc="Additional public properties">

    /** @var \Ubergeek\NanoCm\Setting */
    public $setting;

    /** @var \Ubergeek\NanoCm\Setting[] */
    public $settings;

    /**
     * Suchbegriff
     * @var string
     */
    public $searchTerm;

    /**
     * Generated response to an ajax request
     * @var AjaxResponse
     */
    public $ajaxResponse;

    // </editor-fold>


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
                $this->ajaxResponse = new AjaxResponse();

                switch ($this->getRelativeUrlPart(3)) {

                    // Clear all caches
                    case 'clearcaches':
                        $this->ncm->clearAllCaches();
                        $this->ajaxResponse->message = 'Caches cleared';
                        break;

                    // Delete a specific setting
                    case 'delete':
                        $keys = $this->getParam('keys');
                        $this->ajaxResponse->message = 'Setting deleted';

                        try {
                            $this->orm->deleteSettingsByKey($keys);
                        } catch (\Exception $ex) {
                            $this->log->err("Error while deleting setting", $ex);
                            $this->ajaxResponse->status = AjaxResponse::STATUS_ERROR;
                            $this->ajaxResponse->message = 'Error while deleting setting';
                        }
                        break;

                    // Save a specific setting
                    case 'save':
                        $newSetting = new Setting();
                        $newSetting->key = $this->getParam('key');
                        $newSetting->value = $this->getParam('value');
                        $newSetting->params = $this->getParam('params');
                        $this->ajaxResponse->message = 'Setting saved';

                        try {
                            $this->orm->saveSetting($newSetting);
                        } catch (\Exception $ex) {
                            $this->log->err('Error while saving setting', $ex);
                            $this->ajaxResponse->status = AjaxResponse::STATUS_ERROR;
                            $this->ajaxResponse->message = 'Error while saving setting';
                        }
                        break;
                }

                $content = json_encode($this->ajaxResponse);
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/html');

                switch ($this->getRelativeUrlPart(3)) {
                    case 'phpinfo':
                        $content = $this->renderUserTemplate('content-settings-phpinfo.phtml');
                        break;

                    case 'phpinfo2':
                        $content = $this->renderUserTemplate('content-settings-phpinfo2.phtml');
                        break;

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
