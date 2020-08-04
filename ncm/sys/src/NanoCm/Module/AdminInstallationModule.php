<?php

namespace Ubergeek\NanoCm\Module;

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
     * @var BackupInfo[] Array with information of existing backups
     */
    public $existingBackups;

    // </editor-fold>


    /**
     * @inheritDoc
     */
    public function run() {
        $this->setTitle($this->getSiteTitle() . ' - Update und Backups verwalten');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // HTML blocks
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(3)) {

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

            // Index page
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-installation.phtml');
                break;
        }

        $this->setContent($content);
    }
}
