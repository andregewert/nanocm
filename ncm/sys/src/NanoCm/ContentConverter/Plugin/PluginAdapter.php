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

use Ubergeek\KeyValuePair;
use Ubergeek\NanoCm\Module\AbstractModule;

/**
 * Adapter class for content converter plugins that implements basic functions.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2021-08-22
 */
abstract class PluginAdapter implements PluginInterface {

    /**
     * Reference to the currently executed NanoCM module
     * @var AbstractModule Currently executed NanoCm module
     */
    private $module;

    /**
     * @inheritDoc
     */
    public function setModule(AbstractModule $module) : void {
        $this->module = $module;
    }

    /**
     * @inheritDoc
     */
    public function getModule(): AbstractModule {
        return $this->module;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int {
        return 0;
    }

    abstract public function replacePlaceholder(string $placeholder, array $parameters): string;

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function getVersion(): string;

    abstract public function getPlaceholder(): string;

}