<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2019 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\Cache;

/**
 * Implementiert keine Cache-Funktionialität
 *
 * Kann an Stellen verwendet werden, an denen zwar eine CacheInterface-Implementierung erwartet wird, jedoch kein
 * Caching verwendet werden soll.
 *
 * @package Ubergeek\Cache
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2019-01-13
 */
class NoCache implements CacheInterface
{

    /**
     * Liest - falls vorhanden - einen Wert aus dem Cache aus
     *
     * Ist der angeforderte Wert nicht im Cache vorhanden, so soll
     * diese Methode null zurückgeben.
     *
     * @param $key string
     * @return mixed|null
     */
    public function get(string $key) {
        return null;
    }

    /**
     * Legt einen Wert unter dem angegebenen Schlüssel im Cache ab
     *
     * @param $key string
     * @param $value mixed
     * @return void
     */
    public function put(string $key, $value) {
    }

    /**
     * Aktualisiert den Timestamp eines Cache-Eintrages
     *
     * Arbeitet der konrekte Cache mit einem Ablaufzeitraum für seine Einträge, so
     * kann durch Aufruf dieser Methode der Ablauf eines Eintrages vermieden / verzögert werden.
     *
     * @param $key string
     * @return bool true, wenn der Eintrag tatsächlich im Cache vorhanden ist, ansonsten false
     */
    public function touch(string $key) {
        return false;
    }

    /**
     * Leert den Cache vollständig
     *
     * @return void
     */
    public function clear() {
    }

    /**
     * Löscht einen Eintrag aus dem Cache
     *
     * @param $key string
     * @return bool true, wenn der Eintrag vorhanden war und gelöscht worden ist; false, wenn der Eintrag
     * nicht vorhanden war
     */
    public function unset(string $key) {
        return false;
    }
}