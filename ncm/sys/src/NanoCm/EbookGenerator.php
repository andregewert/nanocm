<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
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
 * Kapselt Funktionen zum Erstellen von E-Books
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@gmail.com>
 * @created 2020-01-09
 */
class EbookGenerator {

    // <editor-fold desc="Internal properties">

    /**
     * Referenz auf die laufende NanoCM-Instanz
     * @var NanoCm
     */
    private $ncm;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct(NanoCm $ncm) {
        $this->ncm = $ncm;
        // ...
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    public function createEpubForArticleWithId(int $id) {
        // TODO implementieren
    }

    public function createEpubForArticleSeriesWithId(int $id) {
        // TODO implementieren
    }

    // </editor-fold>

}