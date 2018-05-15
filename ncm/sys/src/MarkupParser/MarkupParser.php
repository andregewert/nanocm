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

namespace Ubergeek\MarkupParser;

/**
 * Stellt einen Parser dar für Plaintext-Eingaben, die mit einfachem Formatierungs-Markup versehen sind.
 * Die Syntax ist angelehnt an die Markdown-Syntax, realisiert einige Dinge jedoch etwas anders.
 * @author agewert@ubergeek.de
 * @package Ubergeek\MarkupParser
 * @created 2018-05-15
 */
class MarkupParser {

    // <editor-fold desc="Public methods">

    public function parse(string $input) : string {
        $output = "";

        // Basis-Formatierung
        $input = trim($input);
        $input = str_replace(array("\r\n", "\r"), "\n", $input);

        // HTML-Sonderzeichen kodieren
        $input = htmlspecialchars($input, ENT_HTML5 | ENT_COMPAT);

        // Einzelne Blöcke auftrennen und parsen
        $blocks = $this->splitBlocks($input);
        foreach ($blocks as $block) {
            $output .= $this->parseBlock($block);

            // Innerhalb der Blöcke Inline-Markup ersetzen
            // ...
        }

        // Nicht markierte Links etc. ersetzen
        // ...

        return $output;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    protected function splitBlocks(string $input) : array {
        $blocks = preg_split('/\n\n+/is', $input);
        //var_dump($blocks);
        return $blocks;
    }

    protected function parseBlock(string $input) : string {
        // Überschriften
        if (preg_match('/^(#{1,6})\s*(.*)$/', $input, $matches) === 1) {
            $level = strlen($matches[1]);
            $output = "<h$level>" . htmlentities($matches[2]) . "</h$level>\n";
        }

        // Einfache Listen
        elseif (preg_match('/^\s*(\-|#)/i', $input, $matches) === 1) {
            $output = "<ul>\n";
            foreach (preg_split('/^\s*(\-|#)\s*/ims', $input) as $item) {
                if (mb_strlen(trim($item)) > 0) {
                    $output .= '<li>' . $this->parseInlineElements($item) . "</li>\n";
                }
            }
            $output .= "</ul>\n";
        }

        // Text-Absätze
        else {
            $output = '<p>' . $this->parseInlineElements($input) . "</p>\n";
        }

        return $output;
    }

    protected function parseInlineElements(string $input) : string {
        $input = preg_replace('/\*\*(.+?)\*\*/i', "<strong>$1</strong>", $input);
        $input = preg_replace('/\*(.+?)\*/i', "<em>$1</em>", $input);
        $input = preg_replace('/\_(.+?)\_/i', "<u>$1</u>", $input);
        $input = preg_replace('/  $/ims', "<br>", $input);
        $input = $this->parseInlineLinks($input);

        // TODO Bestimmte typografische Zeichen ersetzen
        // \w\s-\s\w -> geschützte Leerzeichen und Dash

        $input = preg_replace('/(\w)\s(-){1,2}\s(\w)/', "$1&nbsp;&ndash; $3", $input);
        $input = preg_replace('/(\w)\s\/\s(\w)/', "$1&nbsp;/ $2", $input);

        return $input;
    }

    protected function parseInlineLinks(string $input) : string {
        // TODO implementieren
        return $input;
    }

    // </editor-fold>
}
