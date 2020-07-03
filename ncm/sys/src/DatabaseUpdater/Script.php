<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\DatabaseUpdater;
use Ubergeek\DatabaseUpdater\Exception;

/**
 * Kapselt Inhalt und Metadaten zu einem Create- oder Update-SQL-Script
 * @package Ubergeek\DatabaseUpdater
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-06-27
 */
class Script {

    // <editor-fold desc="Properties">

    /**
     * Der ursprüngliche Dateiname (ohne Pfadangabe)
     * @var string
     */
    public $filename;

    /**
     * Versionsnummer
     * @var integer
     */
    public $version;

    /**
     * Typ: create oder update
     * @var string
     */
    public $type;

    /**
     * Name der zu modifizierenden Datenbank
     * @var string
     */
    public $databaseName;

    /**
     * Der Inhalt des Scripts
     * @var string
     */
    public $contents;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct($absoluteFilePath) {
        if (!file_exists($absoluteFilePath)) {
            throw new Exception\FileNotFoundException('File not found: ' . $absoluteFilePath);
        }

        $basename = basename($absoluteFilePath);
        if (preg_match('/^(\d+)\-(create|update)\-(.+)\.sql$/i', $basename, $matches) === false) {
            throw new Exception\InvalidFileException('Invalid script file: ' . $basename);
        }

        $this->filename = $basename;
        $this->version = (int)$matches[1];
        $this->type = $matches[2];
        $this->databaseName = $matches[3];
        $this->contents = file_get_contents($absoluteFilePath);
    }

    // </editor-fold>

}