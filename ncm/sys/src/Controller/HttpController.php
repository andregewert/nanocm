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

namespace Ubergeek\Controller;

/**
 * Abstrakte Basisklasse für HTTP-Controller
 * @todo Nutzung der Klassen HttpRequest und HttpResponse!
 */
abstract class HttpController implements ControllerInterface {
    
    // <editor-fold desc="Properties">
    
    /**
     * Ein Array mit den zu sendenden HTTP-Headern; abgebildet als KeyValuePair
     * @var array
     */
    protected $headers = array();

    /**
     * Enthält die generierten Inhalte
     * @var array
     */
    protected $contents = array();
    
    /**
     * Enthält benutzerdefinierte Variablen
     * @var array
     */
    protected $vars = array();
    
    /**
     * Seitentitel
     * @var string
     */
    protected $title = "Unknown page";
    
    /**
     * HTTP-Anfrage
     * @var HttpRequest
     */
    protected $request;
    
    /**
     * HTTP-Antwort
     * @var HttpResponse
     */
    protected $response;
    
    // </editor-fold>


    /**
     * HttpController constructor.
     */
    public function __construct() {
        $this->request = new HttpRequest();
        $this->response = new HttpResponse();
    }
    
    /**
     * Gibt den mit dieser HttpController-Instanz verbundenen HttpRequest zurück
     * @return HttpRequest
     */
    public function getHttpRequest() {
        return $this->request;
    }
    
    /**
     * Gibt die mit dieser HttpController-Instanz verbundene HttpResponse zurück
     * @return HttpResponse
     */
    public function getHttpResponse() {
        return $this->response;
    }

    /**
     * Fügt einem Inhaltsbereich den übergebenen Content hinzu
     * @param string $content Hinzuzufügender Inhalte
     * @param string $area Name des Inhaltsbereichs
     * @return string
     */
    public function addContent(string $content, string $area = 'default') : string {
        if (!is_array($this->contents)) {
            $this->contents = array();
        }
        if (!array_key_exists($area, $this->contents)) {
            $this->contents[$area] = '';
        }
        $this->contents[$area] .= $content;
        return $this->contents[$area];
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
     * @return mixed Wert des Parameters
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

    /**
     * Gibt true zurück, wenn der angegebene Schlüssel in den aktuellen HTTP-Parametern enthalten ist
     * @param string $key
     * @return bool
     */
    public function isParamExisting(string $key): bool {
        return is_array($_REQUEST) && array_key_exists($key, $_REQUEST);
    }

    /**
     * Gibt den Wert einer bestimmten Variablen zurück.
     * Ist die Variable nicht gesetzt, wird der angegebene Default-Wert zurück
     * gegeben.
     * @param string $key
     * @param mixed $default Vorgabewert, falls die Variable nicht gesetzt ist
     * @return mixed
     */
    public function getVar(string $key, $default = null) {
        if (!is_array($this->vars) || !array_key_exists($key, $this->vars)) {
            return $default;
        }
        return $this->vars[$key];
    }

    /**
     * Gibt ein Array mit allen definierten Variablen zurück
     * @return array
     */
    public function getVars(): array {
        return $this->vars;
    }

    /**
     * Gibt true zurück, wenn der angegebene Schlüssel in den Controller-Variablen definiert ist
     * @param string $key
     * @return bool
     */
    public function isVarExisting(string $key): bool {
        return is_array($this->vars) && array_key_exists($key, $this->vars);
    }

    /**
     * @param string $key
     * @param string $value
     */
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
        $dummy[] = new \Ubergeek\KeyValuePair($key, $value);
        $this->headers = $dummy;
    }

    /**
     * @param string $content
     * @param string $area
     */
    public function setContent(string $content, string $area = 'default') {
        if (!is_array($this->contents)) {
            $this->contents = array();
        }
        
        $this->contents[$area] = $content;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setParam(string $key, $value) {
        if (!is_array($_REQUEST)) {
            $_REQUEST = array();
        }
        $_REQUEST[$key] = $value;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setVar(string $key, $value) {
        if (!is_array($this->vars)) {
            $this->vars = array();
        }
        $this->vars[$key] = $value;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle() : string {
        return $this->title;
    }

    /**
     * Kann überschrieben werden, um in konkreten Implementierungen
     * Initialisierungsaufgaben vor dem eigentlichen Programm-Ablauf
     * durchzuführen
     */
    public function init() {
        // Initialisierungsaufgaben durchführen
        // Die Basis-Implementierung ist leer
    }

    /**
     * Führt den Controller bzw. das eigentliche Programm aus.
     * 
     * Die Methode führt die eigentliche Anwendung aus. Dazu gehört, den
     * aktuellen Request korrekt zu interpretieren und ein entsprechendes
     * Ergebnis zusammen zu stellen.
     */
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
    }
}