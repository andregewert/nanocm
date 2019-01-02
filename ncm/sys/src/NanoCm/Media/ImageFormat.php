<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Ubergeek\NanoCm\Media;

/**
 * Bildet die Definition für ein Bildformat ab
 *
 * @package Ubergeek\NanoCm\Media
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-04
 */
class ImageFormat
{
    // <editor-fold desc="Properties">

    /**
     * @var string Key des Bildformates
     */
    public $key;

    /**
     * @var string Titel für dieses Bildformat
     */
    public $title;

    /**
     * @var string Ausführliche Beschreibung für dieses Bildformat
     */
    public $description;

    /**
     * Gibt die Breite für dieses Bildformat an.
     * Ein Wert von 0 bedeutet, dass der Wert für diese Seite automatisch berechnet werden soll. Besitzen beide Seiten
     * einen Wert von 0, so wird für dieses Bildformat die ursprüngliche Bildgröße verwendet.
     *
     * @var int
     */
    public $width;

    /**
     * Gibt die Höhe für dieses Bildformat an.
     * Ein Wert von 0 bedeutet, dass der Wert für diese Seite automatisch berechnet werden soll. Besitzen beide Seiten
     * einen Wert von 0, so wird für dieses Bildformat die ursprüngliche Bildgröße verwendet.
     *
     * @var int
     */
    public $height;

    // </editor-fold>


    // <editor-fold desc="Method">

    /**
     * Liest das Abfrageergebnis am aktuellen Cursor aus und erstellt daraus ein ImageFormat-Objekt
     *
     * @param \PDOStatement $stmt
     * @return null|ImageFormat
     */
    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        /* @var $imageFormat \Ubergeek\NanoCm\Media\ImageFormat */
        if (($imageFormat = $stmt->fetchObject(__CLASS__)) !== false) {
            return $imageFormat;
        }
        return null;
    }

    // </editor-fold>
}