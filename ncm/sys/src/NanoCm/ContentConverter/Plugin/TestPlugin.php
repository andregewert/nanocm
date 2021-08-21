<?php
/*
 * NanoCM
 * Copyright (c) 2017 - 2021 André Gewert <agewert@ubergeek.de>
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

/**
 * Plugin to demonstrate the implementation of content converter plugins
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2021-08-12
 */
class TestPlugin extends PluginAdapter {

    /**
     * @inheritDoc
     */
    function replacePlaceholder(string $placeholder, array $parameters) : string {
        //return '<p>' . $this->getModule()->htmlEncode($this->getDescription()) . '</p>';
        $output = '<p>';
        $output .= '<ul>' . "\n";
        foreach ($parameters as $param) {
            $output .= '<li>';
            $output .= $this->getModule()->htmlEncode($param->key) . ': ';
            $output .= $this->getModule()->htmlEncode($param->value);
            $output .= '</li>' . "\n";
        }
        $output .= '</ul>' . "\n";
        $output .= '</p>';
        return $output;
    }

    public function getName(): string {
        return 'Test';
    }

    public function getDescription(): string {
        return 'Just a test plugin äöüß';
    }

    public function getVersion(): string {
        return '1.0';
    }

    public function getPlaceholder(): string {
        return 'test';
    }
}
