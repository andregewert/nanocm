<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2019 André Gewert <agewert@ubergeek.de>
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

/**
 * Erlaubt den datenbasierten Abruf von Meta- und anderen Daten aus dem CMS
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm\Module
 * @created 2019-09-12
 */
class AdminMetaModule extends AbstractAdminModule {

    public function run() {
        $content = '';
        $this->setContentType('text/javascript');
        $this->setPageTemplate(self::PAGE_NONE);

        switch ($this->getRelativeUrlPart(2)) {

            case 'getArticleUrl':
                $articleId = intval($this->getRelativeUrlPart(3));
                $article = $this->orm->getArticleById($articleId, false);

                if ($article != null) {
                    $content = array(
                        'status'    => 0,
                        'url'       => $this->convUrl($article->getArticleUrl())
                    );
                } else {
                    $content = array(
                        'status'    => 1,
                        'url'       => null
                    );
                }
                break;
        }

        $this->setContent(json_encode($content));
    }

}