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

use Ubergeek\NanoCm\Util\DirectoryEntry;

/**
 * Bietet einige statische Hilfmethoden
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-19
 */
final class Util {

    // <editor-fold desc="Constants">

    /**
     * Specifies that an operation should be done recursively
     * @var bool
     */
    public const RECURSIVE = true;

    /**
     * Specifies that an operation should be done non-recursively
     * @var bool
     */
    public const NON_RECURSIVE = false;

    // </editor-fold>


    /**
     * Creates the gravatar image url for the given mail address
     *
     * @param string $mail Mail address of the gravatar user
     * @param int $size Image size
     * @return string
     */
    public static function getGravatarUserImageUrl($mail, $size = 50) : string {
        $hash = md5(trim($mail));
        $size = (int)$size;
        return "/media/$hash/gravatar/$size";
    }

    /**
     * Überprüft, ob der übergebene Text Begriffe aus der übergebenen Liste enthält
     *
     * @param string $text Zu prüfender Text
     * @param string[] $words Zu prüfende Begriffsliste
     * @return bool true, wenn der zu prüfende Text mindestens einen der genannten Begriffe enthält
     */
    public static function checkTextAgainstWordsList($text, $words) : bool {
        $tokens = preg_split('/([\W]+)/i', strtolower($text));
        foreach ($words as $test) {
            if (in_array($test, $tokens)) return true;
        }
        return false;
    }

    /**
     * Gibt eine Wörter-Blacklist zurück, mit der Kommentare auf Junk hin
     * überprüft werden können
     *
     * @return array
     * @todo Die Begriffsliste könnte auch in der Datenbank gepflegt werden?
     */
    public static function getJunkWords() {
        return array(
            'viagra',
            'cialis',
            'casino',
            'penis',
            'rolex',
            'visit',
            'pussy',
            'porn',
            'porno',
            'drug',
            'prices',
            'pharmacy',
            'prednisolone',
            'capsules',
            'levitra',
            'chlorthalidone',
            'hydrochlorothiazide',
            'adult',
            'dating'
        );
    }

    /**
     * Überprüft die übergebene E-Mail-Adresse auf Gültigkeit
     *
     * Es wird eine formale Überprüfung vorgenommen; außerdem
     * wird die Domain auf einen MX-DNS-Eintrag überprüft.
     *
     * @param String $email
     * @return Boolean
     */
    public static function isValidEmail($email) : bool {
        // formale Überprüfung
        if (preg_match('/^[a-z0-9]+[a-z0-9\+\-\._]*@([a-z0-9\-]+[\.])+[a-z]{2,8}$/i', $email) !== 1)
            return(false);

        // DNS-MX-Überprüfung
        $arr = explode('@', $email, 2);
        if (!checkdnsrr(array_pop($arr), 'MX'))
            return(false);

        return(true);
    }

    public static function getTweetThisUrl($url, $title = null) : string {
        $tweetUrl = 'https://twitter.com/home?status=';
        if ($title != null) {
            $tweetUrl .= urlencode($title . "\n");
        }
        $tweetUrl .= urlencode($url);
        return $tweetUrl;
    }

    /**
     * Gets the size in bytes for the given directory
     *
     * @param string $path Absolute path to the directory
     * @return int Size of the directory in bytes
     */
    public static function getDirectorySize($path) : int {
        $bytestotal = 0;
        $path = realpath($path);
        if($path !== false && $path !== '' && file_exists($path)){
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $object){
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }

    /**
     * Returns the extension of the given filename
     * @param string $filename Filename
     * @return string Extension
     */
    public static function getFileExtension(string $filename) : string {
        if (preg_match("/\.([^\.]+?)$/i", $filename, $matches) > 0) {
            return strtolower($matches[1]);
        }
        return '';
    }

    /**
     * Returns a "human readable" version of the given file size (in bytes)
     * @param int $bytes Filesite in bytes
     * @param int $decimals Number of decimals to show
     * @return string Human readable file size
     */
    public static function sizeHumanReadable($bytes, $decimals = 2) : string {
        $size = array('B','KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }

    /**
     * Fügt die übergebenen Pfadbestandteile mit dem System-Verzeichnistrenner
     * zu einer Pfadangabe zusammen
     *
     * @param string ...$parts Pfadbestandteile
     * @return string Der zusammengesetzte Pfad
     */
    public static function createPath(string ...$parts) : string {
        if ($parts[0] === '/') $parts[0] = '';
        return join(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Kodiert einen String für die HTML-Ausgabe.
     *
     * Der Eingabestring muss UTF8-kodiert sein.
     * Als Zielformate werden Constants::FORMAT_HTML und Constants::FORMAT_XHTML unterstützt.
     * Wird etwas anderes übergeben, so wird der unveränderte Ausgangsstring zurückgegeben.
     *
     * @param string|null $string Input string
     * @param string $targetFormat Target format
     * @return string (X)HTML encoded string
     */
    public static function htmlEncode($string, $targetFormat = Constants::FORMAT_HTML) : string {
        $string = (string)$string;
        if ($targetFormat == Constants::FORMAT_HTML) {
            return htmlentities($string, ENT_COMPAT, 'utf-8');
        } elseif ($targetFormat == Constants::FORMAT_XHTML) {
            //return htmlentities($string, ENT_COMPAT | ENT_XML1 | ENT_SUBSTITUTE, 'utf-8');
            return htmlspecialchars($string, ENT_COMPAT | ENT_XML1, 'UTF-8');
        }
        return $string;
    }
    
    /**
     * Vereinfacht einen String (bspw. eine Artikel-Headline) für die Darstellung
     * in der URL
     *
     * @param string $string Ursprünglicher String
     * @return string Vereinfachter String
     */
    public static function simplifyUrlString(string $string) : string {
        $str = str_replace(array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'), array('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'), $string);
        $str = trim(preg_replace('/[^a-z0-9\-\_ ]/i', '', $str));
        return $str;
    }

    public static function shortenText($input, $length = 80) {
        if ($input == null) return '';
        $i = $length;

        while (substr($input, $i -1, 1) != ' ' && $i > 0) {
            $i--;
        }
        return substr($input, 0, $i);
    }

    /**
     * Gibt ein Array mit den in der Editor-Toolbar anzuzeigenden Sonderzeichen zurück
     * @return array
     * @todo Beschreibung sollte lokalisierbar sein
     */
    public static function getSpecialCharDictionary() {
        return array(
            160     => 'Geschütztes Leerzeichen',
            8201    => 'Schmales Leerzeichen',
            8239    => 'Schmales geschütztes Leerzeichen',
            8211    => 'Halbgeviertstrich',
            8212    => 'Geviertstrich',
            187     => 'Guillemets',
            171     => 'Guillemets',
            8250    => 'Guillemets 2',
            8249    => 'Guillemets 2',
            8222    => 'Anführungszeichen',
            8220    => 'Anführungszeichen',
            8218    => 'Anführungszeichen 2',
            8216    => 'Anführungszeichen 2',
            8226    => 'Bullet',
            183     => 'Mittelpunkt'
        );
    }

    /**
     * Gibt eine gruppierte / kategorisierte Liste von Emoji-Codes zurück.
     *
     * Die Definitionsdatei der verfügbaren Emojis wird generiert aus der vom Unicode Consortium bereitgestellten
     * Testdatei und enthält zunächst alle im Unicode-Standard definierten Emojis. Eine Blacklist ist separat vorhanden
     * und kann -- installationsspezifisch -- vom jeweiligen Administrator angepasst werden. Im Standard-Zustand sind in
     * der Blacklist diejenigen Emojis enthalten, die auf dem iMac des Entwicklers nicht verfünftig unterstützt werden.
     *
     * Eine Idee ist auch, das anzuzeigende Emoji-Spektrum konfigurierbar zu machen, denn für die meisten Zwecke ist die
     * Anzahl der verfügbaren Emojis ohnehin viel zu groß.
     *
     * @return array
     * @todo Eventuell die gesamte Emoji-Funktionalität in eine separate Klasse verschieben
     * @todo Für bessere Kompatibilität optional auf eine Minimal-Whitelist umstellbar machen
     */
    public static function getEmojiDictionary() {
        $emojis = (array)json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji-list.json'));
        $blacklist = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji-blacklist.json'));

        foreach (array_keys($emojis) as $group) {
            $emojis[$group] = (array)$emojis[$group];

            foreach ($blacklist as $blacklistKey) {
                $emojiKeys = array_keys($emojis[$group]);
                $wildcard = null;
                if (substr($blacklistKey, -1) === '*') {
                    $wildcard = 'begin';
                    $blacklistKey = substr($blacklistKey, 0, -1);
                } elseif (substr($blacklistKey, 0, 1) === '*') {
                    $wildcard = 'end';
                    $blacklistKey = substr($blacklistKey, 1);
                }

                foreach ($emojiKeys as $emojiKey) {
                    if (
                        $blacklistKey == $emojiKey
                        || ($wildcard === 'begin' && substr($emojiKey, 0, strlen($blacklistKey)) == $blacklistKey)
                        || ($wildcard === 'end' && substr($emojiKey, -strlen($blacklistKey)) == $blacklistKey)
                    ) {
                        unset($emojis[$group][$emojiKey]);
                    }
                }

                /*
                if (in_array($blacklistKey, array_keys($emojis[$group]))) {
                    unset($emojis[$group][$blacklistKey]);
                }
                */
            }
        }
        return $emojis;
    }

    /**
     * Reads the contents of a directory and returns an array of DirectoryEntry objects
     *
     * @param string $path Absolute path to the directory to read
     * @param bool $recursive Decides if directory should be read recursively
     * @param string $basePath Base path to build a relative filename
     * @param array $exclude Optional array with relative file names to exclude
     * @return DirectoryEntry[] Array with directory contents
     */
    public static function getDirectoryContents(string $path, bool $recursive = true, string $basePath = '', array $exclude = array()) : array {
        $entries = array();

        $dh = opendir($path);
        if ($dh !== false) {
            while (($fname = readdir($dh)) !== false) {
                if ($fname !== '.' && $fname !== '..' && !in_array($fname, $exclude, true)) {

                    $absoluteFileName = Util::createPath($path, $fname);
                    $fileType = is_dir($absoluteFileName)? DirectoryEntry::TYPE_DIR : DirectoryEntry::TYPE_FILE;
                    $directoryEntry = new DirectoryEntry($absoluteFileName, $basePath, $fileType);
                    $entries[] = $directoryEntry;

                    if ($fileType === DirectoryEntry::TYPE_DIR && $recursive) {
                        foreach (self::getDirectoryContents($absoluteFileName, true, $basePath, $exclude) as $temp) {
                            $entries[] = $temp;
                        }
                    }
                }
            }
        }

        return $entries;
    }
    
}