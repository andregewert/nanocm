<?php

/* 
 * Copyright (C) 2017 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
    
    public function getParam(string $key, $default = null) : any;
    
    public function getParams() : array;
    
    public function setParam(string $key, any $value);
    
    public function setVar(string $key, any $value);
    
    public function getVar(string $key, $default = null) : any;
    
    public function getVars() : array;
    
    // Programm-Fluss
    
    public function init();
    
    public function run();
    
    public function execute();
}