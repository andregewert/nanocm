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

/**
 * Eine simple Autoloader-Implementierung
 * @todo Konfigurierbares Mapping von Namespaces auf Verzeichnisse implementieren
 */
spl_autoload_register(function($class) {

    // TODO Der Autoloader sollte erweitert werden um konfigurierbare
    // Pfade mit entsprechenden Präfixes

    $filename = preg_replace('/^Ubergeek/', '', str_replace('\\', DIRECTORY_SEPARATOR, $class));
    $filename = __DIR__ . $filename . '.php';

    if (!file_exists($filename)) {
        throw new ErrorException('Class not found: ' . $class);
    }

    require $filename;
});

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");
