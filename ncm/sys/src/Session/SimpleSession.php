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

namespace Ubergeek\Session;

/**
 * Simple Session-Verwaltung mit Unterstützung für Namespaces
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-16
 */
class SimpleSession implements SessionInterface {

    private $namespace;
    
    public function __construct($namespace = 'default', $name = null) {
        if ($name != null) {
            session_name($name);
        }
        $this->namespace = $namespace;
    }
    
    public function getNamespace(): string {
        return $this->namespace;
    }

    public function getSessionId(): string {
        return session_id();
    }

    public function getSessionName(): string {
        return session_name();
    }

    public function start() {
        session_start();
        $this->id = session_id();
    }

    public function stop() {
        session_write_close();
    }
    
    public function clear() {
        $_SESSION[$this->namespace] = array();
        session_commit();
    }

    public function getVar(string $key, $default = null) {
        $this->checkNamespace();
        if (!array_key_exists($key, $_SESSION[$this->namespace])) {
            return $default;
        }
        return $_SESSION[$this->namespace][$key];
    }

    public function getVars() {
        $this->checkNamespace();
        return $_SESSION[$this->namespace];
    }

    public function setVar(string $key, $value) {
        $this->checkNamespace();
        $_SESSION[$this->namespace][$key] = $value;
    }

    public function setVars(array $values) {
        $_SESSION[$this->namespace] = $values;
    }
    
    private function checkNamespace() {
        if (!array_key_exists($this->namespace, $_SESSION) || !is_array($_SESSION[$this->namespace])) {
            $_SESSION[$this->namespace] = array();
        }
    }

}