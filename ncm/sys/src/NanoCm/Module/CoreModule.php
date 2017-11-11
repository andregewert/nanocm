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

namespace Ubergeek\NanoCm\Module;

/**
 * Die Basisklasse für NCM-Module versteckt einige Implementierungsdetails und
 * stellt ableitenden Klassen genau die Referenzen und Methoden bereit, die
 * häufig genutzt werden.
 * 
 * Über die referenzierten Objekte ist es den Implementierungen, also konkreten
 * Modulen, immer noch möglich, auf alle Teile des Software-Systems (bspw. den
 * ausführenden FrontController) zuzugreifen.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-11
 */
abstract class CoreModule {

    /**
     * Referenz auf den ausführenden FrontController.
     * 
     * Über diese Referenz sind auch alle anderen benötigten Ressourcen
     * zugänglich. Dazu gehören bspw. Datenbank-Mapping und Medien-Manager.
     * 
     * @var \Ubergeek\NanoCm\FrontController
     */
    public $frontController = null;

    /**
     * Referenz auf die NanoCM-Instanz
     * @var \Ubergeek\NanoCm\NanoCm
     */
    public $ncm = null;
    
    /**
     * Referenz auf den OR-Mapper
     * @var \Ubergeek\NanoCm\Orm
     */
    public $orm = null;
    
    /**
     * Referenz auf eine Logger-Instanz
     * @var \Ubergeek\Log\Logger
     */
    public $log = null;
    
    public function __construct(\Ubergeek\NanoCm\FrontController $frontController) {
        $this->frontController = $frontController;
        $this->ncm = $frontController->ncm;
        $this->orm = $frontController->ncm->orm;
        $this->log = $frontController->ncm->log;
    }
    
    public abstract function run();

}