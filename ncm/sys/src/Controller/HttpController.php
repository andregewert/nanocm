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
    
    /**
     * @var string Seitentitel
     */
    protected $title = "Unknown page";

    /**
     * Fügt einem Inhaltsbereich den übergebenen Content hinzu
     * @param string $content Hinzuzufügender Inhalte
     * @param string $area Name des Inhaltsbereichs
     */
    public function addContent(string $content, string $area = 'default'): string {
        if (!is_array($this->contents)) {
            $this->contents = array();
        }
        if (!array_key_exists($area, $this->contents)) {
            $this->contents[$area] = '';
        }
        $this->contents[$area] .= $content;
    }

    /**
     * Fügt der Ausgabe den angegebenen Meta-Wert hinzu
     * @param string $key Schlüssel
     * @param string $value Wert
     */
    public function addMeta(string $key, string $value) {
        if (!is_array($this->headers)) {
            $this->headers = array();
        }
        array_push($this->headers, new \Ubergeek\KeyValuePair($key, $value));
    }
    
    /**
     * Gibt alle bis zum Zeitpunkt des Aufrufs generierten Metadaten zurück
     * @return array Metadaten
     */
    public function getMetaData() : array {
        return $this->headers;
    }
    
    /**
     * Gibt alle bis zum Zeitpunkt für einen bestimmten Schlüssel generierten
     * Metadaten zurück
     * @param string $key Schlüssel
     * @return array Metadaten
     */
    public function getMeta(string $key) : array {
        $dummy = array();
        
        if (is_array($this->headers)) {
            foreach ($this->headers as $header) {
                if ($header instanceof \Ubergeek\KeyValuePair && $header->key == $key) {
                    $dummy[] = $header;
                }
            }
        }
        
        return $dummy;
    }

    /**
     * Gibt den bisher generierten Inhalt für einen bestimmten Inhaltsbereich
     * zurück
     * @param string $area Name des Inhaltsbereichs
     * @return string Generierter Inhalt
     */
    public function getContent(string $area = 'default'): string {
        if (!is_array($this->contents)) return "";
        if (!array_key_exists($area, $this->contents)) return "";
        return $this->contents[$area];
    }

    /**
     * Gibt den Wert eines externen Parameters zurück
     * @param string $key Parameter-Name
     * @param mixed $default Vorgabewert, falls der Parameter nicht übergeben wurde
     * @return Wert des Parameters
     */
    public function getParam(string $key, $default = null) {
        if (!is_array($_REQUEST) || !array_key_exists($key, $_REQUEST)) {
            return $default;
        }
        return $_REQUEST[$key];
    }

    /**
     * Gibt ein Array aller an den Controller übergebenen Parameter zurück
     * @return array Alle Parameter
     */
    public function getParams(): array {
        return $_REQUEST;
    }

    public function getVar(string $key, $default = null) {
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
        if (!is_array($this->headers)) {
            $this->headers = array();
        }
        
        $dummy = array();
        foreach ($this->headers as $header) {
            if ($header instanceof \Ubergeek\KeyValuePair && $header->key != $key) {
                $dummy[] = $header;
            }
        }
        $dummy[$key] = $value;
        $this->headers = $dummy;
    }

    public function setContent(string $content, string $area = 'default') {
        if (!is_array($this->contents)) {
            $this->contents = array();
        }
        
        $this->contents[$area] = $content;
    }

    public function setParam(string $key, $value) {
        if (!is_array($_REQUEST)) {
            $_REQUEST = array();
        }
        $_REQUEST[$key] = $value;
    }

    public function setVar(string $key, $value) {
        if (!is_array($this->vars)) {
            $this->vars = array();
        }
        $this->vars[$key] = $value;
    }
    
    public function setTitle(string $title) {
        $this->title = $title;
    }
    
    public function getTitle() : string {
        return $this->title;
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
                header($header->key . ': ' . $header->value);
            }
        }
        
        // Content ausgeben -> in Output buffer
        echo $this->getContent();
        
        // Gepufferten Inhalt in Frame-Template ausgeben
        // ...
    }
}