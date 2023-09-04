<?php
/*
 * NanoCM
 * Copyright (C) 2017-2023 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\NanoCm\ContentConverter\Plugin;

use Ubergeek\NanoCm\Media\ImageFormat;
use Ubergeek\NanoCm\Medium;

/**
 * Options for slideshow content blocks.
 * These options are passed to the template renderer.
 */
class SlideshowPluginOptions extends PluginOptions {

    /**
     * Meta data for the selected media folder.
     * @var Medium
     */
    public Medium $folder;

    /**
     * The media files to be shown.
     * @var Medium[]
     */
    public array $media;

    /**
     * Meta data for the selected preview image format.
     * @var ImageFormat
     */
    public ImageFormat $format;

    /**
     * Specifies if the slideshow should be automatically played.
     * @var bool
     */
    public bool $autoplay;

}
