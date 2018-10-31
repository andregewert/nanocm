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
 * Kapselt die Daten zu einer URL
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\Net
 */
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
     * Explizit gennanter Port oder null
     * @var integer
     */
    public $port;
    
    /**
     * Das vollständige angeforderte Dokument, inklusive eventueller Parameter
     * @var string
     */
    public $document;

    /**
     * Wenn dem Konstruktor keine Request-URL übergeben wird, dann werden die
     * Daten aus den Server-Superglobals gefüllt
     * @param string|null $requestUrl
     * @throws \Exception Wenn die übergebene URL nicht geparst werden kann
     */
    public function __construct(string $requestUrl = null) {
        if ($requestUrl == null) {
            $this->protocol = (isset($_SERVER['HTTPS']))? 'https' : 'http';
            $this->host = $_SERVER['HTTP_HOST'];
            $this->document = $_SERVER['REQUEST_URI'];
        } else {
            if (preg_match('/^((https?)\:\/\/)(([^\/\:]+)(\:(\d+))?)(.*)$/i', $requestUrl, $matches) !== false) {
                $this->protocol = $matches[2];
                $this->host = $matches[4];
                $this->port = $matches[6];
                $this->document = $matches[7];
            } else {
                throw new \Exception("Ungültige Request-URL übergeben!");
            }
        }
    }
    
    /**
     * Gibt die vollständige angeforderte URL inklusive Protokoll, Port
     * und Hostnamen als String zurück
     * @return string Komplette angeforderte URL
     */
    public function getRequestUrl() : string {
        $url = $this->protocol . '://' . $this->host;
        if (!empty($this->port)) {
            $url .= ':' . $this->port;
        }
        $url .= $this->document;
        return $url;
    }
    
    /**
     * Gibt das angeforderte Dokument ohne URL-Parameter zurück
     * @param string $separator
     * @return string Angefordertes Dokument ohne URL-Parameter
     */
    public function getBaseDocument(string $separator = '?') : string {
        $arr = explode($separator, $this->document, 2);
        return $arr[0];
    }
    
    /**
     * Gibt die in der URL enthaltenen Parameter als String zurück
     * @param string $separator
     * @return string URL-Parameter als String
     */
    public function getParams(string $separator = '?') : string {
        $arr = explode($separator, $this->document, 2);
        if (count($arr) >= 2) {
            return $arr[1];
        }
        return '';
   }

   /**
    * Gibt die in der URL enthaltenen Parameter in Form eines Arrays zurück
    * @param string $paramSeparator Trennzeichen zwischen den Parametern
    * @param string $valueSeparator Trennzeichen zwischen Parameternamen und Wert
    * @param string $docSeparator Trennzeichen zwischen Dokument und Parameter-String
    * @return array Zerlegte URL-Parameter
    */
   public function getParamsArray(string $paramSeparator = '&', string $valueSeparator = '=', string $docSeparator = '&') : array {
       $params = array();
       $string = $this->getParams($docSeparator);
       foreach (explode($paramSeparator, $string) as $param) {
           list($key, $value) = explode($valueSeparator, $param);
           $params[$key] = $value;
       }
       return $params;
   }
}