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

use Ubergeek\Cache\CacheInterface;

/**
 * Implementiert einen einfachen Mechanismus, um Inhalte per HTTP(S) von einer gegebenen URL auszulesen
 * Optional kann ein Cache verwendet werden, um die angeforderten Inhalte zwischenzuspeichern.
 *
 * @package Ubergeek\Net
 * @author André Gewert
 * @created 2018-10-31
 */
class Fetch {

    /**
     * Ruft den Inhalt von der angegebenen URL ab und gibt ihn als String zurück
     *
     * @param string $url Die abzurufende URL
     * @param CacheInterface $cache Optional zu nutzende Cache-Instanz
     * @return string Der abgerufene Inhalt
     */
    public static function fetchFromUrl(string $url, $cache = null) {

        // TODO Cache abfragen

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_status < 400) {
                curl_close($ch);
            } else {
                $response = '';
            }
        } else {
            $response = file_get_contents($url);
        }
        return $response;
    }

    /**
     * Überprüft, ob die angegebene URL fehlerfrei abgerufen werden kann
     *
     * Hinweis: Für diese Funktionalität muss die PHP-Extension curl installiert sein.
     * Andernfalls liefert die Methode immer true zurück.
     *
     * @param string $url Die zu prüfende URL
     * @return bool true, wenn der Zugriff (ohne Authentifizierung) fehlerfrei möglich ist
     */
    public static function isUrlAccessible(string $url) : bool {
        if (!function_exists('curl_init')) return true;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $result = curl_exec($ch);

        if ($result !== false) {
            $info = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            return $info == 200;
        }
        return false;
    }

}