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

namespace Ubergeek\NanoCm;

/**
 * Bildet einen Eintrag in der Access-Log ab
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-01
 */
class AccessLogEntry {

    // <editor-fold desc="Properties">

    public $accesstime = null;

    public $sessionid = '';

    public $method = '';

    public $url = '';

    public $fullurl = '';

    public $useragent = '';

    public $osname = '';

    public $osversion = '';

    public $browsername = '';

    public $browserversion = '';

    public $country = '';

    public $countrycode = '';

    public $region = '';

    public $regionname = '';

    public $city = '';

    public $zip = '';

    public $timezone = '';

    public $latitude = 0;

    public $longitude = 0;

    // </editor-fold>


    // <editor-fold desc="Public methods">

    public function __construct() {
        $this->accesstime = new \DateTime();
    }

    // </editor-fold>
}