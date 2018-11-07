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

namespace Ubergeek\NanoCm;

use Ubergeek\Cache\CacheInterface;
use Ubergeek\NanoCm\Exception\MediaException;

/**
 * Class ImageResizer
 *
 * Der ImageResizer ist dafür zuständig, aus Bildern in verschiedenen Ausgangsformaten Vorschaubilder in definierten
 * Formaten zu generieren. Für generierte Vorschaubilder wird ein dateibasierter Cache verwendet.
 *
 * @package Ubergeek\NanoCm
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

    public function createImageForMediumWithImageFormat(Medium $medium, string $data, ImageFormat $format) {
        if (!in_array($medium->type, $this->supportedTypes)) {
            throw new MediaException("Not supported mime type: $medium->type");
        }

        list($source_width, $source_height, $source_type) = getimagesizefromstring($data);
        $ratio = $source_width /$source_height;
        $src = imagecreatefromstring($data);

        $destWidth = $source_width;
        $destHeight = $source_height;

        if ($format->width > 0) {
            $destWidth = $format->width;
            if ($format->height > 0) {
                $destHeight = $format->height;
            } else {
                $destHeight = $destWidth *$ratio;
                // TODO So einfach geht es nicht -> stattdessen einen passenden Bildausschnitt finden!
            }
        } elseif ($format->height > 0) {
            $destHeight = $format->height;
            if ($destWidth > 0) {
                $destWidth = $destWidth;
            } else {
                $destWidth = $destHeight /$ratio;
                // TODO So einfach geht es nicht -> stattdessen einen passenden Bildausschnitt finden!
            }
        }

        //$copy = imagecreate($destWidth, $destHeight);
        $copy = imagecreatetruecolor($destWidth, $destHeight);
        imagecopyresampled(
            $copy,
            $src,
            0, 0, 0, 0,
            $destWidth,
            $destHeight,
            $source_width,
            $source_height
        );

        ob_start();
        imagepng($copy);
        $c = ob_get_clean();
        return $c;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    // </editor-fold>
}