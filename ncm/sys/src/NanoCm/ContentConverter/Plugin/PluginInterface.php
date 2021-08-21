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
 * Simple plugin for extending the functionality of the markup language used to describe contents
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2021-08-12
 */
interface PluginInterface {

    /**
     * Replaces a placeholder
     * @param string $placeholder The whole placeholder
     * @param KeyValuePair[] $parameters List of parsed parameters
     * @return string The generated content
     */
    public function replacePlaceholder(string $placeholder, array $parameters) : string;

    /**
     * Gets the priority of this plugin.
     * This priority defines the execution order of converter plugins.
     * @return int Priority
     */
    public function getPriority() : int;

    /**
     * Gets the name of this plugin.
     * This can be an arbitrary string, but it should be just a short name.
     * Use the description string for a longer descriptive text for the plugin.
     * @return string Name of the plugin
     */
    public function getName() : string;

    /**
     * Gets a description for this plugin.
     * @return string Description for the plugin
     */
    public function getDescription() : string;

    /**
     * Gets the version of this plugin.
     * The string should be in the format x.x.x.x.
     * @return string Version of the plugin
     */
    public function getVersion() : string;

    /**
     * Gets the placeholder that should be replaced by this plugin.
     * The placeholder should be unique over all plugins.
     * @return string Placeholder that should be replaced by the plugin
     */
    public function getPlaceholder() : string;

    /**
     * Sets the reference to the currently executed NanoCM module
     * @param AbstractModule $module Currently executed module
     */
    public function setModule(AbstractModule $module) : void;

    /**
     * Gets the reference to the currently executed NanoCM module.
     * @return AbstractModule Reference to the currently executed module.
     */
    public function getModule() : AbstractModule;

}