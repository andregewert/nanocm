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
 * Stellt einen einfachen Mechanismus zum Extrahieren von Basis-Informationen aus dem UserAgent-String bereit
 *
 * Das Parsing von Browser- und Betriebssystem-Informationen befindet sich aktuell auf einem sehr limierten Level und
 * sollte in der Zukunft Stück für Stück erweitert werden.
 *
 * Außerdem fehlt noch ein Caching der Parsing-Ergebnisse.
 *
 * Bessere Ergebnisse liefert sicherlich die Browsercap-Funktionalität, die grundsätzlich in PHP integriert ist, jedoch
 * in der Regel nicht auf Shared Servers zur Verfügung steht. NanoCM bietet dennoch Unterstützung für Browsecap, sofern
 * diese auf dem Server bereitsteht. Die Klasse UserAgentInfo ist nur als Fallback-Lösung anzusehen.
 *
 * @package Ubergeek\Net
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-01
 * @todo Caching implementieren!
 */
class UserAgentInfo {

    // <editor-fold desc="Properties">

    /**
     * Der unveränderte, vom Client gesendete UserAgent-String
     *
     * @var null|string
     */
    public $userAgent;

    /**
     * Der Name des Betriebssystems (ohne Versionsangabe)
     *
     * @var string
     */
    public $osName = 'Unknown';

    /**
     * Die Versionsangabe zum Betriebssystem
     *
     * @var string
     */
    public $osVersion = '';

    /**
     * Der Name des Browsers
     *
     * @var string
     */
    public $browserName = 'Unkown';

    /**
     * Die Versionsangabe zum Browser
     *
     * @var string
     */
    public $browserVersion = '';

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * Dem Konstruktor kann direkt der zu parsende UserAgent-String übergeben werden.
     *
     * Wenn kein UserAgent-String übergeben wird, so wird die Server-Variable
     * ausgewertet. Das Parsing wird direkt vom Konstruktor aufgerufen, so dass nach der
     * Instanziierung die erweiterten Eigenschaften sofort ausgelesen und ausgewertet
     * werden können.
     *
     * @param null|string $uaString
     */
    public function __construct($uaString = null) {
        if ($uaString == null) {
            $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $this->userAgent = $uaString;
        }

        $this->parseUserAgentString();
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Parst einen UserAgentString und extrahiert die wichtigsten Informationen daraus
     *
     * @return void
     */
    protected function parseUserAgentString() {
        $this->parseBrowser();
        $this->parseOperatingSystem();
    }

    protected function parseBrowser() {
        $dummy = strtolower($this->userAgent);

        // Internet Explorer
        if (preg_match('/msie (\d+\.\d+);/i', $dummy, $matches)) {
            $this->browserName = 'Internet Explorer';
            $this->browserVersion = $matches[1];
            return;
        }

        // Vivaldi
        else if (preg_match('/vivaldi\/([\d\.]+)/i', $dummy, $matches)) {
            $this->browserName = 'Vivaldi';
            $this->browserVersion = $matches[1];
            return;
        }

        // Chrome
        else if (preg_match('/chrome[\/\s](\d+\.\d+)/i', $dummy, $matches)) {
            $this->browserName = 'Chrome';
            $this->browserVersion = $matches[1];
            return;
        }

        // Edge
        else if (preg_match('/edge\/(\d+)/i', $dummy, $matches)) {
            $this->browserName = 'Edge';
            $this->browserVersion = $matches[1];
            return;
        }

        // Firefox
        else if (preg_match('/firefox[\/\s](\d+\.\d+)/i', $dummy, $matches)) {
            $this->browserName = 'Firefox';
            $this->browserVersion = $matches[1];
            return;
        }

        // Opera
        else if (preg_match('/opr[\/\s](\d+\.\d+)/i', $dummy, $matches)) {
            $this->browserName = 'Opera';
            $this->browserVersion = $matches[1];
            return;
        }

        // Safari
        else if (preg_match('/safari[\/\s](\d+\.\d+)/', $dummy, $matches)) {
            $this->browserName = 'Safari';
            $this->browserVersion = $matches[1];
            return;
        }
    }

    protected function parseOperatingSystem() {
        $dummy = strtolower($this->userAgent);

        // Mac OS
        if (strpos($dummy, '(macintosh;') !== false) {
            if (preg_match('/mac os (.+?)\)/i', $dummy, $matches)) {
                $this->osName = 'Mac OS';
                $this->osVersion = $matches[1];
                return;
            }
        }

        // Linux
        if (strpos($dummy, 'linux') !== false && strpos($dummy, 'android') === false) {
            $this->osName = 'Linux';
            return;
        }

        // Windows
        if (strpos($dummy, 'windows') !== false) {
            if (preg_match('/\(windows (.+?)\)/i', $dummy, $matches)) {
                $this->osName = 'Windows';
                $this->osVersion = $matches[1];
                return;
            }
        }

        // Android
        if (preg_match('/android ([\d\.]+)/i', $dummy, $matches)) {
            $this->osName = 'Android';
            $this->osVersion = $matches[1];
            return;
        }

        // iPhone / iPad / iPod
        if (preg_match('/(iphone|ipad|ipod);[\s\w]+?OS ([\d\_]+)/i', $dummy, $matches)) {
            $this->osName = 'iOS';
            $this->osVersion = $matches[2];
            return;
        }
    }

    // </editor-fold>
}