<?php

/**
 * NanoCM
 * Copyright (C) 2018 André Gewert <agewert@ubergeek.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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
     * Seitenlänge für Suchergebnisse
     * @var int
     */
    public $pageLength = 5;

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
     * @param Setting|null $filter Filterkriterien
     * @param string|null $searchterm Optionaler Suchbegriff
     * @param int $limit Optionales Limit für das Suchergebnis
     * @param int $offset Optionaler Startwert für das Suchergebnis
     * @return Setting[]
     */
    public function searchSettings(Setting $filter = null, $searchterm = null, $limit = null, $offset = null) {
        $settings = array();
        $params = array();

        // SQL zusammenstellen
        $sql = 'SELECT * FROM setting WHERE 1 = 1 ';
        if ($filter instanceof Setting) {
            // TODO implementieren
        }

        // Feier Suchbegriff
        if ($searchterm !== null) {
            $sql .= ' AND name LIKE :name ';
            $params['name'] = "%$searchterm%";
        }
        $sql .= ' ORDER BY name ASC ';

        // Limit berücksichtigen
        if ($limit !== null && $offset !== null) {
            $limit = intval($limit);
            $offset = intval($offset);
            $sql .= " LIMIT $offset, $limit ";
        } elseif ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        // Parameter setzen
        $stmt = $this->basedb->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
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
            UPDATE user SET password = :password, modification_timestamp = datetime(CURRENT_TIMESTAMP, \'localtime\') WHERE id = :id
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
            UPDATE user SET password = :password, modification_timestamp = datetime(CURRENT_TIMESTAMP, \'localtime\') WHERE username = :username
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
            $this->log->debug("Assigning tag $insert");
            $this->assignTagToArticle($articleId, $insert);
        }

        foreach ($toDelete as $delete) {
            $this->log->debug("Unassign tag $delete");
            $this->unassignTagFromArticle($articleId, $delete);
        }
    }

    /**
     * Weist das übergebene Schlagwort einem bestimmten Artikel hinzu
     * @param int $articleId Artikel-ID
     * @param string $title Zuzuweisendes Schlagwort
     * @return void
     */
    public function assignTagToArticle(int $articleId, string $title) {
        if (!empty(trim($title))) {
            $tagId = $this->saveTag($title);
            if ($tagId > 0) {
                $this->assignTagIdToArticle($articleId, $tagId);
            }
        }
    }

    /**
     * Ordnet das Schlagwort mit der angegebenen ID einem Artikel hinzu
     * @param int $articleId Artikel-ID
     * @param int $tagId Tag-ID
     * @return void
     */
    public function assignTagIdToArticle(int $articleId, int $tagId) {
        $sql = 'REPLACE INTO tag_article (article_id, tag_id) VALUES (:article_id, :tag_id)';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->bindValue('tag_id', $tagId);
        $stmt->execute();
    }

    /**
     * Entfernt (falls vorhanden) die Zuweisung eines Schlagwortes von einem
     * bestimmten Artikel
     * @param int $articleId Artikel-ID
     * @param string $title Schlagwort
     * @return void
     */
    public function unassignTagFromArticle(int $articleId, string $title) {
        $tagId = $this->getTagIdByTitle($title);
        $this->unassignTagIdFromArticle($articleId, $tagId);
    }

    /**
     * Entfernt (falls vorhanden) die Zuweisung des Schlagwortes mit der angegebenen ID
     * von einem bestimmten Artikel
     * @param int $articleId Artikel-ID
     * @param int $tagId Tag-ID
     * @return void
     */
    public function unassignTagIdFromArticle(int $articleId, int $tagId) {
        $sql = 'DELETE FROM tag_article WHERE article_id = :article_id AND tag_id = :tag_id';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->bindValue('tag_id', $tagId);
        $stmt->execute();
    }

    /**
     * Entfernt alle Tag-Zuweisungen von einem bestimmten Artikel
     * @param int $articleId Datensatz-ID des betreffenden Artikels
     */
    public function unassignTagsFromArticle(int $articleId) {
        $sql = 'DELETE FROM tag_article WHERE article_id = :article_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('article_id', $articleId);
        $stmt->execute();
    }

    /**
     * Speichert ein Schlagwort in der Datenbank und gibt die Datensatz-ID zurück
     *
     * Wenn die Datensatz-ID nicht korrekt ermittelt werden kann
     * (mögliche Gründe?), so wird 0 zurück gegeben.
     * @param string $title
     * @return int
     */
    public function saveTag(string $title) {
        $sql = 'REPLACE INTO tag (title) VALUES (:title) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('title', $title);
        $stmt->execute();

        return $this->getTagIdByTitle($title);
    }

    /**
     * Ermittelt die Datensatz-ID für das angegebene Schlagwort.
     *
     * Wenn die ID nicht ermittelt werden kann (etwa weil das Schlagwort
     * nicht in der Datenbank vorhanden ist), so wird null zurück
     * gegeben.
     * @param string $title Gesuchtes Schlagwort
     * @return int|null
     */
    public function getTagIdByTitle(string $title) {
        $sql = 'SELECT id FROM tag WHERE LOWER(title) = :title ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('title', mb_strtolower($title));
        $stmt->execute();
        $id = $stmt->fetchColumn();

        if ($id === false) return null;
        return $id;
    }

    // </editor-fold>

    
    // <editor-fold desc="Article">
    
    /**
     * Durchsucht die Artikel nach bestimmten Filterkriterien
     *
     * Wenn der Parameter $countonly auf true gesetzt wird, werden die Parameter $page und $limit nicht mehr
     * berücksichtigt.
     *
     * @param \Ubergeek\NanoCm\Article $filter Optionale Suchfilter
     * @param bool $releasedOnly Gibt an, ob ausschließlich freigeschaltete Artikel
     * berücksichtig werden sollen
     * @param string $searchterm Freier Suchbegriff
     * @param bool $countonly Gibt an, ob das Suchergebnis oder die Antahl der Treffer zurückgegeben werden sollen
     * @param int|null $page Angeforderte Seite
     * @param int|null $limit Maximale Anzahl der zurück zu gebenden Artikel
     * @return array Ein Array mit den gefundenen Artikeln
     */
    public function searchArticles(Article $filter = null, $releasedOnly = true, $searchterm = null, $countonly = false, $page = null, $limit = null) {
        $articles = array();
        $params = array();
        $limit = ($limit == null)? $this->pageLength : intval($limit);

        // Ergebnis oder Anzahl Ergebnisse
        if ($countonly) {
            $sql = 'SELECT COUNT(*) ';
        } else {
            $sql = 'SELECT * ';
        }
        $sql .= ' FROM article WHERE 1 = 1 ';

        // Nur veröffentlichte Artikel berücksichtigen
        if ($releasedOnly) {
            $sql .= '
                AND (
                    start_timestamp <= datetime(CURRENT_TIMESTAMP, \'localtime\')
                    AND (stop_timestamp IS NULL OR stop_timestamp >= datetime(CURRENT_TIMESTAMP, \'localtime\'))
                )
                AND status_code = ' . StatusCode::ACTIVE . ' ';
        }
        
        // Filterbedingungen einfügen
        if ($filter instanceof Article) {
            if ($filter->status_code != null) {
                $sql .= ' AND status_code = :status_code ';
                $params['status_code'] = $filter->status_code;
            }
        }

        // Suchbegriff
        if (!empty($searchterm)) {
            $like = '%' . $searchterm . '%';
            $sql .= ' AND (headline LIKE :search_headline
                        OR content LIKE :search_content) ';
            $params['search_headline'] = $like;
            $params['search_content'] = $like;
        }

        // Begrenzung der Ergebnismenge auf Anzeigeseiten
        if (!$countonly) {
            $sql .= 'ORDER BY start_timestamp DESC ';
            $page = intval($page) -1;
            if ($page < 0) $page = 0;
            $offset = intval($page) * $this->pageLength;
            $sql .= " LIMIT $offset, $limit ";
        }

        $this->log->debug($sql);
        $stmt = $this->basedb->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        if ($countonly) {
            return $stmt->fetchColumn();
        }

        // Ergerbnis auslesen
        while (($article = Article::fetchFromPdoStatement($stmt)) !== null) {
            $article->tags = $this->getTagsByArticleId($article->id);
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
     * @todo Verknüpfte komplexe Daten (Autor etc.) müssen auch ausgelesen werden
     */
    public function getArticleById(int $id, bool $releasedOnly = true) {
        $sql = 'SELECT * FROM article WHERE id = :id ';
        if ($releasedOnly) {
            $sql .= ' AND status_code = ' . StatusCode::ACTIVE;
        }

        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();

        if (($article = Article::fetchFromPdoStatement($stmt)) !== null) {
            $article->tags = $this->getTagsByArticleId($article->id);
        }
        return $article;
    }
    
    /**
     * Gibt die neuesten freigeschalteten Artikel zurück
     * @param int $limit Maximale Anzahl zurückzugebender Artikel
     * @return Article[]
     */
    public function getLatestArticles(int $limit = 5) {
        return $this->searchArticles(null, true, null, false, 0, $limit);
    }

    /**
     * Speichert einen Artikel in der Datenbank
     * @param Article $article Artikeldaten
     * @return int Datensatz-ID
     * @todo Zugriffsrechte prüfen
     */
    public function saveArticle(Article $article) {
        // Artikel aktualisieren
        if ($article->id > 0) {
            $this->updateArticle($article);
        }

        // Artikel hinzufügen
        else {
            $article->id = $this->insertArticle($article);
        }

        return $article->id;
    }

    /**
     * Löscht den Artikel mit der angegebenen ID
     * @param $id Datensatz-ID des zu löschenden Artikels
     * @return bool
     */
    public function deleteArticleById($id) {
        try {
            $this->unassignTagsFromArticle($id);
            $sql = 'DELETE FROM article WHERE id = :article_id ';
            $stmt = $this->basedb->prepare($sql);
            $stmt->bindValue('article_id', $id);
            $stmt->execute();
        } catch (\Exception $ex) {
            $this->log->err('Fehler beim Löschen des Artikels', $ex);
            return false;
        }
        return true;
    }

    /**
     * Löscht mehrere Artikel anhand ihrer Datensatz-IDs
     * @param array $ids IDs der zu löschenden Artikel
     * @return void
     */
    public function deleteArticlesById(array $ids) {
        foreach ($ids as $id) {
            $this->deleteArticleById($id);
        }
    }

    /**
     * Sperrt die Artikel mit den übergebenen IDs
     * @param array $ids IDs der zu sperrenden Artikel
     * @return void
     */
    public function lockArticlesById(array $ids) {
        $sql = 'UPDATE article SET status_code = :status_code WHERE id = :article_id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', StatusCode::LOCKED);

        foreach ($ids as $article_id) {
            $stmt->bindParam('article_id', $article_id);
            $stmt->execute();
        }
    }

    /**Datensatz
     * Aktualisiert einen Artikel-Datensatz.
     *
     * Wichtig: Der Freigabe-Status wird über die Update-Methode niemals
     * verändert! Die Veröffentlichung und das Löschen eines Artikels muss
     * (gegebenenfalls im Anschluss an das eigentliche Speichern) über
     * separate Methodenaufrufe erfolgen!
     *
     * @param Article $article
     */
    private function updateArticle(Article $article) {
        $this->log->debug("Update article");
        $this->log->debug($article);

        $article->modification_timestamp = new \DateTime();

        $sql = 'UPDATE article SET 
                    modification_timestamp = datetime(CURRENT_TIMESTAMP, \'localtime\'),
                    status_code = :status_code,
                    headline = :headline,
                    teaser = :teaser,
                    content = :content,
                    start_timestamp = :start_timestamp,
                    stop_timestamp = :stop_timestamp,
                    publishing_timestamp = :publishing_timestamp,
                    enable_trackbacks = :enable_trackbacks,
                    enable_comments = :enable_comments 
                WHERE
                    id = :id ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('status_code', $article->status_code);
        $stmt->bindValue('headline', $article->headline);
        $stmt->bindValue('teaser', $article->teaser);
        $stmt->bindValue('content', $article->content);
        if ($article->start_timestamp != null) {
            $stmt->bindValue('start_timestamp', $article->start_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('start_timestamp', null);
        }
        if ($article->stop_timestamp != null) {
            $stmt->bindValue('stop_timestamp', $article->stop_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('stop_timestamp', null);
        }
        if ($article->publishing_timestamp != null) {
            $stmt->bindValue('publishing_timestamp', $article->publishing_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('publishing_timestamp', null);
        }
        $stmt->bindValue('enable_trackbacks', $article->enable_trackbacks);
        $stmt->bindValue('enable_comments', $article->enable_comments);
        $stmt->bindValue('id', $article->id);
        $stmt->execute();

        // Verknüpfte Daten speichern
        $this->assignTagsToArticle($article->id, $article->tags);
    }

    /**
     * Speichert den übergebenen Artikel als neuen Datensatz
     * @param Article $article Der zu speichernde Artikel
     * @return int Die generierte Artikel-ID
     */
    private function insertArticle(Article $article) {
        $this->log->debug("Add article");
        $this->log->debug($article);

        // Grundlegende Validierung
        if ($article->start_timestamp == null) {
            $article->start_timestamp = new \DateTime();
        }
        $article->modification_timestamp = new \DateTime();

        $sql = 'INSERT INTO article (
                  creation_timestamp, modification_timestamp, author_id,
                  status_code, headline, teaser, content, start_timestamp,
                  stop_timestamp, publishing_timestamp, enable_trackbacks,
                  enable_comments
              ) VALUES (
                  datetime(CURRENT_TIMESTAMP, \'localtime\'), datetime(CURRENT_TIMESTAMP, \'localtime\'), :author_id,
                  :status_code, :headline, :teaser, :content, :start_timestamp,
                  :stop_timestamp, :publishing_timestamp, :enable_trackbacks,
                  :enable_comments
              ) ';
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('author_id', $article->author_id);
        $stmt->bindValue('status_code', $article->status_code);
        $stmt->bindValue('headline', $article->headline);
        $stmt->bindValue('teaser', $article->teaser);
        $stmt->bindValue('content', $article->content);
        $stmt->bindValue('start_timestamp', $article->start_timestamp->format('Y-m-d H:i'));
        if ($article->stop_timestamp != null) {
            $stmt->bindValue('stop_timestamp', $article->stop_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('stop_timestamp', null);
        }
        if ($article->publishing_timestamp != null) {
            $stmt->bindValue('publishing_timestamp', $article->publishing_timestamp->format('Y-m-d H:i'));
        } else {
            $stmt->bindValue('publishing_timestamp', null);
        }
        $stmt->bindValue('enable_trackbacks', ($article->enable_trackbacks)? 1 : 0);
        $stmt->bindValue('enable_comments', ($article->enable_comments)? 1 : 0);
        $stmt->execute();

        $article->id = $this->basedb->lastInsertId('id');

        // Verknüpfte Daten speichern
        $this->assignTagsToArticle($article->id, $article->tags);

        return $article->id;
    }
    
    // </editor-fold>


    // <editor-fold desc="Page">

    /**
     * Durchsucht die Pages nach verschiedenen Filterkriterien
     * @param array $filter
     * @param bool $releasedOnly
     * @param string $searchterm
     * @param int|null $limit
     * @param int|null $offset
     * @return Page[]
     */
    public function searchPages($filter = null, bool $releasedOnly = true, $searchterm = null, $limit = null, $offset = null) {
        $pages = array();
        $params = array();

        // SQL zusammenstellen
        $sql = 'SELECT * FROM page WHERE 1 = 1 ' ;
        if ($releasedOnly) {
            $sql .= 'AND status_code = ' . StatusCode::ACTIVE;
        }
        if ($searchterm !== null && strlen($searchterm) > 0) {
            $sql .= ' AND headline LIKE :headline OR url LIKE :url OR content LIKE :content ';
            $params['headline'] = "%$searchterm%";
            $params['url'] = "%$searchterm%";
            $params['content'] = "%$searchterm%";
        }
        $sql .= 'ORDER BY headline, url ASC ';
        if ($limit !== null && $offset !== null) {
            $limit = intval($limit);
            $offset = intval($offset);
            $sql .= " LIMIT $offset, $limit ";
        } elseif ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        // Parameter füllen
        $stmt = $this->basedb->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->execute();

        while (($page = Page::fetchFromPdoStatement($stmt)) !== null) {
            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * Liest die Page mit der angegebenen ID aus und gibt ein entsprechendes Objekt zurück
     * @param int $id ID der gesuchten Page
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
        $stmt = $this->basedb->prepare($sql);
        $stmt->bindValue('url', $url);
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
            $this->log->warn($ex);
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
            return self::$userCache[$userId];
        }

        $user = $this->getUserById($userId, true);
        if ($user == null) return null;

        self::$userCache[$userId] = $user;
        return $user;
    }

    // </editor-fold>
}