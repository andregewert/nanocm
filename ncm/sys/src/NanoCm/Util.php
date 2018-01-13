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
 * Bietet einige statische Hilfmethoden
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
final class Util {

    /**
     * Kodiert einen String für die HTML-Ausgabe.
     * Der Eingabestring muss UTF8-kodiert sein.
     * @param string $string
     * @return string HTML-kodierter String
     */
    public static function htmlEncode(string $string) : string {
        return htmlentities($string, ENT_HTML5, 'utf-8');
    }
    
    /**
     * Vereinfacht einen String (bspw. eine Artikel-Headline) für die Darstellung
     * in der URL
     * @param string $string Ursprünglicher String
     * @return string Vereinfachter String
     */
    public static function simplifyUrlString(string $string) : string {
        $str = str_replace(array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'), array('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'), $string);
        $str = trim(preg_replace('/[^a-z0-9\-\_ ]/i', '', $str));
        return $str;
    }
    
}