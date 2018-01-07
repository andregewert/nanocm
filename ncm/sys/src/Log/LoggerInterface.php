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

    function emerg($data, \Exception $ex = null, array $backtrace = null, string $line = '');
    
    function alert($data, \Exception $ex = null, array $backtrace = null, string $line = '');
    
    function crit($data, \Exception $ex = null, array $backtrace = null, string $line = '');
    
    function err($data, \Exception $ex = null, array $backtrace = null, string $line = '');
    
    function warn($data, \Exception $ex = null, array $backtrace = null, string $line = '');
    
    function notice($data, \Exception $ex = null, array $backtrace = null, string $line = '');
    
    /**
     * Gibt eine Debug-Meldung an den Logger weiter
     * @param mixed $data Zu protokollierende Nachricht oder Daten
     * @param \Exception $ex Optionale Referenz auf die auslösende Exception
     * @param array $backtrace Backtrace, falls vorhanden
     * @param string $line Auslösende Codezeile
     */
    function debug($data, \Exception $ex = null, array $backtrace = null, string $line = '');
}