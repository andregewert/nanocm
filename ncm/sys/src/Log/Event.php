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

class Event {
    /**
     * Log-Level
     * @var integer 
     */
    public $level;
    
    /**
     * Log-Nachricht (beliebige Daten!)
     * @var mixed
     */
    public $message;
    
    /**
     * Auslösende Exception, falls vorhanden
     * @var \Exception
     */
    public $exception;
    
    /**
     * Stack-Trace, falls vorhanden
     * @var array
     */
    public $backtrace;
    
    /**
     * Informationen zur auslösenden Zeile, falls vorhanden
     * @var string
     */
    public $line;
    
    /**
     * Schweregrad / Priorität des Events
     * @var int
     */
    public $priority = 0;
    
    /**
     * Erzeugt ein neues zu protokollierendes Ereignis
     * @param int $level Log-Level
     * @param mixed $message Nachricht oder Daten
     * @param \Exception $exception Auslösende Exception, falls vorhanden
     * @param array $backtrace Stack-Trace, falls vorhanden
     * @param string $line Auslösende Zeile, falls vorhanden
     */
    public function __construct(int $level, $message, \Exception $exception = null,
            array $backtrace = null, string $line = "") {
        $this->level = $level;
        $this->message = $message;
        $this->exception = $exception;
        $this->backtrace = $backtrace;
        $this->line = $line;
    }

    public function toString() {
        $string = "$this->line: [$this->level] $this->message\n";
        return $string;
    }
}