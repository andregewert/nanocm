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

/**
 * Filtert Events nach Schweregrad bzw. Priorität
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-10-29
 */
class PriorityFilter implements FilterInterface {
    
    const OPERATOR_MIN = 'min';
    
    const OPERATOR_MAX = 'max';
    
    const OPERATOR_EQUAL = 'equal';

    private $priority;
    
    private $operator;
    
    public function __construct(int $priority, $operator = self::OPERATOR_MIN) {
        $this->priority = $priority;
        $this->operator = $operator;
    }
    
    public function filter(\Ubergeek\Log\Event $event): bool {
        if ($event == null) return false;
        if ($this->operator == self::OPERATOR_EQUAL && $event->priority == $this->priority) {
            return true;
        }
        elseif ($this->operator == self::OPERATOR_MAX && $event->priority >= $this->priority) {
            return true;
        }
        elseif ($this->operator == self::OPERATOR_MIN && $event->priority <= $this->priority) {
            return true;
        }
        return false;
    }

}