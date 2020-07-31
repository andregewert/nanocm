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
use Ubergeek\NanoCm\TemplateInfo;

/**
 * Management of basic settings
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2020-07-25
 */
class AdminBasicsettingsModule extends AbstractAdminModule {

    // <editor-fold desc="Additional public properties">

    /**
     * Array of all currently existing settings
     * @var Setting[]
     */
    public $currentSettings;

    /**
     * Generated response to an ajax request
     * @var AjaxResponse
     */
    public $ajaxResponse;

    /**
     * An array including information for available templates
     * @var TemplateInfo[]
     */
    public $availableTemplates;

    // </editor-fold>


    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Grundlegende Einstellungen verwalten');
        $this->currentSettings = $this->ncm->orm->getAllSettings();
        $this->availableTemplates = $this->ncm->installationManager->getAvailableTemplates();

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX requests
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');
                $this->ajaxResponse = new AjaxResponse();

                switch ($this->getRelativeUrlPart(3)) {
                    case 'savesettings':
                        $this->ajaxResponse->message = 'Settings saved';
                        foreach ($this->getParam('settings') as $key => $value) {
                            try {
                                $setting = new Setting($key, $value);
                                $this->ncm->orm->saveSetting($setting);
                            } catch (\Exception $ex) {
                                $this->log->warn("Exception while saving setting", $ex);
                                $this->ajaxResponse->status = AjaxResponse::STATUS_ERROR;
                                $this->ajaxResponse->message = 'Error while saving some settings';
                            }
                        }
                        break;
                }

                $content = json_encode($this->ajaxResponse);
                break;

            // Carrying page
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-basicsettings.phtml');
                break;
        }

        $this->setContent($content);
    }

}
