<?php

/**
 * NanoCM
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

use Ubergeek\NanoCm\Medium;
use Ubergeek\NanoCm\StatusCode;
use Ubergeek\NanoCm\Util;

/**
 * Verwaltung der Benutzerkonten
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-01-13
 * @todo Überprüfen, ob das Medienverzeichnis wirklich schreibbar ist
 */
class AdminMediaModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Suchbegriff
     *
     * @var string
     */
    public $searchTerm;

    /**
     * Suchfilter: Statuscode
     *
     * @var int
     */
    public $searchStatusCode;

    /**
     * Suchfilter: übergeordneter Ordner
     *
     * @var int
     */
    public $searchParentId;

    /**
     * Auflistung der übergeordneten Ordner
     *
     * @var Medium[]
     */
    public $parentFolders;

    /**
     * Der aktuell gewählte Ordner
     *
     * @var Medium
     */
    public $currentFolder;

    /**
     * Liste der anzuzeigenden Medien
     *
     * @var Medium[]
     */
    public $media;

    /**
     * Zu bearbeitende Mediendatei oder zu bearbeitender Ordner
     *
     * @var Medium
     */
    public $medium;

    /**
     * Die für Listen-Datensätze verfügbaren Statuscodes
     *
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::ACTIVE,
        StatusCode::LOCKED
    );

    /**
     * Gibt an, ob das Medienverzeichnis schreibbar ist
     *
     * @var bool
     */
    public $isMediaDirWritable = false;

    // </editor-fold>


    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Medien verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchStatusCode = $this->getOrOverrideSessionVarWithParam('searchStatusCode');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);
        $this->searchParentId = $this->getOrOverrideSessionVarWithParam('searchParentId', 0);

        if ($this->searchParentId > 0) {
            $this->currentFolder = $this->orm->getMediumById($this->searchParentId);
            $this->parentFolders = $this->orm->getParentFolders($this->searchParentId);
        }

        switch ($this->getRelativeUrlPart(2)) {

            // Datei-Upload
            case 'upload':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                $file = $this->getParam('file');
                $data = $file['fileData'];

                $medium = new Medium();
                $medium->entrytype = Medium::TYPE_FILE;
                $medium->parent_id = intval($this->getParam('parent_id'));
                $medium->title = $file['name'];
                $medium->filename = $file['name'];
                $medium->filesize = $file['size'];
                $medium->type = $file['type'];
                $medium->extension = Util::getFileExtension($file['name']);
                $medium->id = $this->orm->insertInitialMedium($medium, $data);

                $content = json_encode($medium);
                break;

            // AJAX-Aufrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {

                    // Ordner speichern
                    case 'savefolder':
                        $medium = $this->createFolderFromRequest();
                        $this->orm->saveMedium($medium);
                        $content = json_encode(true);
                        break;

                    // Mediendatei speichern
                    case 'savemedium':
                        // TODO implementieren
                        $content = json_encode(true);
                        break;

                    // Mediendateien löschen
                    case 'delete':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->deleteMediaByIds($ids);
                        $content = json_encode(true);
                        break;

                    // Mediendateien sperren
                    case 'lock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setMediaStatusCodesById($ids, StatusCode::LOCKED);
                        $content = json_encode(true);
                        break;

                    // Mediendateien entsperren
                    case 'unlock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setMediaStatusCodesById($ids, StatusCode::ACTIVE);
                        $content = json_encode(true);
                        break;
                }

                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(3)) {

                    // Auflistung von Medien
                    case 'list':
                        $filter = new Medium();
                        $filter->status_code = $this->searchStatusCode;
                        $this->pageCount = ceil($this->orm->searchMedia($filter, $this->searchParentId, $this->searchTerm, true) / $this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->media = $this->orm->searchMedia($filter, $this->searchParentId, $this->searchTerm, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-media-list.phtml');
                        break;

                    // Einen Medieneintrag bearbeiten
                    case 'editmedium':
                        // TODO implementieren
                        $content = $this->renderUserTemplate('content-media-editmedium.phtml');
                        break;

                    // Einen Ordner bearbeiten
                    case 'editfolder':
                        $this->medium = $this->orm->getMediumById($this->getParam('id'), Medium::TYPE_FOLDER);
                        if ($this->medium == null) {
                            $this->medium = new Medium();
                            $this->medium->entrytype = Medium::TYPE_FOLDER;
                            $this->medium->filename = 'Neuer Ordner';
                            $this->medium->parent_id = $this->searchParentId;
                            $this->medium->status_code = StatusCode::ACTIVE;
                            $this->medium->tags = array();
                        }
                        $content = $this->renderUserTemplate('content-media-editfolder.phtml');
                        break;

                    // Bildauswahl
                    case 'imageselection':
                        $content = $this->renderUserTemplate('media-imageselection.phtml');
                        break;

                }
                break;

            // Trägerseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-media.phtml');
        }

        $this->setContent($content);
    }


    // <editor-fold desc="Methods">

    public function getFileImage($extension) {
        $extension = strtolower($extension);
        $extension = preg_replace('/[^a-z0-9]/i', '', $extension);
        $path = Util::createPath($this->ncm->ncmdir, 'img', 'fatcow', '16', 'file_extension_' . $extension . '.png');
        if (file_exists($path)) {
            return 'file_extension_' . $extension . '.png';
        }
        return 'file_extension_bin.png';
    }

    private function createFolderFromRequest() : Medium {
        $id = intval($this->getParam('id'));
        $oldMedium = ($id == 0)? null : $this->orm->getMediumById($id, Medium::TYPE_FOLDER);
        $medium = ($oldMedium == null)? new Medium() : $oldMedium;

        $medium->entrytype = Medium::TYPE_FOLDER;
        $medium->parent_id = intval($this->getParam('parent_id'));
        $medium->status_code = StatusCode::ACTIVE;
        $medium->filename = $this->getParam('filename');
        $medium->filesize = 0;
        $medium->extension = '';
        $medium->type = '';
        $medium->title = $this->getParam('title');
        $medium->description = $this->getParam('description');
        $medium->attribution = '';

        return $medium;
    }

    // </editor-fold>
}