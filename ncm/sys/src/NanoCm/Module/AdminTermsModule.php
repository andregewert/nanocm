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

use Ubergeek\NanoCm\Term;

/**
 * Administration of term definitions
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2020-10-04
 */
class AdminTermsModule extends AbstractAdminModule {

    // <editor-fold desc="Properties">

    /**
     * @var string Search term
     */
    public $searchTerm;

    /**
     * @var int Type of term definition
     */
    public $searchType = 1;

    /**
     * @var Term[] Current search result
     */
    public $terms;

    // </editor-fold>


    /**
     * @throws \Exception
     */
    public function run() {
        $content = '';
        $this->setTitle($this->getSiteTitle() . ' - Junk-Begriffe verwalten');

        $this->searchTerm = $this->getOrOverrideSessionVarWithParam('searchTerm');
        $this->searchPage = $this->getOrOverrideSessionVarWithParam('searchPage', 1);

        switch ($this->getRelativeUrlPart(2)) {

            // HTML blocks
            case 'html':
                $this->setPageTemplate(self::PAGE_NONE);

                switch ($this->getRelativeUrlPart(3)) {

                    // List of found terms
                    case 'list':
                        $this->pageCount = ceil(
                            $this->orm->searchTerms($this->searchType, $this->searchTerm, true) /$this->orm->pageLength
                        );
                        if ($this->searchPage > $this->pageCount) {
                            $this->searchPage = $this->pageCount;
                        }
                        $this->terms = $this->orm->searchTerms(
                            $this->searchType, $this->searchTerm, false, $this->searchPage
                        );
                        $content = $this->renderUserTemplate('content-terms-list.phtml');
                        break;
                }
                break;

            // Carrying page
            case 'index.php':
            case '':
                $content = $this->renderUserTemplate('content-terms.phtml');
                break;
        }

        $this->setContent($content);
    }


    // <editor-fold desc="Internal methods">

    // </editor-fold>
}