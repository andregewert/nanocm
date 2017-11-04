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

namespace Ubergeek\NanoCm\Media;

/**
 * Einfache Medienverwaltung für die Verwendung im NanoCM.
 * 
 * Der MediaManager soll eine einfache Verwaltung von Content-Images
 * ermöglichen. Insbesondere soll die die Skalierung und das Anscheiden von
 * Bildern in vordefinierten Formaten übernehmen. Noch offen ist die Frage, ob
 * lediglich eine Import-Funktion bereitgestellt werden soll (erlaubt eine
 * flexiblere Verarbeitung der Bilder) oder ob auch eingebettete Inhalt
 * dynamisch von den jeweiligen Cloud-Dienstleistern geladen werden sollen.
 * 
 * (Ein Proxy-Script könnte bspw. bei jedem Abruf das angeforderter Bild vom
 * Cloud-Anbieter laden, skalieren und anschneiden.)
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2017-11-04
 */
class MediaManager {
    
}