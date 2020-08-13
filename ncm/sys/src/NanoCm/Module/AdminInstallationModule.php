<?php

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

                switch ($this->getRelativeUrlPart(3)) {
                    // Delete backup
                    case 'deletebackup':
                        break;

                    // Create a new backup
                    case 'createbackup':
                        $backupInfo = $this->ncm->installationManager->createBackup();
                        $this->ajaxResponse->info = $backupInfo;
                        $this->ajaxResponse->message = 'Backup created';
                        break;

                    // Restore existing backup
                    case 'restorebackup':
                        break;
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
