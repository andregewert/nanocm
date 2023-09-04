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

namespace Ubergeek\NanoCm\Module\TemplateRenderer;

use Ubergeek\Dictionary;

/**
 * This class is utilised to pass abitrary options to the template being rendered.
 * Wherever possible, sub classed should be used to be type safe.
 * @created 2023-09-04
 * @author André Gewert <agewert@ubergeek.de>
 */
class RendererOptions
{
    /**
     * Abitrary options, organized in a dictionary.
     * @var Dictionary
     */
    public Dictionary $misc;

    public function __construct() {
        $this->misc = new Dictionary();
    }

    /**
     * Fills the "misc" dictionary with values from an array.
     * @param array $array
     * @return void
     */
    public function fillFromArray(array $array) {
        foreach ($array as $key => $value) {
            $this->misc->set($key, $value);
        }
    }

}