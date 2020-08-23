<?php
/*
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

namespace Ubergeek\NanoCm\Util;

/**
 * Represents an directory entry
 * @package Ubergeek\NanoCm\Util
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-08-23
 */
class DirectoryEntry {

    // <editor-fold desc="Constants">

    /**
     * Entry type file
     * @var int
     */
    public const TYPE_FILE = 0;

    /**
     * Entry type directory
     * @var int
     */
    public const TYPE_DIR = 1;

    // </editor-fold>


    // <editor-fold desc="Properties">

    /**
     * Absolute path of the directory entry
     * @var string
     */
    public $absolutePath;

    /**
     * Relative path of the directory entry; depends
     * on how the directory was read.
     * @var string
     */
    public $relativePath;

    /**
     * Type of directory entry
     * @var int
     */
    public $entryType;

    // </editor-fold>

}