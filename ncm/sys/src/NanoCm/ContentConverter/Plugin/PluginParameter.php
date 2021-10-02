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

class PluginParameter {

    // <editor-fold desc="Constants">

    /**
     * Parameter type "media folder"
     * @var int
     */
    const TYPE_MEDIAFOLDER = 1;

    /**
     * Parameter type "media entry"
     * @var int
     */
    const TYPE_MEDIAENTRY = 2;

    /**
     * Parameter type "string"
     * @var int
     */
    const TYPE_STRING = 3;

    /**
     * Parameter type "selection".
     * One value from a list of options.
     * @var int
     */
    const TYPE_SELECTION = 4;

    /**
     * Parameter type "set".
     * Zero or more values from a list of options.
     * @var int
     */
    const TYPE_SET = 5;

    // </editor-fold>


    // <editor-fold desc="Properties">

    /**
     * Key (name) of this parameter.
     * @var string
     */
    public $key = '';

    /**
     * Current value.
     * @var mixed
     */
    public $value = '';

    /**
     * Data type of this parameter.
     * @var int
     */
    public $type = self::TYPE_STRING;

    /**
     * Specifies if this parameter is required.
     * @var bool
     */
    public $required = false;

    /**
     * An array with selectable values.
     * Applies if type is set or option.
     * @var array
     */
    public $options = array();

    /**
     * Optional default value.
     * @var mixed
     */
    public $default = '';

    // </editor-fold>


    // <editor-fold desc="Methods">

    public static function fromArray(array $data) : PluginParameter {
        $param = new PluginParameter();
        foreach ($data as $key => $value) {
            $key = strtolower($key);
            if ($key === 'key') $param->key = $value;
            if ($key === 'value') $param->value = $value;
            if ($key === 'type') $param->type = $value;
            if ($key === 'required') $param->required = $value;
            if ($key === 'options') $param->options = $value;
            if ($key === 'default') $param->default = $value;
        }
        return $param;
    }

    // </editor-fold>

}
