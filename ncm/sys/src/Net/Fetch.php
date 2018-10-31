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

namespace Ubergeek\Net;

/**
 * Implementiert einen einfachen Mechanismus, um Inhalte per HTTP(S) von einer gegebenen URL auszulesen
 * Optional kann ein Cache verwendet werden, um die angeforderten Inhalte zwischenzuspeichern.
 *
 * @package Ubergeek\Net
 * @author André Gewert
 * @created 2018-10-31
 */
class Fetch {

    public static function fetchFromUrl($url, $cache = null) {

        // TODO Cache abfragen

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $response = file_get_contents($url);
        }
        return $response;
    }

}