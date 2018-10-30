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

use Ubergeek\NanoCm\Definition;

/**
 * Verwaltung von Definitionen
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-10-30
 */
class AdminDefinitionsModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * Die Liste der anzuzeigenden Definitionen
     * @var Definition[]
     */
    public $definitions;

    /**
     * Die zu bearbeitende Definition
     * @var Definition
     */
    public $definition;

    /**
     * Suchbegriff
     * @var string
     */
    public $searchTerm;

    // </editor-fold>


    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Definitionen verwalten');
        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // AJAX-Anfragen
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($this->getRelativeUrlPart(3)) {

                    // Datensatz speichern
                    case 'save':
                        $definition = $this->createDefinitionFromRequest();
                        $this->orm->saveDefinition($definition);
                        $content = json_encode(true);
                        break;

                    // Ausgewählte Datensätze löschen
                    case 'delete':
                        $keys = $this->getParam('keys');
                        if (is_array($keys)) {
                            foreach ($keys as $key) {
                                list ($t, $k) = explode(' ', $key, 2);
                                $this->orm->deleteDefinitionByTypeAndKey($t, $k);
                            }
                        }
                        $content = json_encode(true);
                        break;
                }
                break;

            // Einzelne HTML-Blöcke
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {
                    case 'edit':
                        $this->definition = $this->orm->getDefinitionByTypeAndKey(
                            $this->getParam('definitiontype'),
                            $this->getParam('key')
                        );
                        if ($this->definition == null) $this->definition = new Definition();
                        $content = $this->renderUserTemplate('content-definitions-edit.phtml');
                        break;

                    case 'list':
                        $this->pageCount = ceil($this->orm->searchDefinitions(null, $this->searchTerm, true) /$this->orm->pageLength);
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->definitions = $this->orm->searchDefinitions(null, $this->searchTerm, false, $this->searchPage);
                        $content = $this->renderUserTemplate('content-definitions-list.phtml');
                }

                break;

            // Trägerseite
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-definitions.phtml');
        }

        $this->setContent($content);
    }

    // <editor-fold desc="Internal methods">

    private function createDefinitionFromRequest() {
        $definitiontype = $this->getParam('definitiontype');
        $key = $this->getParam('key');
        $oldDefinition = null;

        if (!empty($definitiontype) && !empty($key)) {
            $oldDefinition = $this->orm->getDefinitionByTypeAndKey($definitiontype, $key);
        }

        $definition = ($oldDefinition != null)? $oldDefinition : new Definition();
        $definition->definitiontype = $this->getParam('definitiontype');
        $definition->key = $this->getParam('key');
        $definition->title = $this->getParam('title');
        $definition->value = $this->getParam('value');
        $definition->parameters = $this->getParam('parameters');
        return $definition;
    }

    // </editor-fold>
}