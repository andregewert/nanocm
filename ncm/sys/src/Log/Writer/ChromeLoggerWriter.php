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

use Ubergeek\Log\ChromeLogger;
use Ubergeek\Log\Event;

/**
 * Nutzt das ChromeLogger-Protokoll, um die Debug-Ausgabe per HTTP-Header an
 * den Web-Browser zu übergeben
 * @author André Gewert <agewert@ubergeek.de>
 */
class ChromeLoggerWriter extends AbstractWriter {
    
    private $chromeLogger;
    
    public function __construct($filters = null) {
        parent::__construct($filters);
        $this->chromeLogger = new ChromeLogger();
    }

    public function flush() {
        header(ChromeLogger::HEADER_NAME . ': ' . $this->chromeLogger->createOutputString());
    }

    public function doWrite(Event $event) {
        $this->chromeLogger->log($event->message, 'log');
    }

}