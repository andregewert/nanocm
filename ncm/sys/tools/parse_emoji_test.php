#! /usr/bin/php
<?php
/**
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/**
 * Soll die Emoji-Testdatei parsen, um daraus eine PHP-Datenstruktur zu erstellen,
 * die für das Emoji-Virtual-Keyboard verwendet wird
 */

// TODO Black List implementieren
// TODO Eventuell kann man auch die Beschreibung noch übernehmen und als Tooltip verwenden?

class ParseEmojiTestFile {
    public function parseFile(string $filename) : array {
        $result = array();
        if (($fh = fopen($filename, 'r')) !== false) {
            $group = null;
            while (!feof($fh) && ($line = fgets($fh, 4096)) !== false) {
                $line = trim($line);
                if (!empty(trim($line))) {
                    if (preg_match('/^\# group\: (.+)$/i', $line, $matches) != 0) {
                        $group = $matches[1];
                        $result[$group] = array();
                    } elseif (preg_match('/^(.+)\;(.+)\#.*$/', $line, $matches) != 0) {
                        $codes = explode(' ', trim($matches[1]));
                        $fq = trim($matches[2]) == 'fully-qualified';

                        if ($fq && $group !== null) {
                            $code = array_pop($codes);
                            if (!in_array($code, $result[$group])) {
                                $result[$group][] = $code;
                            }
                        }
                    }
                }
            }
            fclose($fh);
        }
        return $result;
    }
}

$parser = new ParseEmojiTestFile();
$result = $parser->parseFile('emoji-test.txt');
echo json_encode($result);
