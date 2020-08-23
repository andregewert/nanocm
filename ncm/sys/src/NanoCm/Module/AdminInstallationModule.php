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
use Ubergeek\NanoCm\BackupInfo;

/**
 * This module manages backups and installed versions / updates of nanoCM.
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm\Module
 * @created 2020-08-03
 */
class AdminInstallationModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Array with information of existing backups
     * @var BackupInfo[]
     */
    public $existingBackups;

    /**
     * Relative filename of the currently edited or created backup
     * @var string
     */
    public $filename;

    /**
     * Metadata for the selected backup file
     * @var BackupInfo
     */
    public $backupInfo;

    /**
     * Contains the response data for ajax requests
     * @var AjaxResponse
     */
    public $ajaxResponse;

    // </editor-fold>


    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Update und Backups verwalten');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);
        $content = '';

        switch ($this->getRelativeUrlPart(2)) {

            // HTML blocks
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(3)) {

                    // Dialog for creating new backup
                    case 'createbackup':
                        $this->filename = 'backup-' . date('Y-m-d H:i:s') . '.zip';
                        $content = $this->renderUserTemplate('content-installation-createbackup.phtml');
                        break;

                    // Dialog for restoring a given backup
                    case 'restorebackup':
                        $this->filename = $this->getParam('key');
                        $this->backupInfo = $this->ncm->installationManager->readBackupInfoByRelativeFilename($this->filename);
                        $content = $this->renderUserTemplate('content-installation-restorebackup.phtml');
                        break;

                    // List existing backups
                    case 'list':
                        $this->pageCount = ceil($this->ncm->installationManager->getAvailableBackups(true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->existingBackups = $this->ncm->installationManager->getAvailableBackups(false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-installation-list.phtml');
                        break;

                }
                break;

            // AJAX requests
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');
                $this->ajaxResponse = new AjaxResponse();

                try {
                    switch ($this->getRelativeUrlPart(3)) {

                        // Delete backup
                        case 'delete':
                            $keys = $this->getParam('keys');
                            if (is_array($keys)) {
                                foreach ($keys as $backupName) {
                                    $backupInfo = $this->ncm->installationManager->readBackupInfoByRelativeFilename($backupName);
                                    if ($backupInfo !== null) {
                                        $this->log->debug("Delete backup " . $backupInfo->filename);
                                        $this->ncm->installationManager->deleteBackup($backupInfo);
                                    }
                                }
                            }
                            $this->ajaxResponse->message = 'Backups deleted';
                            break;

                        // Create a new backup
                        case 'create':
                            $backupInfo = $this->ncm->installationManager->createBackup();
                            $this->ajaxResponse->info = $backupInfo;
                            $this->ajaxResponse->message = 'Backup created';
                            break;

                        // Restore existing backup
                        case 'restore':
                            $backupInfo = $this->ncm->installationManager->readBackupInfoByRelativeFilename(
                                $this->getParam('key')
                            );
                            if ($backupInfo !== null) {
                                $this->ncm->installationManager->restoreBackup($backupInfo);
                                $this->ajaxResponse->message = 'Backup restored';
                            }
                            break;
                    }
                } catch (\Exception $ex) {
                    $this->ajaxResponse->status = AjaxResponse::STATUS_ERROR;
                    $this->ajaxResponse->message = $ex->getMessage();
                    $this->ajaxResponse->info = $ex;
                }
                $content = json_encode($this->ajaxResponse);
                break;

            // Index page
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-installation.phtml');
                break;
        }

        $this->setContent($content);
    }
}
