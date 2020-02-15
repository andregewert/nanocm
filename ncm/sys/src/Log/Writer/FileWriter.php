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

use Ubergeek\Log\Event;

/**
 * Protokolliert die übergebenen Events in eine Log-Datei
 * @author André Gewert <agewert@ubergeek.de>
 */
class FileWriter extends AbstractWriter {

    /**
     * Pfad zur Logdatei
     * @var string
     */
    private $filename;

    /**
     * Datei-Handle für die Logdatei
     * @var false|resource
     */
    private $fileHandle;

    // <editor-fold desc="Constructor">

    public function __construct($filename, $filters = null) {
        parent::__construct($filters);
        $this->filename = $filename;
        $this->fileHandle = fopen($this->filename, 'a+');
    }

    // </editor-fold>


    // <editor-fold desc="WriterInterface">

    /**
     * Schließt durch den Writer geöffnete Ressourcen wieder
     */
    public function close() {
        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
        }
    }

    /**
     * Leert den Ausgabepuffer der Logdatei
     */
    public function flush() {
        if (is_resource($this->fileHandle)) {
            flush($this->fileHandle);
        }
    }

    /**
     * Schreibt ein Ereignis in die Logdatei
     * @param Event $event Das zu protokollierende Ereignis
     */
    public function doWrite(Event $event) {
        if (!is_resource($this->fileHandle)) {
            $this->fileHandle = fopen($this->filename, 'a+');
        }
        fwrite($this->fileHandle, $event->toString());
    }

    // </editor-fold>

}