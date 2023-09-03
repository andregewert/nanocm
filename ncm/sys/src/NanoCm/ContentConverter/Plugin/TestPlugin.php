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

use Ubergeek\Dictionary;
use Ubergeek\NanoCm\Module\AbstractModule;

/**
 * Plugin to demonstrate the implementation of content converter plugins
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2021-08-12
 */
class TestPlugin extends PluginAdapter {

    /**
     * @param AbstractModule $callingModule
     * @param string $placeholder
     * @param Dictionary $arguments
     * @inheritDoc
     */
    function replacePlaceholder(AbstractModule $callingModule, string $placeholder, Dictionary $arguments) : string {
        $output = '<p>Params:</p>';
        $output .= '<ul>' . "\n";
        foreach ($arguments as $param) {
            $output .= '<li>';
            $output .= $this->getModule()->htmlEncode($param->key) . ': ';
            $output .= $this->getModule()->htmlEncode($param->value);
            $output .= '</li>' . "\n";
        }
        $output .= '</ul>' . "\n";
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'Test';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string {
        return 'Just a simple test plugin';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string {
        return '1.0';
    }

    /**
     * @inheritDoc
     */
    public function getPlaceholder(): string {
        return 'test';
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool {
        return false;
    }

}
