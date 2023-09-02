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

/**
 * Encapsulates all option which are passed to the template when rendering the contents by a plugin.
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2023-09-02
 */
class PluginOptions {

    /**
     * Reference to the plugin which is being executed.
     * @var PluginInterface
     */
    public PluginInterface $plugin;

    /**
     * The complete placeholder which is being replaced.
     * @var string
     */
    public string $placeholder;

    /**
     * An array of KeyValuePair with the parsed options.
     * @var Dictionary
     */
    public Dictionary $arguments;

    /**
     * Plugin specific extended information.
     * @var Dictionary
     */
    public Dictionary $extended;

}