<?php

/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek\NanoCm\Module;

use function Couchbase\passthruDecoder;
use Ubergeek\NanoCm\StatusCode;
use Ubergeek\NanoCm\User;
use Ubergeek\NanoCm\UserType;

/**
 * Verwaltung der Benutzerkonten
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
class AdminUsersModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Die Liste der anzuzeigenden Benutzer-Datensätze
     * @var User[]
     */
    public $users;

    /**
     * Der zu bearbeitende Benutzer-Datensatz
     * @var User
     */
    public $user;

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
     * Die verfügbaren Status-Codes
     * @var int[]
     */
    public $availableStatusCodes = array(
        StatusCode::ACTIVE,
        StatusCode::LOCKED
    );

    /**
     * Enthält die verfügbaren Benutzerkontentypen
     * @var int[]
     */
    public $availableUserTypes = array(
        UserType::GUEST,
        UserType::EDITOR,
        UserType::ADMIN
    );

    // </editor-fold>

    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Benutzer verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchStatusCode = $this->getOrOverrideSessionVarWithParam('searchStatusCode');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Anfragen
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {

                    // Benutzerkonto speichern
                    case 'save':
                        $user = $this->createUserFromRequest();
                        $content = json_encode($this->orm->saveUser($user));
                        break;

                    // Benutzerkonto sperren
                    case 'lock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setUserStatusCodeByIds($ids, StatusCode::LOCKED);
                        $content = json_encode(true);
                        break;

                    // Benutzerkonto entsperren
                    case 'unlock':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->setUserStatusCodeByIds($ids, StatusCode::ACTIVE);
                        $content = json_encode(true);
                        break;

                    // Benutzerkonto löschen
                    case 'delete':
                        $ids = $this->getParam('ids');
                        if (is_array($ids)) $this->orm->deleteUsersByIds($ids);
                        $content = json_encode(true);
                        break;
                }

                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);
                switch ($this->getRelativeUrlPart(3)) {

                    // Benutzerkonto hinzufügen oder bearbeiten
                    case 'edit':
                        $this->user = $this->orm->getUserById(intval($this->getParam('id')), true);
                        if ($this->user == null) {
                            $this->user = new User();
                            $this->user->username = 'newuser';
                            $this->user->usertype = UserType::EDITOR;
                        }
                        $content = $this->renderUserTemplate('content-users-edit.phtml');
                        break;

                    // Benutzerkonten auflisten
                    case 'list':
                        $filter = new User();
                        $filter->status_code = $this->searchStatusCode;

                        $this->pageCount = ceil($this->orm->searchUsers($filter, $this->searchTerm, true) / $this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->users = $this->orm->searchUsers($filter, $this->searchTerm, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-users-list.phtml');
                        break;
                }
                break;

            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-users.phtml');
                break;
        }

        $this->setContent($content);
    }

    // <editor-fold desc="Internal methods">

    private function createUserFromRequest() : User {
        $id = intval($this->getParam('id'));
        $oldUser = ($id == 0)? null : $this->orm->getUserById($id, true);
        $user = ($oldUser == null)? new User() : $oldUser;

        $user->status_code = $this->getParam('status_code');
        $user->firstname = $this->getParam('firstname');
        $user->lastname = $this->getParam('lastname');
        $user->username = $this->getParam('username');
        $user->email = $this->getParam('email');
        $user->usertype = $this->getParam('usertype');

        if (!empty($this->getParam('password'))) {
            $user->password = password_hash($this->getParam('password'), PASSWORD_DEFAULT);
        } elseif ($oldUser = null) {
            $user->password = '';
        }

        return $user;
    }

    // </editor-fold>
}