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

namespace Ubergeek\Epub\Exception;

/**
 * Wird geworfen, wenn versucht wird, einen Inhalt mit einer bereits verwendeten Content-ID hinzuzufügen
 *
 * Bei der manuellen Vergabe von Content-IDs kann es, je nach Herangehensweise, schwierig sein, eindeutige
 * Content-IDs zu generieren. Um das zu vermeiden, bietet die Content-Klasse auch die Möglichkeit, automatische
 * Content-IDs zu vergeben.
 *
 * @package Ubergeek\Epub\Exception
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-01-07
 */
class DuplicateContentIdException
    extends \RuntimeException
    implements ExceptionInterface {

}