<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2018 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\NanoCm;

/**
 * Bildet eine Hinweis-Meldung für den End-Anwender ab
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-12-02
 */
class UserMessage
{
    // <editor-fold desc="Constants">

    public const TYPE_INFO = 'info';

    public const TYPE_WARNING = 'warning';

    public const TYPE_ERROR = 'error';

    // </editor-fold>


    // <editor-fold desc="Properties">

    /**
     * @var string Optionale Überschrift für diesen Hinweis
     */
    public $title;

    /**
     * @var string Der eigentliche Hinweistext
     */
    public $message;

    /**
     * @var string Nachrichtentyp
     */
    public $type;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct($message = null, $title = null, $type = self::TYPE_INFO) {
        $this->message = $message;
        $this->title = $title;
        $this->type = $type;
    }

    // </editor-fold>
}