<?php
// NanoCM
// Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

namespace Ubergeek\DatabaseUpdater;

/**
 * Implementiert die SQLite-spezifischen Funktionen für den DatabaseUpdater
 *
 * @package Ubergeek\DatabaseUpdater
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-06-27
 */
class SqliteDatabase implements DatabaseInterface {

    // <editor-fold desc="Properties">

    private $basePath;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct($basePath) {
        $this->basePath = $basePath;
    }

    // </editor-fold>


    // <editor-fold desc="DatabaseInterface">

    /**
     * @inheritDoc
     */
    public function createDatabaseConnection($databaseName) {
        $pdo = new \PDO(
            'sqlite:' . $this->createDatabaseFilename($databaseName)
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function createDatabaseFilename($relativeDatabaseName) {
        return $this->basePath . DIRECTORY_SEPARATOR . $relativeDatabaseName . '.sqlite';
    }

    // </editor-fold>
}