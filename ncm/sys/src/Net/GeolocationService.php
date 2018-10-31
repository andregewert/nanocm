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
 * Nutzt den Service ip-api.com, um Geolocation-Daten zu IP-Adressen zu ermitteln
 *
 * Die Nutzung der API von ip-api.com ist ausschließlich für nicht-kommerzielle Zwecke
 * kostenlos erlaubt. Beim kommerziellen Einsatz von NCM sind deshalb alle
 * Geolocation-Funktionen auszuschalten!
 *
 * Bei einem nicht-kommerziellen Einsatz können mit Hilfe dieses Geolocation-Services
 * Statistiken zu den Herkunftsländern von Website-Besuchern erfasst werden.
 *
 * @package Ubergeek\Net
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-10-31
 */
class GeolocationService {

    // <editor-fold desc="Properties">

    /**
     * Zu verwendender Cache
     * @var CacheInterface
     */
    private $cache;

    // </editor-fold>


    // <editor-fold desc="Construtor">

    /**
     * Dem Konstruktor kann eine zu verwendende Cache-Instanz übergeben werden.
     * Wird keine Cache-Instanz übergeben, so werden die Anfragen nicht gecacht.
     *
     * @param CacheInterface $cache
     */
    public function __construct($cache = null) {
        $this->cache = $cache;
    }

    // <editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Ermittelt Geolocation-Informationen zur angegebenen IPv4-Adresse
     *
     * @param string $ip
     * @return Geolocation
     */
    public function getGeolocationForIpAddress(string $ip) {
        $info = null;

        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\$/', $ip) === false) {
            return null;
        }

        if ($this->cache instanceof CacheInterface) {
            $info = $this->cache->get($ip);
            if ($info != null) {
                return $info;
            }
        }

        $result = json_decode(Fetch::fetchFromUrl("http://ip-api.com/json/$ip"));
        if (is_object($result) && $result->status == 'success') {
            $info = new Geolocation();
            $info->country = $result->country;
            $info->countryCode = $result->countryCode;
            $info->region = $result->region;
            $info->regionName = $result->regionName;
            $info->city = $result->city;
            $info->zip = $result->zip;
            $info->latitude = floatval($result->lat);
            $info->longitude = floatval($result->lon);
            $info->timezone = $result->timezone;
            $info->isp = $result->timezone;
            $info->organisation = $result->org;
            $info->asName = $result->as;
        }

        if ($info != null && $this->cache instanceof CacheInterface) {
            $this->cache->put($ip, $info);
        }
        return $info;
    }

    // </editor-fold>

}