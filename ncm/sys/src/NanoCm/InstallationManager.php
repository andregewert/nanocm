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
use JsonException;
use Ubergeek\NanoCm\Util\DirectoryEntry;
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

    /**
     * Paths to include into backups
     * @var string[]
     */
    private $backupDirs = array('ncm', 'tpl');

    /**
     * Paths to exclude from backups
     * @var string[]
     */
    private $backupExcludePaths = array(
        'backup'
    );

    /**
     * Regular expression to check backup filenames
     * @var string
     */
    private $backupFilenamePattern = '/^backup-(.+)\.zip$/i';

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
     *
     * @return TemplateInfo[] An array containing information about installed templates
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
                        if ($info !== null) {
                            $templates[$fname] = $info;
                        }
                    }
                }
            }
        }

        uasort($templates, static function($a, $b) {
            return strnatcasecmp($a->title, $b->title);
        });

        return $templates;
    }

    /**
     * Creates an array with information of available ncm releases by
     * reading a remote feed
     *
     * @return array
     */
    public function getAvailableVersionsFromServer() {
        // TODO implementieren
        return array();
    }

    /**
     * Returns an array with information for existing backups
     *
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
                    $backupInfo = $this->readBackupInfoByRelativeFilename($fname);
                    if ($backupInfo !== null) {
                        $backups[] = $backupInfo;
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
                    && $b->creationDateTime instanceof DateTime
                    && $a->creationDateTime != $b->creationDateTime) {
                        $r = ($a->creationDateTime < $b->creationDateTime)? 1 : 0;
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
        return $backups;
    }

    /**
     * Reads metadata for the specified (relative) backup file
     *
     * The given file has to be located in the ncm backup directory.
     * If the filename does not match the default pattern it is considered as invalid.
     *
     * @param string $relativeFilename Relative filename
     * @return BackupInfo|null Metadata or null if the given filename is not valid
     */
    public function readBackupInfoByRelativeFilename(string $relativeFilename) : ?BackupInfo {
        $absoluteFilename = $this->backupPath . DIRECTORY_SEPARATOR . $relativeFilename;
        if (!file_exists($absoluteFilename)) return null;
        if (preg_match($this->backupFilenamePattern, $relativeFilename) < 1) return null;

        $backupInfo = new BackupInfo();
        $backupInfo->filename = $absoluteFilename;
        $backupInfo->creationDateTime = new DateTime();
        $backupInfo->creationDateTime->setTimestamp(filectime($absoluteFilename));
        $backupInfo->filesize = filesize($absoluteFilename);
        $backupInfo->version = 'unknown';
        if (($info = $this->readInstallationInformationFromBackup($backupInfo)) !== null) {
            $backupInfo->installationInfo = $info;
            if (array_key_exists('version', $info)) $backupInfo->version = $info['version'];
        }

        return $backupInfo;
    }

    /**
     * Creates a backup of the current installation
     *
     * The backup file (zip archive) is created in sys/backup. This method return
     * an BackupInfo object containing information about the newly created backup.
     * Throws an exception when an error occurs.
     *
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

        // Files within the installation root
        $files = Util::getDirectoryContents(
            $this->installationBasePath,
            Util::NON_RECURSIVE,
            $this->installationBasePath,
            $this->backupExcludePaths
        );
        foreach ($files as $directoryEntry) {
            if ($directoryEntry->entryType !== DirectoryEntry::TYPE_DIR) {
                $zipArchive->addFromString(
                    $directoryEntry->relativePath,
                    file_get_contents($directoryEntry->absolutePath)
                );
            }
        }

        // Files from subdirs
        foreach ($this->backupDirs as $dir) {
            $absDir = Util::createPath($this->installationBasePath, $dir);
            $files = Util::getDirectoryContents(
                $absDir,
                Util::RECURSIVE,
                $this->installationBasePath,
                $this->backupExcludePaths
            );

            foreach ($files as $directoryEntry) {
                if ($directoryEntry->entryType === DirectoryEntry::TYPE_DIR) {
                    $zipArchive->addEmptyDir($directoryEntry->relativePath);
                } else {
                    $zipArchive->addFromString(
                        $directoryEntry->relativePath,
                        file_get_contents($directoryEntry->absolutePath)
                    );
                }
            }
        }

        $zipArchive->close();
        return $backupInfo;
    }

    /**
     * Deletes the specified backup file
     *
     * @param BackupInfo $backupInfo Backup information
     * @return void
     */
    public function deleteBackup(BackupInfo $backupInfo) : void {
        if (preg_match($this->backupFilenamePattern, basename($backupInfo->filename)) > 0) {
            if (file_exists($backupInfo->filename)) {
                unlink($backupInfo->filename);
            }
        }
    }

    /**
     * Returns the backup content (zip file) as a string
     *
     * @param BackupInfo $backupInfo Backup information
     * @return string The backup zip file's contents
     */
    public function getBackupContents(BackupInfo $backupInfo) : string {
        if (preg_match($this->backupFilenamePattern, basename($backupInfo->filename)) > 0) {
            return file_get_contents($backupInfo->filename);
        }
    }

    /**
     * Restores a specific backup
     *
     * Throws an exception when an error occurs.
     * @param BackupInfo $backupInfo The backup to restore
     * @return void
     */
    public function restoreBackup(BackupInfo $backupInfo) : void {
        $zipArchive = new ZipArchive();
        if ($zipArchive->open($backupInfo->filename) === true) {
            $zipArchive->extractTo($this->installationBasePath);
            $zipArchive->close();
        }
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