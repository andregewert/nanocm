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
 * Kapselt alle system-internen Datenbank-Funktionen in einer Klasse.
 * 
 * Alle Object-Relation-Mapping-Methoden für integrale Bestandteile des NanoCM
 * werden in dieser Klasse zur Verfügung gestellt. Eine Instanz dieses ORM ist
 * über den jeweiligen Controller in jedem Template zugänglich.
 * 
 * Optionale Zusatzmodule können ihre eigene Datenbank-Funktionalitäten über
 * eigene Klassen-Instanzen bereitstellen.
 * 
 * Zu den Grundfunktionen des NanoCM gehören:
 * 
 * - Artikelverwaltung
 * - Benutzerverwaltung
 * - Verwaltung von Kommentaren und Trackbacks
 * - Grundlegende Statistiken
 * - Systemeinstellungen
 * 
 * Zu den optionalen Modulen gehört beispielsweise die Medienverwaltung.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @todo Caching / Converting eventuell in die Controller-Klassen verschieben
 */
class Orm {

    // <editor-fold desc="Properties">

    /**
     * Handle für die Basis-Datenbank
     * @var \PDO
     */
    private $basedb;
    
    /**
     * Optionale Log-Instanz
     * @var \Ubergeek\Log\LoggerInterface
     */
    private $log;

    /**
     * Cache für den User-ID-Converter
     * @var User[]
     */
    private static $userCache;

    // </editor-fold>

    /**
     * Dem Konstruktor muss das Datenbank-Handle für die Basis-Systemdatenbank
     * übergeben werden.
     * @param \PDO $dbhandle
     * @param \Ubergeek\Log\LoggerInterface|null $log
     */
    public function __construct(\PDO $dbhandle, \Ubergeek\Log\LoggerInterface $log = null) {
        $this->basedb = $dbhandle;
        $this->log = $log;
        
        if ($this->log == null) {
            $this->log = new \Ubergeek\Log\Logger();
        }
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
     * @param mixed $default Optionaler Standard-Rückgabewert
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
     * @param mixed $default Optionaler Standard-Rückgabewert
     * @return mixed Der gesuchte Wert oder der vorgegebene Standard-Wert
     */
    public function getSettingParams(string $name, $default = null) {
        $setting = $this->getSetting($name);
        if ($setting == null) return $default;
        return $setting->params;
    }

    /**
     * Durchsucht die Systemeinstellungen und gibt ein Array mit den gefundenen
     * Datensätzen zurück
     * @param Setting|null $filter
     * @param integer $limit
     * @return Setting[]
     */
    public function searchSettings(Setting $filter = null, $limit = null) {
        $settings = array();

        $sql = 'SELECT * FROM setting WHERE 1 = 1 ';

        // TODO Filter auswerten

        $sql .= ' ORDER BY name ASC ';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        $stmt = $this->basedb->prepare($sql);
        $stmt->execute();

        while (($setting = Setting::fetchFromPdoStatement($stmt)) !== null) {
            $settings[] = $setting;
        }

        return $settings;
    }
    
    // </editor-fold>
    
    
    // <editor-fold desc="User">

    /**
     * Setzt das Passwort für einen bestimmten Benutzer
     * @param int $id Benutzer-ID
     * @param string $password Neues Passwort
     * @return bool true bei Erfolg
     */
    public function setUserPasswordById(int $id, string $password) : bool {
        $stmt = $this->basedb->prepare('
            UPDATE user SET password = :password, modification_timestamp = CURRENT_TIMESTAMP WHERE id = :id
        ');
        $stmt->bindValue('password', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Setzt das Passwort für einen bestimmten Benutzer
     * @param string $username Benutzername
     * @param string $password Neues Passwort
     * @return bool true bei Erfolg
     */
    public function setUserPasswordByUsername(string $username, string $password) : bool {
        $stmt = $this->basedb->prepare('
            UPDATE user SET password = :password, modification_timestamp = CURRENT_TIMESTAMP WHERE username = :username
        ');
        $stmt->bindValue('password', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindValue('username', $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Durchsucht die Benutzerdatenbank nach flexiblen Filterkriterien
     * @param array $filter = null
     * @return array Liste der gefundenen Benutzerdatensätze
     */
    public function searchUsers(array $filter = null) {
        // TODO Implementieren
        return array();
    }

    /**
     * Gibt - falls vorhanden - den Benutzer-Datensatz mit der angegebenen ID
     * zurück
     * 
     * Kann der angefordrte Benutzer-Datensatz nicht gefunden werden, so wird
     * NULL zurück gegeben.
     * @param int $id Benutzer-ID
     * @param bool $includeInactive Auf true setzen, wenn auch inaktive Konten
     *  berücksichtigt werden sollen
     * @return User Gesuchter Benutzer-Datensatz oder NULL
     */
    public function getUserById(int $id, bool $includeInactive) {
        $sql = 'SELECT * FROM User WHERE id = :userid ';
        if (!$includeInactive) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('userid', $id);
        $stmt->execute();
        
        return User::fetchFromPdoStmt($stmt);
    }
    
    /**
     * Gibt - falls vorhanden - den Benutzer-Datensatz mit dem angegebenen
     * Benutzernamen zurück
     * 
     * Kann der angeforderte Benutzer-Datensatz nicht gefunden werden, so wird
     * NULL zurück gegeben.
     * @param string $username Benutzername
     * @param bool $includeInactive Auf true setzen, wenn auch inaktive Konten
     *  berücksichtigt werden sollen
     * @return User Gesuchter Benutzer-Datensatz oder NULL
     */
    public function getUserByUsername(string $username, bool $includeInactive) {
        $sql = 'SELECT * FROM user WHERE username = :username ';
        if (!$includeInactive) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('username', $username);
        $stmt->execute();
        
        return User::fetchFromPdoStmt($stmt);
    }

    /**
     * Gibt - sofern Benutzername und Passwort mit den Werten in der Datenbank
     * übereinstimmen - den gesuchten Benutzer-Datensatz zurück
     * 
     * Kann der Datensatz nicht gefunden werden oder stimmen übergebenes und
     * gespeichertes Passwort nicht überein, so wird NULL zurück gegeben. Der
     * Grund für eine nicht erfolgreiche Abfrage wird nicht mitgeteilt.
     * 
     * @param string $username Gesuchter Benutzername
     * @param string $passwd Eingegebenes bzw. bekanntes Passwort
     * @return User Gesuchter Benutzer-Datensatz oder NULL
     */
    public function getUserByCredentials(string $username, string $passwd) {
        $user = $this->getUserByUsername($username, false);
        if ($user != null) {
            if (password_verify($passwd, $user->password)) return $user;
        }
        return null;
    }
    
    /**
     * Speichert den übergebenen Benutzer-Datensatz in der Datenbank
     * @param \Ubergeek\NanoCm\User $user
     */
    public function saveUser(User $user) {
        // TODO Implementieren
    }
    
    // </editor-fold>


    // <editor-fold desc="Tag">

    /**
     * Gibt eine Liste aller definierten Schlagworte zurück
     * @return Tag[]
     */
    public function getTags() {
        $tags = array();

        $sql = 'SELECT * FROM tag ORDER BY title ASC';
        $stmt = $this->basedb->prepare($sql);
        $stmt->execute();

        while (($tag = Tag::fetchFromPdoStatement($stmt)) !== null) {
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Gibt ein Array mit den Tags (in Form von Strings) zurück,
     * die einem bestimmten Artikel zugeordnet sind
     * @param $articleId Artikel-ID
     * @return string[] Array der zugewiesenen Tags
     */
    public function getTagsByArticleId($articleId) {
        $tags = array();

        $sql = 'SELECT tag.title FROM tag_article LEFT JOIN tag
                ON tag.id = tag_article.tag_id
                WHERE tag_article.article_id = :article_id';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->execute();

        while (($tag = $stmt->fetchColumn()) !== false) {
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Weist die übergebenen Tags einem bestimmten Artikel zu
     *
     * Die zuzuweisenden Tags werden lediglich als Strings übergeben,
     * nicht etwa als Tag-Objekte.
     * @param $articleId Artikel-ID
     * @param string[] $tags Liste der zuzuweisenden Tags
     * @return void
     */
    public function assignTagsToArticle($articleId, array $tags) {
        $existingTags = $this->getTagsByArticleId($articleId);
        $toInsert = array_diff($tags, $existingTags);
        $toDelete = array_diff($existingTags, $tags);

        foreach ($toInsert as $insert) {

        }

        foreach ($toDelete as $delete) {

        }
    }

    public function assignTagToArticle($articleId, string $tag) {
        //$sql = 'REPLACE INTO tag_article () VALUES (:article_id, :tag_id) ';
    }

    public function saveTag(string $tag) {
        $sql = 'REPLACE INTO tag (title) VALUES (:tag) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('tag', $tag);
        $stmt->execute();
    }

    // </editor-fold>

    
    // <editor-fold desc="Article">
    
    /**
     * Durchsucht die Artikel nach bestimmten Filterkriterien
     * @param \Ubergeek\NanoCm\Article $filter Optionale Suchfilter
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Artikel
     * berücksichtig werden sollen
     * @param integer|null $page Angeforderte Seite
     * @param integer|null $limit Maximale Anzahl der zurück zu gebenden Artikel
     * @return array Ein Array mit den gefundenen Artikeln
     */
    public function searchArticles(Article $filter = null, $releasedOnly = true, $page = null, $limit = null) {
        $articles = array();
        
        $sql = 'SELECT * FROM article WHERE 1 = 1 ';
        
        if ($releasedOnly) {
            $sql .= '
                AND (
                    start_timestamp <= CURRENT_TIMESTAMP
                    AND (stop_timestamp IS NULL OR stop_timestamp >= CURRENT_TIMESTAMP)
                )
                AND status_code = ' . StatusCode::ACTIVE . ' ';
        }
        
        // Filterbedingungen einfügen
        if (is_array($filter)) {
            // TODO implementieren
        }
        
        $sql .= 'ORDER BY publishing_timestamp DESC ';

        // Aufteilung in Seiten
        if ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        $stmt = $this->basedb->prepare($sql);
        $stmt->execute();

        while (($article = Article::fetchFromPdoStatement($stmt)) !== null) {
            $articles[] = $article;
        }

        return $articles;
    }

    /**
     * Liest den Artikel mit der angegebenen ID aus und gibt ein entsprechendes Objekt
     * oder null zurück.
     * @param int $id ID des angeforderten Artikels
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Artikel berücksichtigt werden sollen
     * @return Article|null
     */
    public function getArticleById(int $id, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM article WHERE id = :id ';
        if ($releasedOnly) {
            $sql .= ' AND status_code = ' . StatusCode::ACTIVE;
        }

        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();

        return Article::fetchFromPdoStatement($stmt);
    }
    
    /**
     * Gibt die neuesten freigeschalteten Artikel zurück
     * @param int $limit Maximale Anzahl zurückzugebender Artikel
     * @return Article[]
     */
    public function getLatestArticles(int $limit = 5) {
        return $this->searchArticles(null, true, $limit);
    }
    
    public function saveArticle(Article $article) {
        // TODO Implementieren
    }
    
    // </editor-fold>


    // <editor-fold desc="Page">

    /**
     * Durchsucht die Pages nach verschiedenen Filterkriterien
     * @param array $filter
     * @param bool $releasedOnly
     * @param integer|null $limit
     * @return Page[]
     */
    public function searchPages($filter = null, bool $releasedOnly = true, $limit = null) {
        $pages = array();

        $sql = 'SELECT * FROM page WHERE 1 = 1 ' ;

        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }

        if ($limit !== null) {
            $limit = intval($limit);
            $sql .= " LIMIT $limit ";
        }

        $sql .= 'ORDER BY url ASC ';

        $stmt = $this->basedb->prepare($sql);
        $stmt->execute();

        while (($page = Page::fetchFromPdoStatement($stmt)) !== false) {
            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * Liest die Page mit der angegebenen ID aus und gibt ein entsprechendes Objekt zurück
     * @param integer $id ID der gesuchten Page
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Pages berücksichtigt werden sollen
     * @return Page|null
     */
    public function getPageById(int $id, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM page WHERE id = :id ';
        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        return Page::fetchFromPdoStatement($stmt);
    }

    /**
     * Liest die Page mit der angegebenen URL aus und gibt ein entsprechendes Objekt zurück
     * @param string $url URL der gesuchten Page
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Pages berücksichtigt werden sollen
     * @return Page|null
     */
    public function getPageByUrl(string $url, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM page WHERE url = :url ';
        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        $this->log->debug($sql);
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('url', $url);
        $this->log->debug($url);
        $stmt->execute();
        return Page::fetchFromPdoStatement($stmt);
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
    
    /**
     * Gibt den Standard-Seitentitel zurück.
     * 
     * Wenn der Seitentitel nicht ermittelt werden kann (weil beispielsweise
     * noch keine Datenbank vorhanden ist), so wird ein Vorgabetitel zurück
     * gegeben.
     * @return string Seitentitel
     */
    public function getSiteTitle() : string {
        $title = 'NanoCM';
        try {
            $title = $this->getSettingValue(Constants::SETTING_SYSTEM_SITETITLE);
        } catch (\Exception $ex) {
            $this->log->debug($ex);
        }
        return $title;
    }
    
    // </editor-fold>


    // <editor-fold desc="Converter methods">


    /**
     * Konvertiert eine Benutzer-ID in den zugehörigen Anzeigenamen
     * @param int $userId
     * @return string
     */
    public function convertUserIdToName(int $userId) : string {
        $user = $this->getCachedUser($userId);
        if ($user == null) return '';
        return $user->getFullName();
    }

    public function convertStatusId($statusId) : string {
        switch ($statusId) {
            case 0:
                return 'Online';
            case 9:
                return 'Gesperrt';
        }
        return 'Unbekannt';
    }

    /**
     * Gibt einen ggf. gecachten Benutzer-Datensatz zurück.
     *
     * Dieser Cache wird für Konvertierungen im User Interface verwendet und berücksichtigt auch immer alle
     * Benutzer-Datensätze unabhängig von ihrem Status.
     * Alle anderen Funktionen sollten Benutzerdatensätze grundsätzlich in "Echtzeit" überprüfen,
     * d. h. immer aktuelle Informationen aus der Datenbank beziehen.
     * @param int $userId
     * @return User|null
     */
    public function getCachedUser(int $userId) {
        if (!is_array(self::$userCache)) {
            self::$userCache = array();
        }
        if (array_key_exists($userId, self::$userCache)) {
            $this->log->debug("Found user id $userId in cache");
            return self::$userCache[$userId];
        }

        $user = $this->getUserById($userId, true);
        if ($user == null) return null;

        self::$userCache[$userId] = $user;
        return $user;
    }

    // </editor-fold>
}