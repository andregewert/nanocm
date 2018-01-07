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

abstract class AbstractWriter implements WriterInterface {
    
    /**
     * Enthält die anzuwendenden Filter
     * @var array
     */
    private $filters;
    
    /**
     * Dem Konstruktor können ein Filter oder ein Array mit mehreren Filtern
     * übergeben werden
     * @param \Ubergeek\Log\Filter\FilterInterface|mixed $filters
     */
    public function __construct($filters = null) {
        if (is_array($filters)) {
            $this->filters = $filters;
        } elseif ($filters instanceof \Ubergeek\Log\Filter\FilterInterface) {
            $this->filters = array($filters);
        }
    }
    
    public final function addFilter(\Ubergeek\Log\Filter\FilterInterface $filter) {
        if (!is_array($this->filters)) {
            $this->filters = array();
        }
        $this->filters[] = $filter;
    }

    public function close() {
    }

    public function flush() {
    }

    /**
     * Erweitert die write-Methode um die Anwendung der konfigurierten Filter.
     * Nur wenn das übergebene Event von allen Filtern akzeptiert wird, wird
     * es an die doWrite()-Methode weitergereicht.
     * @param \Ubergeek\Log\Event $event Das zu protokollierende Event
     */
    public final function write(\Ubergeek\Log\Event $event) {
        $accepted = true;
        
        if (is_array($this->filters)) {
            foreach ($this->filters as $filter) {
                if (!$filter->filter($event)) {
                    $accepted = false;
                    break;
                }
            }
        }
        
        if ($accepted) {
            $this->doWrite($event);
        }
    }

    abstract function doWrite(\Ubergeek\Log\Event $event);
}
