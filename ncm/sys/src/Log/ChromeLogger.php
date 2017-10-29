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

namespace Ubergeek\Log;

/**
 * Simple Implementierung des ChromeLogger-Protokolls
 */
class ChromeLogger {
    
    //log, warn, error, info, group, groupEnd, groupCollapsed, and table. 
    
    /**
     * HTTP-Header-Name für die ChromeLogger-Ausgabe
     * @var string
     */
    const HEADER_NAME = 'X-ChromeLogger-Data';
    
    
    /**
     * Versionsnummer dieser Klasse / Bibliothek
     * @var string
     */
    private $version = '0.1';
    
    /**
     * Bezeichner der generierten Protokoll-Spalten
     * @var array
     */
    private $columns = array('log', 'backtrace', 'type');
    
    /**
     * Ein Array mit den einzelnen generierten Log-Ausgaben
     * @var array
     */
    private $rows = array();

    /**
     * Erstellt den passend kodierten String, der per HTTP-Header "X-ChromeLogger-Data"
     * an den Browser gesendet werden soll
     */
    public function createOutputString(): string {
        $data = str_replace(
            array("\n", "\r"),
            '',
            base64_encode(
                json_encode(array(
                    'version' => $this->version,
                    'columns' => $this->columns,
                    'rows' => $this->rows
                ))
            )
        );
        return $data;
    }
    
    public function debug($data, string $trace = "") {
        $this->log($data, "log", $trace);
    }
    
    public function warn($data, string $trace = "") {
        $this->log($data, "warn", $trace);
    }
    
    public function info($data, string $trace = "") {
        $this->log($data, "info", $trace);
    }
    
    public function table($tabledata, string $trace = "") {
        // TODO implementieren
    }
    
    public function hasOutput() : bool {
        return is_array($this->rows) && count($this->rows) >= 1;
    }
    
    public function log($data, string $type = 'log', string $trace = "") {
        if (!is_array($this->rows)) {
            $this->rows = array();
        }
        
        if (empty($trace)) {
            $backtrace = debug_backtrace();
            if (is_array($backtrace) && array_key_exists(1, $backtrace)) {
                $trace = basename($backtrace[1]['file']);
                $trace .= ': ' . $backtrace[1]['line'];
            } else {
                $trace = 'unknown';
            }
        }
        
        $this->rows[] = array($this->convertVar($data), $trace, $type);
    }
    
    /**
     * Bereitet eine PHP-Variable für ChromeLogger-komforme JSON-Kodierung vor
     * @param mixed $var
     * @return mixed
     */
    protected function convertVar($var) {
        if (is_string($var)) {
            $var = array(str_replace(array("\n", "\r"), '', $var));
        }
        else if (!is_array($var)) {
            $var = array($var);
        }
        return $var;
    }
}