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

namespace Ubergeek\Cache;

use Ubergeek\Cache\Exception\InvalidConfigurationException;
use Ubergeek\Log\Logger;

/**
 * Implementiert einen simplen Caching-Mechanismus, der seine Einträge
 * im Dateisystem speichert und als Expiration-Strategie ein festgelegtes
 * DateInterval verwendet.
 *
 * @package Ubergeek\Cache
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-10-31
 *
 * @todo Garbage collection implementieren!
 */
class FileCache implements CacheInterface {

    // <editor-fold desc="Properties">

    /**
     * Ablageort für die Cache-Einträge
     *
     * @var null|string
     */
    private $cachedir = null;

    /**
     * Zeit-Intervall in Sekunden, nach dem die Cache-Einträge ablaufen sollen
     *
     * @var int
     */
    private $lifetime = 0;

    /**
     * Optionales Präfix für die im Cache-Verzeichnis verwalteten Dateien
     *
     * @var string
     */
    private $filePrefix = '';

    /**
     * Optionale Logger-Instanz
     *
     * @var Logger
     */
    private $log = null;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * Dem Konstruktor müssen alle Konfigurationsoptionen übergeben werden
     *
     * @param string $cachedir Absoluter Verzeichnispfad für die Ablage von Cache-Dateien
     * @param int $lifetime Lebensdauer der Cache-Einträge in Sekunden
     * @param string $prefix Optionale Präfix für die Cache-Dateien (falls bspw. mehrere Caches dasselbe Verzeichnis nutzen sollen)
     * @param Logger $log Optionale Logger-Instanz
     */
    public function __construct(string $cachedir,
                                int $lifetime,
                                $prefix = '',
                                $log = null) {
        $this->cachedir = $cachedir;
        $this->lifetime = $lifetime;
        $this->filePrefix = $prefix;
        $this->log = $log;
        $this->removeExpiredEntries();
    }

    // </editor-fold>


    // <editor-fold desc="CacheInterface implementation">

    /**
     * Liest - falls vorhanden - einen Wert aus dem Cache aus
     *
     * Ist der angeforderte Inhalt nicht im Cache vorhanden oder
     * bereits abgelaufen, so wird null zurückgegeben. Andernfalls
     * wird der gecachte Wert zurückgegeben.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key) {
        $fname = $this->createCacheFileName($key);
        if (!file_exists($fname) || $this->isCacheFileExpired($fname)) {
            return null;
        }
        if ($this->log instanceof Logger) {
            $this->log->debug("Reading cached content for key $key from file $fname");
        }
        return unserialize(file_get_contents($fname));
    }

    /**
     * Legt einen Wert unter dem angegebenen Schlüssel im Cache ab
     *
     * @param string $key Schlüssel
     * @param mixed $value Abzulegender Wert
     * @return void
     */
    public function put(string $key, $value) {
        $fname = $this->createCacheFileName($key);
        if ($this->log != null) {
            $this->log->debug("Putting value to cache for key $key");
        }
        try {
            file_put_contents($fname, serialize($value));
        } catch (\Exception $ex) {
            if ($this->log instanceof Logger) {
                $this->log->debug("Error while writing cache file: " . $ex->getMessage());
                $this->log->debug($ex->getTrace());
            }
        }
    }

    /**
     * Aktualisiert den Timestamp eines Cache-Eintrags
     *
     * @param string $key Schlüssel des zu aktualisierenden Cache-Eintrags
     * @return bool true bei Erfolg; false, wenn der Eintrag nicht vorhanden ist oder nicht aktualisiert werden konnte
     */
    public function touch(string $key) {
        $fname = $this->createCacheFileName($key);
        if (file_exists($fname)) {
            if ($this->log != null) {
                $this->log->debug("Touching cache entry $key for file $fname");
            }
            try {
                touch($fname);
            } catch (\Exception $ex) {
                if ($this->log instanceof Logger) {
                    $this->log->debug("Error while touching cache file: " . $ex->getMessage());
                    $this->log->debug($ex->getTrace());
                }
            }
        }
        return false;
    }

    /**
     * Löscht den gesamten Cache
     *
     * @return void
     */
    public function clear() {
        if ($this->cachedir == null || !file_exists($this->cachedir)) {
            throw new InvalidConfigurationException("Invalid cache dir: $this->cachedir");
        }

        $dh = opendir($this->cachedir);
        if ($dh !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname != '.' && $fname != '..') {
                    if ($this->filePrefix == '' || substr($fname, 0, mb_strlen($this->filePrefix)) == $this->filePrefix) {
                        $fullname = $this->cachedir . DIRECTORY_SEPARATOR . $fname;
                        if ($this->log != null) {
                            $this->log->debug("Removing file $fullname");
                        }
                        try {
                            unlink($fullname);
                        } catch (\Exception $ex) {
                            if ($this->log instanceof Logger) {
                                $this->log->debug("Error while deleting cache file: " . $ex->getMessage());
                                $this->log->debug($ex->getTrace());
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }
    }

    /**
     * Entfernt einen einzelnen Eintrag aus dem Cache
     *
     * @param string $key Schlüssel des zu entfernenden Cache-Eintrags
     * @return bool true bei Erfolg
     */
    public function unset(string $key) {
        $fname = $this->createCacheFileName($key);
        if (file_exists($fname)) {
            if ($this->log != null) {
                $this->log->debug("Unsetting cache entry $key / removing file $fname");
            }
            try {
                return unlink($fname);
            } catch (\Exception $ex) {
                if ($this->log instanceof Logger) {
                    $this->log->debug("Error while deleting cache file: " . $ex->getMessage());
                    $this->log->debug($ex->getTrace());
                }
            }
        }
        return false;
    }

    // </editor-fold>


    // <editor-fold desc="Internal Methods">

    /**
     * Löscht alle abgelaufenen Einträge dieses Caches
     *
     * @return void
     */
    private function removeExpiredEntries() {
        if ($this->cachedir == null || !file_exists($this->cachedir)) {
            throw new InvalidConfigurationException("Invalid cache dir: $this->cachedir");
        }

        $dh = opendir($this->cachedir);
        if ($dh !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname != '.' && $fname != '..') {
                    if ($this->filePrefix == '' || substr($fname, 0, mb_strlen($this->filePrefix)) == $this->filePrefix) {
                        $fullname = $this->cachedir . DIRECTORY_SEPARATOR . $fname;
                        $diff = time() -filemtime($fullname);

                        if ($diff > $this->lifetime) {
                            if ($this->log != null) $this->log->debug("Removing expired file $fullname");
                            try {
                                unlink($fullname);
                            } catch (\Exception $ex) {
                                if ($this->log instanceof Logger) {
                                    $this->log->debug("Error while deleting cache file: " . $ex->getMessage());
                                    $this->log->debug($ex->getTrace());
                                }
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }
    }

    /**
     * Überprüft, ob die angegebene Cache-Datei abgelaufen ist
     *
     * @param $filename Der zu prüfende Dateiname
     * @return bool true, wenn der Cache-Eintrag bereits abgelaufen ist
     */
    private function isCacheFileExpired($filename) {
        if (!file_exists($filename)) return true;
        $diff = time() -filemtime($filename);
        if ($diff > $this->lifetime) {
            if ($this->log != null) {
                $this->log->debug("Entry $filename is expired (diff: $diff)");
            }
            return true;
        }
        return false;
    }

    /**
     * Erstellt den zu verwendenden Dateinamen für den übergebenen Key
     *
     * Diese Methode überprüft außerdem, ob das konfigurierte Verzeichnis
     * tatsächlich vorhanden ist bzw. tatsächlich ein Verzeichnis ist.
     * Gegebenenfalls wird das Verzeichnis angelegt.
     *
     * @param string $key
     * @return string
     * @throws InvalidConfigurationException Wenn das Cache-Verzeichnis nicht konfiguriert ist
     */
    private function createCacheFileName(string $key) : string {
        if ($this->cachedir == null) {
            throw new InvalidConfigurationException("Invalid cache dir: $this->cachedir");
        }

        if (file_exists($this->cachedir) && !is_dir($this->cachedir)) {
            throw new InvalidConfigurationException("Configured path is not a directory: $this->cachedir");
        }

        if (!file_exists($this->cachedir)) {
            if (!mkdir($this->cachedir)) {
                throw new InvalidConfigurationException("Could not create cache directory: $this->cachedir");
            }
        }

        $fname = $this->cachedir . DIRECTORY_SEPARATOR . (string)$this->filePrefix . md5($key);
        if ($this->log != null) {
            $this->log->debug("Cache file for key $key is $fname");
        }
        return $fname;
    }

    // </editor-fold>

}
