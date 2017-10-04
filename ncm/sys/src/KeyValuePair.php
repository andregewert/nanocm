<?php

/* 
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ubergeek;

/**
 * Bildet ein Schlüssel-Wert-Paar ab
 */
class KeyValuePair {
    
    /**
     * Schlüssel
     * @var string
     */
    public $key;

    /**
     * Wert
     * @var mixed
     */
    public $value;
    
    /**
     * Dem Konstruktor können optional direkt Schlüssel und Wert übergeben werden
     * @param string $key Schlüssel
     * @param object $value Wert
     */
    public function __construct(string $key = null, $value = null) {
        $this->key = $key;
        $this->value = $value;
    }
}