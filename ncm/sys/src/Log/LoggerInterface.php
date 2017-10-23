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
 * Bildet das Log-Interface ab, gegen das die Klassen programmiert werden sollen
 */
interface LoggerInterface {
    /**
     * Gibt eine Debug-Meldung an den Logger weiter
     * @param string $msg
     * @param \Exception $ex Optionale Referenz auf die auslösende Exception
     * @param array $backtrace
     * @param string $line
     */
    function debug(string $msg, \Exception $ex = null, array $backtrace = null, string $line = '');
}