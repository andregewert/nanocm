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

namespace Ubergeek\Controller;

/**
 * Einfaches Interface für einen Controller nach dem MVC-Schema
 * 
 * Das NanoCM ist darauf ausgelegt, ausschließlich über HTTP aufgerufen zu
 * werden.
 */
interface ControllerInterface {
    public function addMeta(string $key, string $value);
    
    public function replaceMeta(string $key, string $value);
    
    public function getMetaData() : array;
    
    public function getMeta(string $key) : array;
    
    public function setContent(string $content, string $area = 'default');
    
    public function addContent(string $content, string $area = 'default') : string;
    
    public function getContent(string $area = 'default') : string;
    
    public function getParam(string $key, $default = null);

    public function isParamExisting(string $key) : bool;

    public function getParams() : array;
    
    public function setParam(string $key, $value);
    
    public function setVar(string $key, $value);
    
    public function getVar(string $key, $default = null);

    public function isVarExisting(string $key) : bool;
    
    public function getVars() : array;
    
    public function setTitle(string $title);
    
    public function getTitle() : string;
    
    public function init();
    
    public function run();
    
    public function execute();
}