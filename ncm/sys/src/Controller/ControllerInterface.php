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

interface ControllerInterface {
    public function addMeta(string $key, string $value);
    
    public function replaceMeta(string $key, string $value);
    
    public function setContent(string $content);
    
    public function addContent(string $content) : string;
    
    public function getContent() : string;
    
    public function getParam(string $key) : any;
    
    public function getParams() : array;
    
    public function setParam(string $key, any $value);
    
    public function setVar(string $key, any $value);
    
    public function getVar(string $key) : any;
    
    public function getVars() : array;
    
    // Programm-Fluss
    
    public function init();
    
    public function run();
    
    public function execute();
}