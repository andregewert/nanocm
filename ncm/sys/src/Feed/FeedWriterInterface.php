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

namespace Ubergeek\Feed;

/**
 * Bildet ein generisches Interface zum Schreiben (Erstellen) von Feed-Dateien ab
 *
 * @todo Eventuell lässt sich dieses Interface auch brauchbar für die Generierung von ePub-Dateien nutzen
 * @package Ubergeek\Feed
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-09
 */
interface FeedWriterInterface {

    /**
     * Erstellt für das übergebene Feed-Objekt das gewünschte Ausgabeformat
     *
     * @param Feed $feed Der zu wandelnde Feed
     * @return string Das Ergebnis im gewünschten Ausgabeformat
     */
    public function writeFeed(Feed $feed) : string;

}