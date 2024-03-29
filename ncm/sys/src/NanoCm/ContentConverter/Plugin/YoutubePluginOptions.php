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

/**
 * Encapsulates the options for the youtube (preview) content plugin.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2023-09-04
 */
class YoutubePluginOptions extends PluginOptions {

    /**
     * The youtube video id
     * @var string
     */
    public string $videoId = '';

    /**
     * ImageFormat for the generated preview image.
     * @var ImageFormat
     */
    public ImageFormat $previewImageFormat;

}