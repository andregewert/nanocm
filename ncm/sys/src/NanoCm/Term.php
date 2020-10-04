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

namespace Ubergeek\NanoCm;

class Term {

    // <editor-fold desc="Properties">

    /**
     * @var int Type of term definition
     */
    public $type;

    /**
     * @var string Term
     */
    public $term;

    /**
     * @var string Optional data or description for this term
     */
    public $data;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * Term constructor.
     * @param array|null $data Data to initialize with
     */
    public function __construct($data = null) {
        if (is_array($data)) {
            $this->type = (int)$data['type'];
            $this->term = (string)$data['term'];
            $this->data = (string)$data['data'];
        }
    }

    // </editor-fold>


    // <editor-fold desc="Methods">

    /**
     * Creates a term object based on the current dataset cursor of the given dbo statement object
     * @param \PDOStatement $stmt
     * @return mixed|null
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        if (($def = $stmt->fetchObject(__CLASS__)) !== false) {
            return $def;
        }
        return null;
    }

    /**
     * Returns the primary key as a string
     *
     * @return string
     */
    public function getKey() {
        return $this->type . '_' . $this->term;
    }

    // </editor-fold>

}