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

namespace Ubergeek\NanoCm;

/**
 * Bildet eine frei definierbare CMS-Seite ab
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2018-01-13
 */
class Page {

    // <editor-fold desc="Properties">

    /**
     * Eindeutige Datensatz-ID
     * @var integer
     */
    public $id;

    /**
     * Zeitpunkt der Datensatz-Erstellung
     * @var \DateTime
     */
    public $creation_timestamp;

    /**
     * Zeitpunkt der letzten Datensatz-Änderung
     * @var \DateTime
     */
    public $modification_timestamp;

    /**
     * Benutzer-ID des Autors
     * @var integer
     */
    public $author_id;

    /**
     * Statuscode des Datensatzes
     * @var integer
     */
    public $status_code;

    /**
     * URL zu dieser Seite
     * @var string
     */
    public $url;

    /**
     * Seitenüberschrift
     * @var string
     */
    public $headline;

    /**
     * Eigentlicher Seiteninhalt
     * @var string
     */
    public $content;

    /**
     * Veröffentlichungszeitpunkt
     * @var \DateTime|null
     */
    public $publishing_timestamp = null;

    // <editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Erstellt ein Page-Objekt aus dem übergebenen PDO-Statement
     * @param \PDOStatement $stmt
     * @return Page
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $page \Ubergeek\NanoCm\Page */

        if (($page = $stmt->fetchObject(__CLASS__)) !== false) {
            $page->creation_timestamp = new \DateTime($page->creation_timestamp);
            $page->modification_timestamp = new \DateTime($page->modification_timestamp);
            if ($page->publishing_timestamp != null) {
                $page->publishing_timestamp = new \DateTime($page->publishing_timestamp);
            }
            return $page;
        }
        return null;
    }

    public function getPageUrl() : string {
        //return '/page/' . $this->id . '/' . urlencode($this->headline);
        return 'page/' . $this->url;
    }

    // </editor-fold>

}