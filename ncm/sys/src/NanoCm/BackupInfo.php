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

namespace Ubergeek\NanoCm;

/**
 * Class BackupInfo
 *
 * Represents some metadata for a singe existing backup / snapshot of
 * one nanoCM installation.
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-07-31
 */
class BackupInfo {

    // <editor-fold desc="Properties">

    /**
     * @var DateTimte Creation time of this backup
     */
    public $creationDateTime;

    /**
     * @var string Version string for the included nanoCM installation
     */
    public $version;

    /**
     * @var int Size in bytes of this backup
     */
    public $filesize;

    /**
     * @var string Absolute path to the backup archive
     */
    public $filename;

    /**
     * @var array Includes the installation information from ncm/sys/version.info in an array
     */
    public $installationInfo;

    // </editor-fold>

}