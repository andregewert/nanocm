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

namespace Ubergeek\Log\Filter;
use Ubergeek\Log\Event;

/**
 * Interface für Event-Filter.
 * Die LogWriter können mit beliebigen Filter-Instanzen konfiguriert werden,
 * die dafür sorgen, dass nur bestimmte Events tatsächlich geschrieben werden.
 * Die einfachste Filterung besteht in der Überprüfung des Log-Levels, also
 * des Schweregrades des zu protokollierenden Events.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-10-29
 */
interface FilterInterface {

    /**
     * Filtert das übergebene Logging-Event.
     * Soll das Event protokolliert werden, gibt die Methode true zurück. Wenn
     * die Methode false zurück gibt, soll das Event vom Writer ignoriert
     * werden.
     * @param Event $event
     * @return bool true, wenn das Event protokolliert werden soll
     */
    public function filter(Event $event) : bool;

}