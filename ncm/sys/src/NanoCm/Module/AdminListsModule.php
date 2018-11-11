<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Ubergeek\NanoCm\Module;
use Ubergeek\NanoCm\StatusCode;
use Ubergeek\NanoCm\UserList;
use Ubergeek\NanoCm\UserListItem;

/**
 * Verwaltung der Artikel
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-10-28
 */
class AdminListsModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Die anzuzeigenden Listen
     * @var UserList[]
     */
    public $lists;

    /**
     * Die zu bearbeitende Liste
     * @var UserList
     */
    public $list;

    /**
     * Die UserListItems einer ausgewählten Liste
     * @var UserListItem[]
     */
    public $listItems;

    /**
     * Der zu bearbeitende Listeneintrag
     * @var UserListItem
     */
    public $listItem;

    /**
     * Suchbegriff
     * @var string
     */
    public $searchTerm;

    /**
     * Suchfilter: Statuscode
     * @var integer
     */
    public $searchStatusCode;

    /**
     * Die für Listen-Datensätze verfügbaren Statuscodes
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::ACTIVE,
        StatusCode::LOCKED
    );

    // </editor-fold>


    /**
     * @throws \Exception
     */
    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Listen verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchStatusCode = $this->getOrOverrideSessionVarWithParam('searchStatusCode');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Anfragen
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {
                    // Liste löschen
                    case 'delete':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->deleteUserListsById($ids);
                        $content = json_encode(true);
                        break;

                    // Liste sperren
                    case 'lock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setUserListStatusCodesById($ids, StatusCode::LOCKED);
                        $content = json_encode(true);
                        break;

                    // Liste entsperren
                    case 'unlock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setUserListStatusCodesById($ids, StatusCode::ACTIVE);
                        $content = json_encode(true);
                        break;

                    // List speichern
                    case 'save':
                        $list = $this->createUserListFromRequest();
                        $content = json_encode($this->orm->saveUserList($list));
                        break;

                    // Listeneinträge sperren
                    case 'lockitems':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setUserListItemsStatusCodeById($ids, StatusCode::LOCKED);
                        $content = json_encode(true);
                        break;

                    // Listeneinträge entsperren
                    case 'unlockitems':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setUserListItemsStatusCodeById($ids, StatusCode::ACTIVE);
                        $content = json_encode(true);
                        break;

                    // Listeneinträge löschen
                    case 'deleteitems':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->deleteUserListItemsById($ids);
                        $content = json_encode(true);
                        break;

                    // Listeneintrag speichern
                    case 'saveitem':
                        $listitem = $this->createUserListItemFromRequest();
                        $content = json_encode($this->orm->saveUserListItem($listitem));
                        break;
                }

                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {

                    // Liste bearbeiten
                    case 'edit':
                        $this->list = $this->orm->getUserListById($this->getParam('id'), false);
                        if ($this->list == null) {
                            $this->list = new UserList();
                            $this->list->title = 'Neue Liste';
                            $this->list->status_code = StatusCode::LOCKED;
                        }
                        $content = $this->renderUserTemplate('content-lists-edit.phtml');
                        break;

                    // Listeneintrag bearbeiten
                    case 'edititem':
                        $this->listItem = $this->orm->getUserListItemById($this->getParam('id'), false);
                        if ($this->listItem == null) {
                            $this->listItem = new UserListItem();
                            $this->listItem->userlist_id = intval($this->getParam('userlist_id'));
                            $this->listItem->title = 'Neuer Listeneintrag';
                            $this->listItem->status_code = StatusCode::LOCKED;
                        }
                        $content = $this->renderUserTemplate('content-lists-edititem.phtml');
                        break;

                    // Auflistung der Listen
                    case 'list':
                        $filter = new UserList();
                        $filter->status_code = $this->searchStatusCode;

                        $this->pageCount = ceil($this->orm->searchUserLists($filter, $this->searchTerm, true) / $this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->lists = $this->orm->searchUserLists($filter, $this->searchTerm, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-lists-list.phtml');
                        break;

                    // Auflistung von Listeneinträgen
                    case 'listitems':
                        $this->list = $this->orm->getUserListById($this->getParam('id'), false);
                        if ($this->list != null) {
                            $this->listItems = $this->orm->searchUserListItemsByUserListId(
                                $this->list->id,
                                $this->searchStatusCode,
                                $this->searchTerm
                            );
                            $this->log->debug($this->listItems);
                        }
                        $content = $this->renderUserTemplate('content-lists-listitems-list.phtml');
                        break;
                }
                break;

            // Listeneinträge auflisten
            case 'listitems':
                $this->list = $this->orm->getUserListById($this->getRelativeUrlPart(3), false);
                $content = $this->renderUserTemplate('content-lists-listitems.phtml');
                break;

            // Trägerseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-lists.phtml');
                break;
        }

        $this->setContent($content);
    }


    // <editor-fold desc="Internal methods">

    private function createUserListFromRequest() : UserList {
        $list = new UserList();
        $id = intval($this->getParam('id'));
        $oldList = ($id == 0)? null : $this->orm->getUserListById($id, false);

        if ($oldList != null) {
            $list->id = $oldList->id;
            $list->key = $oldList->key;
            $list->creation_timestamp = $oldList->creation_timestamp;
            $list->modification_timestamp = $oldList->modification_timestamp;
        }
        $list->key = $this->getParam('key');
        $list->title = $this->getParam('title');
        $list->status_code = intval($this->getParam('status_code'));
        return $list;
    }

    private function createUserListItemFromRequest() : UserListItem {
        $id = intval($this->getParam('id'));
        $oldItem = ($id == 0)? null : $this->orm->getUserListItemById($id, false);

        if ($oldItem != null) {
            $listitem = $oldItem;
        } else {
            $listitem = new UserListItem();
        }

        $listitem->userlist_id = intval($this->getParam('userlist_id'));
        $listitem->parent_id = intval($this->getParam('parent_id'));
        $listitem->status_code = intval($this->getParam('status_code'));
        $listitem->title = $this->getParam('title');
        $listitem->content = $this->getParam('content');
        $listitem->parameters = $this->getParam('parameters');
        $listitem->sorting_code = $this->getParam('sorting_code');
        return $listitem;
    }

    // </editor-fold>
}