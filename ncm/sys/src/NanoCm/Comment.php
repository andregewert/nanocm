<?php
/**
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Ubergeek\NanoCm;


use http\Exception\BadQueryStringException;

/**
 * Bildet einen Artikelkommentar ab
 * @author agewert@ubergeek.de
 * @package Ubergeek\NanoCm
 * @created 2018-05-15
 */
class Comment {

    // <editor-fold desc="Properties">

    /**
     * Eindeutige Datensatz-ID
     * @var integer
     */
    public $id;

    /**
     * Datensatz-ID des verknüpften Artikels
     * @var integer
     */
    public $article_id;

    /**
     * Zeitpunkt der Erstellung
     * @var \DateTime
     */
    public $creation_timestamp;

    /**
     * Zeitpunkt der letzten Änderung
     * @var \DateTime
     */
    public $modification_timestamp;

    /**
     * Statuscode des Datensatzes
     * @var integer
     */
    public $status_code;

    /**
     * Spam-Stauts
     * @var integer
     */
    public $spam_status;

    /**
     * Frei eingebbarer Benutzername für den Kommentar
     * @var string
     */
    public $username;

    /**
     * E-Mail-Adresse des Kommentators
     * @var string
     */
    public $email;

    /**
     * Überschrift für den Kommentar
     * @var string
     */
    public $headline;

    /**
     * Eigentlicher Kommentarinhalt
     * @var string
     */
    public $content;


    // </editor-fold>


    // <editor-fold desc="Public methods">

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    // </editor-fold>
}