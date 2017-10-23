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

/**
 * Enthält eine Reihe von Standard-Konstanten, um den Zugriff auf vordefinierte
 * System-Variablen und -Einstellungen zu erleichtern
 */
abstract class Constants {
    
    // <editor-fold desc="Keys für die Einstellungs-Datenbank">

    /**
     * Systemsprache
     */
    const SETTING_SYSTEM_LANG = 'system.lang';
    
    /**
     * Relativer Pfad zum zu benutzenden HTML-Template
     */
    const SETTING_SYSTEM_TPLDIR = 'system.tpldir';
    
    /**
     * Seitentitel
     */
    const SETTING_SYSTEM_PAGETITLE = 'system.pagetitle';
    
    /**
     * Copyright- bzw. Footer-Hinweis
     */
    const SETTING_SYSTEM_COPYRIGHTNOTICE = 'system.copyrightnotice';

//    /**
//     * Anzeigename / Realname des Webmaster
//     */
//    const SETTING_SYSTEM_WEBMASTER_NAME = 'system.webmaster.name';
//    
//    /**
//     * E-Mail-Adresse des Webmasters
//     */
//    const SETTING_SYSTEM_WEBMASTER_EMAIL = 'system.webmaster.email';
//
//    /**
//     * Optionale weitere URL für den Webmaster, z. B. Profil bei Twitter etc.
//     */
//    const SETTING_SYSTEM_WEBMASTER_URL = 'system.webmaster.url';
//    
//    /**
//     * Passwort für den Administrationszugang
//     */
//    const SETTING_SYSTEM_WEBMASTER_PASSWD = 'system.webmster.passwd';
    
    // </editor-fold>
    
    
    // <editor-fold desc="Variablen, die im NanoCmController soie in den Templates bereitstehen">

    /**
     * Die absolute URL der Installationsbasis bzw. zum Wurzelverzeichnis.
     * Die URL ist absolut zum Server-Root.
     */
    const VAR_URL_ROOT = 'url.root';
    
    /**
     * Die absolute URL zum Unterverzeichnis "ncm", in dem sich das vollständige
     * Content Management System befindet.
     */
    const VAR_URL_NCM = 'url.ncm';
    
    /**
     * Der abolsute Dateipfad zur Installationsbasis
     */
    const VAR_PATH_ROOT = 'path.root';
    
    /**
     * Der absolute Dateipfad zum Unterverzeichnis "ncm"
     */
    const VAR_PATH_NCM = 'path.ncm';
    
    // </editor-fold>
    
}