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

interface SessionInterface {

    /**
     * Starts a session
     * @return void
     */
    function start();

    /**
     * Closes the session
     * @return void
     */
    function stop();

    /**
     * Clears all saved data from the current session
     * @return void
     */
    function clear();

    /**
     * Returns the session's name
     * @return string Name of the session
     */
    function getSessionName() : string;

    /**
     * Returns the session id
     * @return string ID of the session
     */
    function getSessionId() : string;

    /**
     * Return the session's namespace
     * @return string Namespace of the current session
     */
    function getNamespace() : string;

    /**
     * Gets a value from the current session
     *
     * @param string $key Key of the requested variable
     * @param mixed|null $default Default value if variable is not existent
     * @return mixed Value of the requested variable or the given default value
     */
    function getVar(string $key, $default = null);

    /**
     * Checks if the given variable is saved in the current session
     *
     * @param string $key Name of the variable to be checked
     * @return bool true if the variable is existent
     */
    function isVarExisting(string $key) : bool;

    /**
     * Returns an array of all variables which are defined in the current session
     *
     * @return array
     */
    function getVars();

    /**
     * Saves the value of the named variable within the current session
     *
     * @param string $key Name of the variable
     * @param mixed $value The value to be saved
     * @return void
     */
    function setVar(string $key, $value);

    /**
     * Saves one or more values within in the current session
     *
     * @param array $values Array with key-value-pairs to be saved in the current session
     * @return void
     */
    function setVars(array $values);
    
}
