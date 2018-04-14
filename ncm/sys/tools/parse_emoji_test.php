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
 * die für das Emoji-Virtual-Keyboard verwendet wird.
 *
 * Eine Blacklist der nicht auszuwertenden Emojis wird nicht an dieser Stelle
 * implementiert, da der Betreuer einer konkreten Installation auch ohne Update
 * der Definitionsdatei in der Lage sein soll, das Emoji-Keyboard anzupassen.
 *
 * Auf diese Weise ist es möglich, bei einem Update des Unicode-Standards das
 * passende Test-File unverändert zu parsen, damit die Emoji-Definitionsdatei
 * zu aktualisieren und dennoch eine individuelle Blacklist beizubehalten.
 */

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
                    } elseif (preg_match('/^(.+)\;(.+)\#\s+\S+\s+(.*)$/', $line, $matches) != 0) {

                        $codes = trim($matches[1]);
                        $fq = trim($matches[2]) == 'fully-qualified';
                        $desc = trim($matches[3]);

                        if ($fq && $group !== null) {
                            if (!in_array($codes, array_keys($result[$group]))) {
                                $chars = explode(' ', $codes);
                                $html = '&#x' . join(';&#x', $chars) . ';';
                                $tone = '';

                                if (in_array('1F3FB', $chars)) {
                                    $tone = 'light';
                                } elseif (in_array('1F3FC', $chars)) {
                                    $tone = 'mediumlight';
                                } elseif (in_array('1F3FD', $chars)) {
                                    $tone = 'medium';
                                } elseif (in_array('1F3FE', $chars)) {
                                    $tone = 'mediumdark';
                                } elseif (in_array('1F3FF', $chars)) {
                                    $tone = 'dark';
                                }

                                $result[$group][$codes] = array(
                                    'code'              => $codes,
                                    'html'              => $html,
                                    'description'       => $desc,
                                    'tone'              => $tone
                                );
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
