<?php

/**
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

use Ubergeek\NanoCm\ContentConverter\DecoratedContentConverter;
use Ubergeek\NanoCm\ContentConverter\HtmlConverter;

class PlainTextConverter extends DecoratedContentConverter {

    public function __construct(HtmlConverter $htmlConverter) {
        parent::__construct($htmlConverter);
    }

    public function convertFormattedText(string $input, array $options = array()): string {

        if ($this->decoratedConverter !== null) {
            $input = $this->decoratedConverter->convertFormattedText($input, $options);
        }

        // TODO vernünftige Implementierung :)
        return strip_tags($input);
    }

}