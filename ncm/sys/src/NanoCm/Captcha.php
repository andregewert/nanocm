<?php
// NanoCM
// Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

namespace Ubergeek\NanoCm;

/**
 * Simple captcha implementation
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@gmail.com>
 * @created 2018-12-02
 */
class Captcha {

    // <editor-fold desc="Properties">

    /**
     * @var int First operand
     */
    public $valueA;

    /**
     * @var int Second operand
     */
    public $valueB;

    /**
     * @var string Operator (+ or -)
     */
    public $operator;

    /**
     * @var string Unique captcha id
     */
    public $captchaId;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    /**
     * Der Konstruktor erstellt einen Captcha mit
     * zufälligen Werten
     */
    public function __construct() {
        $this->operator = (rand(0, 1) == 1)? '-' : '+';

        if ($this->operator == '-')
            $this->valueA = rand(3, 10);
        else
            $this->valueA = rand(1, 10);

        do {
            $this->valueB = rand(1, 10);
        } while (
            $this->valueA == $this->valueB
            || ($this->operator == '-' && $this->valueB > $this->valueA)
        );

        if (function_exists('random_bytes')) {
            $this->captchaId = md5(random_bytes(1096));
        } else {
            $this->captchaId = md5(rand());
        }
    }

    // </editor-fold>
}