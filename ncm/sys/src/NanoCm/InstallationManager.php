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
 * Class InstallationManager
 *
 * This class implements update and backup tools for nanoCM.
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-07-31
 */
class InstallationManager {

    // <editor-fold desc="Internal properties">

    /**
     * @var NanoCm Reference to the current nanocm instance
     */
    public $ncm;

    /**
     * @var string URL for the atom feed listing available releases of nanoCM
     */
    public $updateFeed;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct(NanoCm $nanoCm) {
        $this->ncm = $nanoCm;
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Returns an array with information for every installed (available)
     * nano|cm template.
     *
     * @return TemplateInfo[]
     */
    public function getAvailableTemplates() {
        $templates = array();
        $dir = Util::createPath($this->ncm->pubdir, 'tpl');
        $dh = opendir($dir);

        if ($dh !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname !== '.' && $fname !== '..') {
                    $dirname = $dir . DIRECTORY_SEPARATOR . $fname;
                    if (is_dir($dirname)) {
                        $info = $this->readTemplateInformation($fname);
                        if ($info !== null) $templates[$fname] = $info;
                    }
                }
            }
        }

        uasort($templates, function($a, $b) {
            return strnatcasecmp($a->title, $b->title);
        });

        return $templates;
    }

    public function getAvailableVersionsFromServer() {
        // TODO implementieren
    }

    public function getAvailableBackups() {
        // TODO implementieren
    }

    public function createBackup() {
        // TODO implementieren
    }

    public function deleteBackup(string $relativeFilename) : void {
        // TODO implementieren
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function readTemplateInformation($tplDir) {
        $versionFile = Util::createPath($this->ncm->pubdir, 'tpl', $tplDir, 'META-INF', 'version.json');
        if (!file_exists($versionFile)) return null;
        $infoArray = json_decode(file_get_contents($versionFile), true);
        $infoArray['dirname'] = $tplDir;
        if (is_array($infoArray)) return new TemplateInfo($infoArray);
        return null;
    }

    // </editor-fold>

}