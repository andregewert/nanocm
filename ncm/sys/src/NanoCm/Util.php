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
     * @param string|null $string
     * @return string HTML-kodierter String
     */
    public static function htmlEncode($string) : string {
        if (empty($string)) {
            $string = '';
        }
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

    /**
     * Gibt eine gruppierte / kategorisierte Liste von Emoji-Codes zurück.
     *
     * Die Definitionsdatei der verfügbaren Emojis wird generiert aus der vom Unicode Consortium bereitgestellten
     * Testdatei und enthält zunächst alle im Unicode-Standard definierten Emojis. Eine Blacklist ist separat vorhanden
     * und kann -- installationsspezifisch -- vom jeweiligen Administrator angepasst werden. Im Standard-Zustand sind in
     * der Blacklist diejenigen Emojis enthalten, die auf dem iMac des Entwicklers nicht verfünftig unterstützt werden.
     *
     * Eine Idee ist auch, das anzuzeigende Emoji-Spektrum konfigurierbar zu machen, denn für die meisten Zwecke ist die
     * Anzahl der verfügbaren Emojis ohnehin viel zu groß.
     *
     * @return array
     * @todo Eventuell die gesamte Emoji-Funktionalität in eine separate Klasse verschieben
     */
    public static function getEmojiDictionary() {
        $emojis = (array)json_decode(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji-list.json'));
        $blacklist = json_decode(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji-blacklist.json'));

        foreach (array_keys($emojis) as $group) {
            $emojis[$group] = (array)$emojis[$group];

            foreach ($blacklist as $blacklistKey) {
                $emojiKeys = array_keys($emojis[$group]);
                $wildcard = null;
                if (substr($blacklistKey, -1) == '*') {
                    $wildcard = 'begin';
                    $blacklistKey = substr($blacklistKey, 0, -1);
                } elseif (substr($blacklistKey, 0, 1) == '*') {
                    $wildcard = 'end';
                    $blacklistKey = substr($blacklistKey, 1);
                }

                foreach ($emojiKeys as $emojiKey) {
                    if (
                        $blacklistKey == $emojiKey
                        || ($wildcard == 'begin' && substr($emojiKey, 0, strlen($blacklistKey)) == $blacklistKey)
                        || ($wildcard == 'end' && substr($emojiKey, -strlen($blacklistKey)) == $blacklistKey)
                    ) {
                        unset($emojis[$group][$emojiKey]);
                    }
                }

                /*
                if (in_array($blacklistKey, array_keys($emojis[$group]))) {
                    unset($emojis[$group][$blacklistKey]);
                }
                */
            }
        }
        return $emojis;
    }
    
}