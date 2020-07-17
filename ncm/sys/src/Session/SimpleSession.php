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

namespace Ubergeek\Session;

/**
 * Simple Session-Verwaltung mit Unterstützung für Namespaces
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-16
 */
class SimpleSession implements SessionInterface {

    // <editor-fold desc="Internal properties">

    /**
     * @var string Name of the session
     */
    private $namespace;

    /**
     * @var string ID of the session
     */
    private $id;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * SimpleSession constructor.
     * @param string $namespace Namespace of the new session
     * @param string|null $name Name of the new session
     */
    public function __construct($namespace = 'default', $name = null) {
        if ($name != null) {
            session_name($name);
        }
        $this->namespace = $namespace;
    }

    // </editor-fold>


    // <editor-fold desc="SessionInterface">

    /**
     * @inheritDoc
     */
    public function getNamespace(): string {
        return $this->namespace;
    }

    /**
     * @inheritDoc
     */
    public function getSessionId(): string {
        return session_id();
    }

    /**
     * @inheritDoc
     */
    public function getSessionName(): string {
        return session_name();
    }

    /**
     * @inheritDoc
     */
    public function start() {
        session_start();
        $this->id = session_id();
    }

    /**
     * @inheritDoc
     */
    public function stop() {
        session_write_close();
    }

    /**
     * @inheritDoc
     */
    public function clear() {
        $_SESSION[$this->namespace] = array();
        session_write_close();
    }

    /**
     * @inheritDoc
     */
    public function getVar(string $key, $default = null) {
        $this->checkNamespace();
        if (!array_key_exists($key, $_SESSION[$this->namespace])) {
            return $default;
        }
        return $_SESSION[$this->namespace][$key];
    }

    /**
     * @inheritDoc
     */
    public function isVarExisting(string $key): bool {
        $this->checkNamespace();
        return array_key_exists($key, $_SESSION[$this->namespace]);
    }

    /**
     * @inheritDoc
     */
    public function getVars() {
        $this->checkNamespace();
        return $_SESSION[$this->namespace];
    }

    /**
     * @inheritDoc
     */
    public function setVar(string $key, $value) {
        $this->checkNamespace();
        $_SESSION[$this->namespace][$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function setVars(array $values) {
        $_SESSION[$this->namespace] = $values;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function checkNamespace() {
        if (!array_key_exists($this->namespace, $_SESSION) || !is_array($_SESSION[$this->namespace])) {
            $_SESSION[$this->namespace] = array();
        }
    }

    // </editor-fold>

}