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
 * Bildet Informationen zu einer Geolocation ab
 *
 * @package Ubergeek\Net
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-10-31
 */
class Geolocation {

    // <editor-fold desc="Properties">

    /**
     * Ländername
     * @var string
     */
    public $country;

    /**
     * Länderkürzel
     * @var string
     */
    public $countryCode;

    /**
     * Regionalcode (bspw. HH für Hamburg)
     * @var string
     */
    public $region;

    /**
     * Name der Region
     * @var string
     */
    public $regionName;

    /**
     * Stadt
     * @var string
     */
    public $city;

    /**
     * Postleitzahl
     * @var string
     */
    public $zip;

    /**
     * Latitude
     * @var float
     */
    public $latitude;

    /**
     * Longitude
     * @var float
     */
    public $longitude;

    /**
     * Zeitzone
     * @var string
     */
    public $timezone;

    /**
     * Name des Internet Service Providers
     * @var string
     */
    public $isp;

    /**
     * Name der Organisation
     * @var string
     */
    public $organisation;

    /**
     * Nummer / Name des autonomen Systems
     * @var string
     */
    public $asName;

    // </editor-fold>

}