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
 * Bildet einen NCM-Artikel ab
 * @author agewert@ubergeek.de
 * @created 2017-10-27
 */
class Article {
    
    /**
     * Eindeutige ID des Artikels
     * @var integer
     */
    public $id;
    
    /**
     * Erstellungszeitpunkt
     * @var \DateTime
     */
    public $creationTimestamp;
    
    /**
     * Änderungs-Zeitpunkt
     * @var \DateTime
     */
    public $modificationTimestamp;

    /**
     * Benutzer-ID des Autors
     * @var integer
     */
    public $authorId;
    
    /**
     * Statuscode
     * @var integer
     */
    public $statusCode;

    /**
     * Artikelüberschrift
     * @var string
     */
    public $headline;
    
    /**
     * Optionaler Anrisstext
     * @var string
     */
    public $teaser;
    
    /**
     * Artikeltext
     * @var string
     */
    public $content;

    /**
     * Startzeitpunkt für die Freischaltung
     * @var \DateTime
     */
    public $startTimestamp;
    
    /**
     * Endzeitpunkt für die Freischaltung
     * @var \DateTime
     */
    public $stopTimestamp;
    
    /**
     * Veröffentlichungszeitpunkt
     * @var \DateTime
     */
    public $publishingTimestamp;
    
    /**
     * Freigabestatus der Trackback-Funktion für diesen Artikel
     * 
     * Die globale Einstellung für die Trackback-Funktionalität hat immer
     * Vorrang vor der artikel-bezogenen Einstellung.
     * @var bool
     */
    public $enableTrackbacks;
    
    /**
     * Freigabestatus der Kommentar-Funktion für diesen Artikel
     * 
     * Die globale Einstellung für die Kommentar-Funktionalität hat immer
     * Vorrang vor der artikel-bezogenen Einstellung.
     * @var bool
     */
    public $enableComments;
    
    /**
     * Erstellt ein Article-Objekt anhand des übergebenen PDO-Statements
     * @param \PDOStatement $stmt
     * @return Article
     */
    public final static function fetchFromPdoStmt(\PDOStatement $stmt) {
        $stmt->setFetchMode(\PDO::FETCH_CLASS, __CLASS__);
        
        if (($article = $stmt->fetch()) !== false) {
            return $article;
        }
        
        return null;
    }
}