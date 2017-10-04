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

namespace Ubergeek\NanoCm;
use Ubergeek\Log;

/**
 * Kapselt alle system-internen Datenbank-Funktionen in einer Klasse.
 * @author agewert@ubergeek.de
 */
class DbMapper {
    
    /**
     * Handle für die Basis-Datenbank
     * @var \PDO
     */
    private $basedb;
    
    /**
     * Dem Konstruktor muss das Datenbank-Handle für die Basis-Systemdatenbank
     * übergeben werden.
     * @param \PDO $dbhandle
     */
    public function __construct(\PDO $dbhandle) {
        $this->basedb = $dbhandle;
    }
    
    
    // <editor-fold desc="Settings">

    /**
     * Liest einen Systemeinstellungs-Datensatz aus
     * @param string $name Name der gesuchten Einstellung
     * @return \Ubergeek\NanoCm\Setting Die gesuchte Einstellung oder null
     */
    public function getSetting(string $name) {
        $sql = 'SELECT * FROM setting WHERE name = :name ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('name', $name);
        $stmt->execute();
        
        if (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $setting = new Setting();
            $setting->key = $row['name'];
            $setting->value = $row['setting'];
            $setting->params = $row['params'];
            return $setting;
        }
        
        return null;
    }
    
    /**
     * Gibt (nur) den String-Wert einer Systemeinstellung zurück.
     * Ist die angeforderte Einstellung nicht definiert, kann über den optionalen
     * zweiten Parameter bestimmt werden, welcher Wert in diesem Fall zurück
     * gegeben werden soll.
     * @param string $name Name der gesuchten Einstellung
     * @param type $default Optionaler Standard-Rückgabewert
     * @return mixed Der gesuchte Wert oder der vorgegebene Standard-Wert
     */
    public function getSettingValue(string $name, $default = null) {
        $setting = $this->getSetting($name);
        if ($setting == null) return $default;
        return $setting->value;
    }
    
    /**
     * Gibt (nur) die Parameter einer Systemeinstellung zurück.
     * Ist die angeforderte Einstellung nicht definiert, kann über den optionalen
     * zweiten Parameter bestimmt werden, welcher Wert in diesem Fall zurück
     * gegeben werden soll.
     * @param string $name Name der gesuchten Einstellung
     * @param type $default Optionaler Standard-Rückgabewert
     * @return mixed Der gesuchte Wert oder der vorgegebene Standard-Wert
     */
    public function getSettingParams(string $name, $default = null) {
        $setting = $this->getSetting($name);
        if ($setting == null) return $default;
        return $setting->params;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="Shortcut methods">
    
    /**
     * Gibt den Copyright-Hinweis / die Footer-Notiz für die Website zurück
     * @return string
     */
    public function getCopyrightNotice() {
        return $this->getSettingValue(Constants::SETTING_SYSTEM_COPYRIGHTNOTICE, '');
    }
    
    // </editor-fold>
}