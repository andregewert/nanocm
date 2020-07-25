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

namespace Ubergeek\NanoCm\Media;

use Ubergeek\Cache\CacheInterface;
use Ubergeek\Log\Logger;
use Ubergeek\Log\LoggerInterface;
use Ubergeek\Log\Writer\NullWriter;
use Ubergeek\NanoCm\Media\Exception\MediaException;
use Ubergeek\NanoCm\Medium;
use Ubergeek\Net\Fetch;

/**
 * Einfache Medienverwaltung für die Verwendung im NanoCM.
 * 
 * Der MediaManager soll eine einfache Verwaltung von Content-Images
 * ermöglichen. Insbesondere soll die die Skalierung und das Anscheiden von
 * Bildern in vordefinierten Formaten übernehmen. Noch offen ist die Frage, ob
 * lediglich eine Import-Funktion bereitgestellt werden soll (erlaubt eine
 * flexiblere Verarbeitung der Bilder) oder ob auch eingebettete Inhalt
 * dynamisch von den jeweiligen Cloud-Dienstleistern geladen werden sollen.
 * 
 * (Ein Proxy-Script könnte bspw. bei jedem Abruf das angeforderter Bild vom
 * Cloud-Anbieter laden, skalieren und anschneiden.)
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-04
 */
class MediaManager {

    // <editor-fold desc="Properties">

    /**
     * Optional zu verwendender Cache für generierte Bilder
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Optionale Logger-Instanz
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * Enthält eine Liste der unterstützten Eingangsformate in Form von MIME-Types
     *
     * @var string[]
     */
    private $supportedTypes = array(
        'image/jpeg',
        'image/gif',
        'image/png'
    );

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * MediaManager constructor.
     *
     * @param CacheInterface $cache Optional zu verwendender Cache
     * @param null|LoggerInterface $log Optionale Logger-Instanz
     */
    public function __construct($cache = null, $log = null) {
        $this->cache = $cache;
        $this->log = $log;

        if ($log == null) {
            $this->log = new Logger(new NullWriter());
        }
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Ermittelt über die öffentliche Twitter-API Informationen zu dem Tweet mit der angegebenen ID
     *
     * @param $id Tweet-ID
     * @return TweetInfo|null Verfügbare Tweet-Informationen
     */
    public function getTweetInfoById($id) {
        $cacheKey = 'twitter-' . $id;
        if ($this->cache instanceof CacheInterface) {
            $tweetInfo = $this->cache->get($cacheKey);
            if ($tweetInfo != null) {
                $this->log->debug("Found tweet info in cache: $id");
                return $tweetInfo;
            }
        }

        $tweetInfo = null;
        $url = "https://api.twitter.com/1/statuses/oembed.json?id=$id";
        $info = json_decode(Fetch::fetchFromUrl($url));

        if ($info != null) {
            $tweetInfo = new TweetInfo();
            $tweetInfo->url = $info->url;
            $tweetInfo->author_name = $info->author_name;
            $tweetInfo->author_url = $info->author_url;
            $tweetInfo->html = $info->html;
            $tweetInfo->width = $info->width;
            $tweetInfo->height = $info->height;
            $tweetInfo->type = $info->type;
            $tweetInfo->cache_age = $info->cache_age;
            $tweetInfo->provider_name = $info->provider_name;
            $tweetInfo->provider_url = $info->provider_url;
            $tweetInfo->version = $info->version;

            if ($this->cache instanceof CacheInterface) {
                $this->cache->put($cacheKey, $tweetInfo);
            }
        }

        return $tweetInfo;
    }

    /**
     * Ermittelt anhand einer Tweet-URL die verfügbaren Tweet-Informationen
     *
     * @param $url Tweet-URL
     * @return TweetInfo|null Verfügbare Tweet-Informationen
     */
    public function getTweetInfoByUrl($url) {
        if (preg_match('/twitter.com\/[^\/]+\/status\/(\d+)/i', $url, $matches)) {
            $id = $matches[1];
            return $this->getTweetInfoById($id);
        }
        return null;
    }

    /**
     * Erstellt einen Gravatar für den angegebenen E-Mail-Hash in einer bestimmten Größe
     *
     * @param string $hash MD5-Hash der betreffenden E-Mail-Adresse
     * @param int $size Angeforderter Größe
     * @return string|null Die generierten Image-Daten
     */
    public function getGravatar(string $hash, int $size = 50) {
        if (preg_match('/^[a-f0-9]+$/i', $hash) !== false) {
            $cachekey = 'gravatar-' . $hash . '-' . $size;

            if ($this->cache instanceof CacheInterface) {
                $image = $this->cache->get($cachekey);
                if ($image != null) {
                    $this->log->debug("Found gravatar in cache: $hash / $size");
                    return $image;
                }
            }

            $size = intval($size);
            $url  = "http://www.gravatar.com/avatar/$hash?default=robohash&size=$size&rating=X";
            $image = Fetch::fetchFromUrl($url);

            if ($image != null && $this->cache instanceof CacheInterface) {
                $this->cache->put($cachekey, $image);
                $this->log->debug("Putting gravatar image in cache: $hash / $size");
            }
            return $image;
        }

        throw new MediaException("Invalid gravatar hash: $hash");
    }

    /**
     * Erstellt ein Vorschaubild für das Youtube-Video mit angegebener Video-ID in dem übergebenen Bildformat
     *
     * Diese Methode verwendet den optional konfigurierten Cache.
     *
     * @param string $youtubeId ID des Youtube-Videos
     * @param ImageFormat $format Gewünschtes Ausgabeformat
     * @return string|null Rohe Bilddaten (JPEG) oder null, wenn bei der Erstellung ein Fehler auftritt
     */
    public function createImageForYoutubeVideoWithFormat(string $youtubeId, ImageFormat $format) {
        if (preg_match('/^[a-z0-9_\-]+$/i', $youtubeId) !== false) {
            $cacheKey = 'yt-' . $youtubeId . '-' . $format->key;

            if ($this->cache instanceof CacheInterface) {
                $image = $this->cache->get($cacheKey);
                if ($image != null) {
                    $this->log->debug("Found youtube preview in cache: $youtubeId / $format->key");
                    return $image;
                }
            }

            //$srcImgData = Fetch::fetchFromUrl("https://i.ytimg.com/vi/$youtubeId/hqdefault.jpg");
            $srcImgData = Fetch::fetchFromUrl("https://i.ytimg.com/vi/$youtubeId/maxresdefault.jpg");

            $tgtImgData = $this->resizeImageDataToFormat($srcImgData, $format);
            if ($tgtImgData != null && $this->cache instanceof CacheInterface) {
                $this->cache->put($cacheKey, $tgtImgData);
            }
            return $tgtImgData;
        }

        throw new MediaException("Invalid youtube id: $youtubeId");
    }

    /**
     * Erstellt aus der übergebenen Mediendatei ein (Vorschau-)Bild mit der übergebenen Format-Definition.
     *
     * Wenn das Ausgabeformat eine feste Größe definiert, so wird versucht, einen passenden mittigen Zielausschnitt im
     * skalierten Ausgangsbild zu findet. Definiert dagegen das Format eine der Kantenlängen nicht, so wird diese anhand
     * des Seitenverhältnisses dynamisch festgelegt. Definiert das Ausgabeformat keine der beiden Kantenlängen, so wird
     * das Bild in der ursprünglichen Größe ausgegeben.
     *
     * Diese Methode verwendet Caching, sofern diese Klasse mit einer entsprechenden Cache-Instanz konfiguriert worden
     * ist.
     *
     * @param Medium $medium Metadaten zur ursprünglichen Mediendatei (aus der Medienverwaltung)
     * @param string $imagefile Absoluter Pfad für die Bild-Datei
     * @param ImageFormat $format Die Definition für das Ausgabeformat (aus der Medienverwaltung)
     * @param string $outputImageType Typ des Ausgabebildes
     * @param int $scaling Skalierungsfaktor
     * @return null|string Die genrierten Bilddaten als String
     */
    public function createImageForMediumWithImageFormat(Medium $medium, string $imagefile, ImageFormat $format, $outputImageType = 'jpeg', $scaling = 1) {
        if (!in_array($medium->type, $this->supportedTypes)) {
            throw new MediaException("Not supported mime type: $medium->type");
        }

        if ($scaling < 1) $scaling = 1;

        // Bild aus dem Cache laden, wenn möglich
        $cacheKey = $medium->id . '-' . $format->key . '-' . $outputImageType . '-' . $scaling;

        if ($this->cache instanceof CacheInterface) {
            $image = $this->cache->get($cacheKey);
            if ($image !== null) {
                $this->log->debug("Found media thumbnail in cache: $medium->id / $format->key");
                return $image;
            }
        }

        $data = @file_get_contents($imagefile);
        $image = $this->resizeImageDataToFormat($data, $format, $outputImageType, $scaling);
        if ($image !== null && $this->cache instanceof CacheInterface) {
            $this->cache->put($cacheKey, $image);
        }
        return $image;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Erstellt ein Thumbnail im angegebenen Format für die übergebenen Bilddaten
     *
     * Hinweis: Diese Methode verwendet keinen Cache! Die Consumer sind dazu angehalten, bei Bedarf Caching zu
     * implementieren. Die öffentlichen Methoden dieser Klasse bspw. übernehmen das Zwischenspeichern von generierten
     * Vorschaubildern.
     *
     * @param string $imgData Rohe Bilddaten
     * @param ImageFormat $format Gewünschtes Ausgabeformat
     * @param string $outputImageType Ausgabetyp (JPEG oder PNG)
     * @param int $scaling Skalierungsfaktor (für HiDPI-Ausgabe)
     * @return string Rohe Bilddaten für das Thumbnail
     */
    private function resizeImageDataToFormat(string $imgData, ImageFormat $format, $outputImageType = 'jpeg', $scaling = 1) {
        list($sourceWidth, $sourceHeight, $sourceType) = getimagesizefromstring($imgData);
        $originalWidth = $sourceWidth;
        $originalHeight = $sourceHeight;
        $ratio = $sourceWidth /$sourceHeight;
        $src = imagecreatefromstring($imgData);
        $offsetTop = 0;
        $offsetLeft = 0;
        if ($scaling < 1) $scaling = 1;

        // Höhe ist variabel
        if ($format->width > 0 && $format->height == 0) {
            $destWidth = $format->width *$scaling;
            $destHeight = $format->width /$ratio;
        }

        // Breite ist variabel
        else if ($format->width == 0 && $format->height > 0) {
            $destHeight = $format->height *$scaling;
            $destWidth = $format->height *$ratio;
        }

        // Ursprungsformat verwenden
        else if ($format->width == 0 && $format->height == 0) {
            $destWidth = $sourceWidth *$scaling;
            $destHeight = $sourceHeight *$scaling;
        }

        // Festes Format; Ausschnitt dynamisch wählen
        else {
            $destWidth = $format->width *$scaling;
            $destHeight = $format->height *$scaling;

            $f1 = $destWidth / $originalWidth;
            $f2 = $destHeight / $originalHeight;

            if (abs(1 -$f1) <= abs(1 -$f2)) {
                $scalingFactor = $f1;
                while ($originalHeight *$scalingFactor < $destHeight) {
                    $scalingFactor += 0.001;
                }
                if (abs(1 -$scalingFactor) > abs(1 -$f2)) {
                    $scalingFactor = $f2;
                }
            } else {
                $scalingFactor = $f2;
                while ($originalWidth *$scalingFactor < $destWidth) {
                    $scalingFactor += 0.001;
                }
                if (abs(1 -$scalingFactor) > abs(1 -$f1)) {
                    $scalingFactor = $f1;
                }
            }

            $sourceWidth = ceil($destWidth /$scalingFactor);
            $sourceHeight = ceil($destHeight /$scalingFactor);
            $offsetLeft = floor($originalWidth /2) -ceil(($destWidth /$scalingFactor) /2);
            $offsetTop = floor($originalHeight /2) - ceil(($destHeight /$scalingFactor) /2);
        }

        $copy = imagecreatetruecolor($destWidth, $destHeight);
        imagefill($copy, 0, 0, imagecolorallocate($copy, 255, 255, 255));

        imagecopyresampled(
            $copy,  $src,
            0, 0,
            $offsetLeft, $offsetTop,
            $destWidth, $destHeight,
            $sourceWidth, $sourceHeight
        );

        ob_start();
        switch ($outputImageType) {
            case 'png':
                imagepng($copy);
                break;

            case 'jpeg':
            default:
                imagejpeg($copy);
        }
        $image = ob_get_clean();
        return $image;
    }

    // </editor-fold>

}