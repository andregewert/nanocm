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

namespace Ubergeek\Log\Writer;

/**
 * Beschreibt das Interface, das alle Writer-Implementierungen umsetzen müssen
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-10-29
 */
interface WriterInterface {
    /**
     * Schreibt das Ereignis
     * @param array $event Zu protokollierendes Ereignis
     */
    public function write(\Ubergeek\Log\Event $event);
    
    /**
     * "Flusht" den Ausgabe-Stream
     */
    public function flush();
    
    /**
     * Schließt den Ausgabe-Stream, falls notwendig / möglich
     */
    public function close();
    
    /**
     * Fügt dem Writer einen Filter hinzu
     * @param \Ubergeek\Log\Filter\FilterInterface $filter Hinzuzufügender Filter
     */
    public function addFilter(\Ubergeek\Log\Filter\FilterInterface $filter);
}