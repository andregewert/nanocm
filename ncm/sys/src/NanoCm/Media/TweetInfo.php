<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2019 André Gewert <agewert@ubergeek.de>
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

/**
 * Class TweetInfo
 *
 * @package Ubergeek\NanoCm\Media
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2019-01-02
 */
class TweetInfo
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $author_name;

    /**
     * @var string
     */
    public $author_url;

    /**
     * @var string
     */
    public $html;

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $cache_age;

    /**
     * @var string
     */
    public $provider_name;

    /**
     * @var string
     */
    public $provider_url;

    /**
     * @var float
     */
    public $version;

}