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

namespace Ubergeek\Controller;
use \Ubergeek;

abstract class HttpController implements ControllerInterface {
    
    /**
     * @var array Ein Array mit den zu sendenden HTTP-Headern; abgebildet als
     * KeyValuePair
     */
    protected $headers = array();

    /**
     * @var array Enthält die generierten Inhalte
     */
    protected $contents = array();
    
    /**
     * @var array Enthält benutzerdefinierte Variablen
     */
    protected $vars = array();

    // ...
    public function addContent(string $content, string $area = 'default'): string {
        
    }

    public function addMeta(string $key, string $value) {
        
    }
    
    public function getMetaData() : array {
        return $this->headers;
    }
    
    public function getMeta(string $key) : array {
        $dummy = array();
        
        if (is_array($this->headers)) {
            foreach ($this->headers as $header) {
                if ($header instanceof \Ubergeek\KeyValuePair && $header->Key == $key) {
                    $dummy[] = $header;
                }
            }
        }
        
        return $dummy;
    }

    public function getContent(string $area = 'default'): string {
        if (!is_array($this->contents)) return "";
        if (!array_key_exists($area, $this->contents)) return "";
        return $this->contents[$area];
    }

    public function getParam(string $key, $default = null): any {
        if (!is_array($_REQUEST) || !array_key_exists($key, $_REQUEST)) {
            return $default;
        }
        return $_REQUEST[$key];
    }

    public function getParams(): array {
        return $_REQUEST;
    }

    public function getVar(string $key, $default = null): any {
        if (!is_array($this->vars)) {
            return $default;
        }
        if (!array_key_exists($this->vars, $key)) {
            return $default;
        }
        return $this->vars[$key];
    }

    public function getVars(): array {
        return $this->vars;
    }

    public function replaceMeta(string $key, string $value) {
        
    }

    public function setContent(string $content, string $area = 'default') {
        if (!is_array($this->contents)) {
            $this->contents = array();
        }
        
        $this->contents[$area] = $content;
    }

    public function setParam(string $key, any $value) {
        
    }

    public function setVar(string $key, any $value) {
        
    }

    public function init() {
        // Initialisierungsaufgaben durchführen
        // In der Basis-Implementierung passiert hier nicht viel
    }

    public final function execute() {
        $this->init();
        $this->run();
        
        // Header ausgeben
        $headers = $this->getMetaData();
        if (is_array($headers)) {
            foreach ($headers as $header) {
                header($header->Key . ': ' . $header->Value);
            }
        }
        
        // Content ausgeben
        echo $this->getContent();
    }
}