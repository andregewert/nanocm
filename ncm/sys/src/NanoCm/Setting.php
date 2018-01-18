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

namespace Ubergeek\NanoCm;

/**
 * Bildet einen Eintrag aus den nanocm-Systemeinstellungen ab
 * @author agewert@ubergeek.de
 */
class Setting
    extends \Ubergeek\KeyValuePair {
    
    /**
     * Optionale Parameter für diese Einstellung
     * @var string
     */
    public $params = null;

    /**
     * Dem Konstruktor können optional direkt Schlüssel und Wert sowie ein
     * weiterer Parameter übergeben werden
     * @param string $key Schlüssel
     * @param object $value Wert
     * @param null $params
     */
    public function __construct(string $key = null, $value = null, $params = null) {
        parent::__construct($key, $value);
        $this->params = $params;
    }

    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        if (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $setting = new Setting();
            $setting->key = $row['name'];
            $setting->value = $row['setting'];
            $setting->params = $row['params'];
            return $setting;
        }
        return null;
    }
}