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
 * Der NullWriter protokolliert nichts, sondern kann verwendet werden, um eine
 * "leere" Logger-Instanz zu konfigurieren, damit loggende Klassen nicht bei
 * jedem Log-Aufruf überprüfen müssen, ob ein Logger vorhanden ist.
 * @author André Gewert <agewert@ubergeek.de>
 */
class NullWriter implements WriterInterface {
    
    public function close() {
    }

    public function flush() {
    }

    public function write(\Ubergeek\Log\Event $event) {
    }

    public function addFilter(\Ubergeek\Log\Filter\FilterInterface $filter) {
    }
}