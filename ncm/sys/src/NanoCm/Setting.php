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
 * Bildet einen Eintrag aus den nanocm-Systemeinstellungen ab
 * @author agewert@ubergeek.de
 */
class Setting
    extends \Ubergeek\KeyValuePair {

    // <editor-fold desc="Constants">

    /**
     * Gibt an, ob bei abgefangenen Fehlern die Exceptions (mit Fehlermeldung und Stacktrace) ausgegeben werden sollen.
     * Achtung: In Produktiv-Umgebungen sollte dieses Logging unbedingt ausgeschaltet werden!
     *
     * @var string
     */
    public const SYSTEM_DEBUG_SHOWEXCEPTIONS = 'system.debug.showexceptions';

    /**
     * Gibt an, ob Debug-Output per ChromeLogger-Protokoll ausgegeben werden soll.
     * Achtung: In Produktiv-Umgebungen sollte dieses Logging unbedingt ausgeschaltet werden!
     *
     * @var string
     */
    public const SYSTEM_DEBUG_ENABLECHROMELOGGER = 'system.debug.enablechromelogger';

    /**
     * Standard-Seitenlänge für Auflistungen im Administrationsbereich
     *
     * @var string
     */
    public const SYSTEM_ADMIN_PAGELENGTH = 'system.admin.pagelength';

    /**
     * Relativer Pfad zum zu benutzenden HTML-Template
     *
     * @var string
     */
    public const SYSTEM_TEMPLATE_PATH = 'system.template.path';

    /**
     * Seitentitel
     * Der Seitentitel kann beispielsweise von den Templates genutzt werden.
     *
     * @var string
     */
    public const SYSTEM_SITETITLE = 'system.pagetitle';

    /**
     * Copyright- bzw. Footer-Hinweis.
     * Diese Informationen können von den Seiten-Templates verwendet werden und etwa im Footer ausgegeben werden.
     * @var string
     */
    public const SYSTEM_COPYRIGHTNOTICE = 'system.copyrightnotice';

    /**
     * Gibt an, ob die Trackback-Funktion aktiv sein soll
     *
     * @var string
     */
    public const SYSTEM_ENABLETRACKBACKS = 'system.enabletrackbacks';

    /**
     * Systemsprache
     *
     * @var string
     */
    public const SYSTEM_LANG = 'system.lang';

    /**
     * Gibt an, ob die Kommentierung von Artikel möglich sein soll
     *
     * @var string
     */
    public const SYSTEM_ENABLECOMMENTS = 'system.enablecomments';

    /**
     * Anzeigename / Realname des Webmaster
     *
     * @var string
     */
    public const SYSTEM_WEBMASTER_NAME = 'system.webmaster.name';

    /**
     * E-Mail-Adresse des Webmasters
     *
     * @var string
     */
    public const SYSTEM_WEBMASTER_EMAIL = 'system.webmaster.email';

    /**
     * Optionale weitere URL für den Webmaster, z. B. Profil bei Twitter etc.
     *
     * @var string
     */
    public const SYSTEM_WEBMASTER_URL = 'system.webmaster.url';

    /**
     * Gibt an, ob generell Statistiken protokolliert werden sollen.
     * Wird dieser Wert auf 0 gesetzt, so werden überhaupt keine Statistiken geschrieben.
     *
     * @var string
     */
    public const SYSTEM_STATS_ENABLELOGGING = 'system.stats.enablelogging';

    /**
     * Gibt an, ob das ausführliche Accesslog geführt werden soll
     * Diese Funktion kann ausgeschaltet werden, um die Geschwindigkeit zu erhöhen oder um Platz zu sparen.
     *
     * @var string
     */
    public const SYSTEM_STATS_ENABLEACCESSLOG = 'system.stats.enableaccesslog';

    /**
     * Gibt an, ob für das Browser-Sniffing die browscap-Funktionen genutzt werden sollen.
     * Leider ist die Konfiguration von browscap nur systemweit möglich dürfte somit auf den meisten Shared Hosts nicht
     * zur Verfügung stehen.
     *
     * @var string
     */
    public const SYSTEM_STATS_ENABLEBROWSCAP = 'system.stats.enablebrowscap';

    /**
     * Diese Einstellung gibt an, ob die Statistiken Geolocation-Informationen führen sollen.
     * Wird dieser Wert eingeschaltet, so werden mit Hilfe des Services ip-api.com Geolocation-Informationen zu jedem
     * Seitenaufruf ermittelt und in die Statistiken geschrieben. Ermöglicht beispielsweise zu ermitteln, aus welchen
     * Ländern / Regionen oder gar Städten die Seitenbesucher stammen.
     *
     * @var string
     */
    public const SYSTEM_STATS_ENABLEGEOLOCATION = 'system.stats.enablegeolocation';

    // </editor-fold>


    /**
     * Optionale Parameter für diese Einstellung
     *
     * @var string
     */
    public $params = null;

    /**
     * Dem Konstruktor können optional direkt Schlüssel und Wert sowie ein
     * weiterer Parameter übergeben werden
     *
     * @param string $key Schlüssel
     * @param object $value Wert
     * @param null $params
     */
    public function __construct(string $key = null, $value = null, $params = null) {
        parent::__construct($key, $value);
        $this->params = $params;
    }

    public static function fetchFromPdoStatement(\PDOStatement $stmt) {
        if (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $setting = new Setting();
            $setting->key = $row['name'];
            $setting->value = $row['setting'];
            $setting->params = $row['params'];
            return $setting;
        }
        return null;
    }
}