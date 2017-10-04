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

namespace Ubergeek\Net;

class Url {
    
    /**
     * Absolute URLs
     */
    const TYPE_ABSOLUTE = 'absolute';
    
    /**
     * Server-relative URLs
     */
    const TYPE_RELATIVE = 'relative';
    
    
    /**
     * Das verwendete Protokoll: HTTP oder HTTPS
     * @var string
     */
    public $protocol;
    
    /**
     * Name des HTTP-Hosts
     * @var string
     */
    public $host;
    
    /**
     * Das vollständige angeforderte Dokument, inklusive eventueller Parameter
     * @var string
     */
    public $document;

    
    public function __construct(\Ubergeek\Controller\HttpRequest $httpRequest = null) {
        if ($request == null) {
            $this->protocol = (isset($_SERVER['HTTPS']))? 'https' : 'http';
            $this->host = $_SERVER['HTTP_HOST'];
            $this->document = $_SERVER['REQUEST_URI'];
        }
    }
    
    public function getBaseDocument(string $separator = '?') : string {
        $arr = explode($separator, $this->document, 2);
        return $arr[0];
    }
    
    public function getParams(string $separator = '?') : string {
        $arr = explode($separator, $this->document, 2);
        if (count($arr) >= 2) return $arr[1];
        return '';
   }
}