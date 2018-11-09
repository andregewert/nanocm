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

namespace Ubergeek\NanoCm\Media;

use Ubergeek\Cache\CacheInterface;
use Ubergeek\NanoCm\Exception\MediaException;
use Ubergeek\NanoCm\Medium;

/**
 * Class ImageResizer
 *
 * Der ImageResizer ist dafür zuständig, aus Bildern in verschiedenen Ausgangsformaten Vorschaubilder in definierten
 * Formaten zu generieren. Für generierte Vorschaubilder wird ein dateibasierter Cache verwendet.
 *
 * @package Ubergeek\NanoCm\Media
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-07
 */
class ImageResizer
{
    // <editor-fold desc="Properties">

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

    /**
     * Zu verwendender Cache für erzeugte Vorschaubilder
     *
     * @var CacheInterface
     */
    private $cache;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * ImageResizer constructor.
     *
     * @param CacheInterface|null $cache
     */
    public function __construct($cache) {
        $this->cache = $cache;
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Erstellt aus der übergebenen Mediendatei ein (Vorschau-)Bild mit der übergebenen Format-Definition.
     *
     * Wenn das Ausgabeformat eine feste Größe definiert, so wird versucht, einen passenden mittigen Zielausschnitt im
     * skalierten Ausgangsbild zu findet. Definiert dagegen das Format eine der Kantenlängen nicht, so wird diese anhand
     * des Seitenverhältnisses dynamisch festgelegt. Definiert das Ausgabeformat keine der beiden Kantenlängen, so wird
     * das Bild in der ursprünglichen Größe ausgegeben.
     *
     * @param Medium $medium Metadaten zur ursprünglichen Mediendatei (aus der Medienverwaltung)
     * @param string $data Die eigentlichen Daten des Ausgangsbildes (aus dem Dateisystem)
     * @param ImageFormat $format Die Definition für das Ausgabeformat (aus der Medienverwaltung)
     * @param string $outputImageType Typ des Ausgabebildes
     * @return null|string Die genrierten Bilddaten als String
     *
     * @todo Caching verwenden!
     * @todo Fehler abfangen?
     */
    public function createImageForMediumWithImageFormat(Medium $medium, string $data, ImageFormat $format, $outputImageType = 'jpeg') {
        if (!in_array($medium->type, $this->supportedTypes)) {
            throw new MediaException("Not supported mime type: $medium->type");
        }

        list($sourceWidth, $sourceHeight, $sourceType) = getimagesizefromstring($data);
        $originalWidth = $sourceWidth;
        $originalHeight = $sourceHeight;

        $ratio = $sourceWidth /$sourceHeight;
        $src = imagecreatefromstring($data);
        $offsetTop = 0;
        $offsetLeft = 0;

        // Höhe ist variabel
        if ($format->width > 0 && $format->height == 0) {
            $destWidth = $format->width;
            $destHeight = $format->width /$ratio;
        }

        // Breite ist variabel
        else if ($format->width == 0 && $format->height > 0) {
            $destHeight = $format->height;
            $destWidth = $format->height *$ratio;
        }

        // Ursprungsformat verwenden
        else if ($format->width == 0 && $format->height == 0) {
            $destWidth = $sourceWidth;
            $destHeight = $sourceHeight;
        }

        // Festes Format; Ausschnitt dynamisch wählen
        else {
            $destWidth = $format->width;
            $destHeight = $format->height;

            // Breite ist die lange Seite
            if ($destWidth > $destHeight || ($destWidth == $destHeight && $originalWidth < $originalHeight)) {
                if ($originalWidth != $destWidth) {
                    $scalingFactor = $destWidth /$originalWidth;
                    $sourceWidth = ceil($destWidth /$scalingFactor);
                    $sourceHeight = ceil($destHeight /$scalingFactor);
                    $offsetTop = floor($originalHeight /2) - ceil(($destHeight /$scalingFactor) /2);
                } else {
                    $offsetTop = floor($originalHeight /2) - ceil($destHeight /2);
                }
            }

            // Höhe ist die lange Seite
            else {
                if ($originalHeight != $destHeight) {
                    $scalingFactor = $destHeight /$originalHeight;
                    $sourceWidth = ceil($destWidth /$scalingFactor);
                    $sourceHeight = ceil($destHeight /$scalingFactor);
                    $offsetLeft = floor($originalWidth /2) -ceil(($destWidth /$scalingFactor) /2);
                } else {
                    $offsetLeft = floor($originalWidth /2) -ceil($destWidth /2);
                }
            }
        }

        $copy = imagecreatetruecolor($destWidth, $destHeight);
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
        $c = ob_get_clean();
        return $c;
    }

    // </editor-fold>

}