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

use DateTime;
use DirectoryIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

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
     * Reference to the current nanocm instance
     * @var NanoCm
     */
    public $ncm;

    /**
     * URL for the atom feed listing available releases of nanoCM
     * @var string
     */
    public $updateFeed;

    /**
     * Absolute path to the backup directory
     * @var string
     */
    public $backupPath;

    /**
     * Absolute path to the user template directory
     * @var string
     */
    public $templatePath;

    /**
     * Absolute path to the installation base
     * @var string
     */
    public $installationBasePath;

    private $backupDirs = array(
        'ncm/sys/db'
    );

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct(NanoCm $nanoCm) {
        $this->ncm = $nanoCm;
        $this->installationBasePath = $this->ncm->pubdir;
        $this->backupPath = Util::createPath($this->ncm->sysdir, 'backup');
        $this->templatePath = Util::createPath($this->ncm->pubdir, 'tpl');
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Returns an array with information for every installed (available)
     * nano|cm template.
     * @return TemplateInfo[]
     */
    public function getAvailableTemplates(): array {
        $templates = array();
        $dh = opendir($this->templatePath);

        if ($dh !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname !== '.' && $fname !== '..') {
                    $dirname = $this->templatePath . DIRECTORY_SEPARATOR . $fname;
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

    /**
     * Returns an array with information for existing backups
     * @param bool $countOnly Set to true if method should return number of entries only
     * @param int|null $page Number of page to return
     * @param int|null $limit Maximum number if entries to return
     * @return BackupInfo[]|int Array of backup information or number of found backups
     */
    public function getAvailableBackups($countOnly = false, $page = null, $limit = null) {

        /* @var $backups BackupInfo[] */
        $backups = array();
        $dh = opendir($this->backupPath);
        $limit = ($limit === null)? $this->ncm->orm->pageLength : (int)$limit;

        if ($dh !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname !== '.' && $fname !== '..') {
                    if (preg_match('/^backup-(.+)\.zip$/i', $fname) > 0) {
                        $absname = $this->backupPath . DIRECTORY_SEPARATOR . $fname;
                        $backupInfo = new BackupInfo();
                        $backupInfo->filename = $absname;
                        $backupInfo->creationDateTime = new DateTime();
                        $backupInfo->creationDateTime->setTimestamp(filectime($absname));
                        $backupInfo->filesize = filesize($absname);
                        $backupInfo->version = 'unknown';
                        $backups[$absname] = $backupInfo;
                    }
                }
            }
        }

        uasort($backups,
            /**
             * @param BackupInfo $a
             * @param BackupInfo $b
             * @return int
             */
            function($a, $b) {
                $r = 0;
                if ($a->creationDateTime instanceof DateTime
                    && $b->creationDateTime instanceof DateTime) {
                    if ($a->creationDateTime != $b->creationDateTime) {
                        $r = ($a->creationDateTime < $b->creationDateTime)? 1 : 0;
                    }
                }
                return $r;
            }
        );

        if (!$countOnly) {
            $page = (int)$page -1;
            if ($page < 0) $page = 0;
            $offset = $page *$limit;
            $backups = array_slice($backups, $offset, $limit, true);
        }

        if ($countOnly) return count($backups);

        foreach ($backups as $backup) {
            if (($info = $this->readInstallationInformationFromBackup($backup)) !== null) {
                $backup->installationInfo = $info;
                if (array_key_exists('version', $info)) $backup->version = $info['version'];
            }
        }

        return $backups;
    }

    /**
     * Creates a backup of the current installation
     * @param null $filename Optional filename
     * @return BackupInfo|null Information for the created backup or null in case of an error
     */
    public function createBackup($filename = null) : ?BackupInfo {
        $now = new DateTime();
        if ($filename === null) {
            $filename = 'backup-' . $now->format('Y-m-d H:i:s') . '.zip';
        }
        $filename = basename($filename);

        $backupInfo = new BackupInfo();
        $backupInfo->creationDateTime = $now;
        $backupInfo->installationInfo = $this->ncm->versionInfo;
        $backupInfo->version = $this->ncm->versionInfo->version;
        $backupInfo->filename = $filename;

        $zipFile = Util::createPath($this->backupPath, $filename);
        $zipArchive = new ZipArchive();
        $zipArchive->open($zipFile, ZipArchive::CREATE);

        foreach ($this->backupDirs as $dir) {
            $absDir = Util::createPath($this->installationBasePath, $dir);
            /*
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absDir), RecursiveIteratorIterator::SELF_FIRST
            );
            */

            $files = new DirectoryIterator($absDir);

            foreach ($files as $absoluteFilename) {

                var_dump($absoluteFilename . '');

                $basename = basename($absoluteFilename);
                if ($basename === '.' || $basename === '..') continue;

                $relativeFileName = str_replace(
                    $this->installationBasePath,
                    '',
                    $absoluteFilename
                );

                var_dump($relativeFileName);

                /*
                if (is_dir($absoluteFilename)) {
                    $zipArchive->addEmptyDir($relativeFileName);
                } else {
                    $zipArchive->addFromString(
                        $relativeFileName,
                        file_get_contents($absoluteFilename)
                    );
                }
                */
            }
        }

        $zipArchive->close();
        return $backupInfo;
    }

    /**
     * Deletes a specific backup
     * @param string $relativeFilename The backup to delete
     * @return void
     */
    public function deleteBackup(string $relativeFilename) : void {
        $zipFile = Util::createPath(
            $this->backupPath,
            basename($relativeFilename)
        );
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
    }

    /**
     * Restores a specific backup
     * @param BackupInfo $backupInfo The backup to restore
     */
    public function restoreBackup(BackupInfo $backupInfo) : void {
        // TODO implementieren
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function readTemplateInformation($tplDir): ?TemplateInfo {
        $versionFile = Util::createPath($this->ncm->pubdir, 'tpl', $tplDir, 'META-INF', 'version.json');
        if (!file_exists($versionFile)) return null;
        $infoArray = json_decode(file_get_contents($versionFile), true);
        $infoArray['dirname'] = $tplDir;
        if (is_array($infoArray)) return new TemplateInfo($infoArray);
        return null;
    }

    private function readInstallationInformationFromBackup(BackupInfo $backupInfo) : ?array {
        $zip = new ZipArchive();
        $info = null;
        if ($zip->open($backupInfo->filename) === true) {
            if (($c = $zip->getFromName("ncm/sys/version.json")) !== false) {
                try {
                    $info = json_decode($c, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                }
            }
        }
        return $info;
    }

    // </editor-fold>

}