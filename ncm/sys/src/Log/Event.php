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
    // <editor-fold desc="Public fields">

    /** @var integer Log-Level */
    public $level;
    
    /** @var string Log-Nachricht */
    public $message;
    
    /** @var \Exception Auslösende Exception, falls vorhanden */
    public $exception;
    
    /** @var array Stack-Trace, falls vorhanden */
    public $backtrace;
    
    /** @var string Informationen zur auslösenden Zeile, falls vorhanden */
    public $line;
    
    // </editor-fold>
    
    
    // <editor-fold desc="Konstruktoren">

    /**
     * Erzeugt ein neues zu protokollierendes Ereignis
     * @param int $level Log-Level
     * @param string $message Nachricht
     * @param \Exception $exception Auslösende Exception, falls vorhanden
     * @param array $backtrace Stack-Trace, falls vorhanden
     * @param string $line Auslösende Zeile, falls vorhanden
     */
    public function __construct(int $level, string $message, \Exception $exception = null,
            array $backtrace = null, string $line = "") {
        $this->level = $level;
        $this->message = $message;
        $this->exception = $exception;
        $this->backtrace = $backtrace;
        $this->line = $line;
    }
    
    // </editor-fold>
}