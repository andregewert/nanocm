<?php

/*
 * NanoCM
 * Copyright (C) 2017-2023 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\NanoCm\Module;
use Ubergeek\Epub\Document;
use Ubergeek\Feed\AtomWriter;
use Ubergeek\NanoCm\Article;
use Ubergeek\NanoCm\Comment;
use Ubergeek\NanoCm\Constants;
use Ubergeek\NanoCm\EbookGenerator;
use Ubergeek\NanoCm\Exception\ContentNotFoundException;
use Ubergeek\NanoCm\FeedGenerator;
use Ubergeek\NanoCm\Medium;
use Ubergeek\NanoCm\Page;
use Ubergeek\NanoCm\Setting;
use Ubergeek\NanoCm\StatusCode;
use Ubergeek\NanoCm\Tag;
use Ubergeek\NanoCm\UserType;
use Ubergeek\NanoCm\Util;

/**
 * Kern(-Ausgabe-)modul des NanoCM.
 * 
 * Dieses Modul implementiert die Kern-Ausgabe-Funktionen des Content Managers.
 * Dazu gehören insbesondere die Startseite, die Darstellung einzelner Artikel
 * sowie das CMS-Archiv.
 * 
 * @author André Gewert <agewert@ubergeek.de>
 * @package Ubergeek\NanoCm
 * @created 2017-11-12
 */
class CoreModule extends AbstractModule {

    // <editor-fold desc="Properties">

    /** @var string Generierter Content */
    private $content = '';

    /** @var Page Gegebenenfalls anzuzeigende Seite */
    public $page = null;

    /** @var Article Gegebenenfalls anzuzeigender Weblog-Artikel */
    public $article = null;

    /** @var Document Zu erzeugendes E-Book-Dokument */
    public $ebook = null;

    /** @var bool Gibt an, ob der abgerufene Artikel noch nicht freigeschaltet ist, sondern als Preview angezeigt wird. */
    public $isPreview = false;

    /** @var Comment[] Gegebenfalls anzuzeigende Kommentare zu einem Artikel */
    public $comments = null;

    /** @var Article[] Gegebenenfalls anzuzeigende Weblog-Artikel */
    public $articles = null;

    /** @var string Gesuchte Tags */
    public $searchTags = null;

    /** @var bool Gibt an, ob die Kommentarfunktion grundsätzlich eingeschaltet ist */
    public $commentsEnabled = false;

    /** @var bool Gibt an, ob die Trackback-Funktion grundsätzlich eingeschaltet ist */
    public $trackbacksEnabled = false;

    /**
     * Gibt an, ob der angemeldete (oder anonyme) Nutzer Vorschau-Ansichten von noch nicht freigeschalteten
     * Artikeln sehen darf
     * @var bool
     */
    public $isPreviewEnabled = false;

    /** @var string Benutzereingabe "Name" für Kommentar */
    public $commentName;

    /** @var string Benutzereingabe "E-Mail" für Kommentar */
    public $commentMail;

    /** @var string Benutzereingabe "Website" für Kommentar */
    public $commentSite;

    /** @var string Benutzereingabe "Ort" für Kommentar */
    public $commentLocation;

    /** @var string Benutzereingabe "Überschrift" für Kommentar */
    public $commentHeadline;

    /** @var string Benutzereingabe "Text" für Kommentar */
    public $commentText;

    /** @var bool Benutzereingabe füe "Gravatar verwenden" */
    public $commentUseGravatar;

    /** @var bool Zusätzliches Eingabefeld für Kommentare (soll leer bleiben -> verzweifelter Versucht, Bots abzuhalten) */
    public $commentAdditionalInput;

    // </editor-fold>


    /**
     * Core functions for the public front end
     * @throws ContentNotFoundException
     * @throws \Exception
     */
    public function run() {
        $parts = $this->getRelativeUrlParts();

        // Das CoreModule protokolliert Seitenzugriffe, wenn die Funktion aktiviert ist
        if ($this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLELOGGING) == '1') {
            $geolocation = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEGEOLOCATION) == '1';
            $accesslog = $this->orm->getSettingValue(Setting::SYSTEM_STATS_ENABLEACCESSLOG) == '1';
            $entry = $this->ncm->createAccessLogEntry($this->frontController->getHttpRequest());

            if ($accesslog) {
                $this->orm->logHttpRequest($entry);
            }
            $this->orm->logSimplifiedStats($entry, $geolocation);
        }

        // Kommentare und Trackbacks
        $this->commentsEnabled = $this->orm->getSettingValue(Setting::SYSTEM_ENABLECOMMENTS) == '1';
        $this->trackbacksEnabled = $this->orm->getSettingValue(Setting::SYSTEM_ENABLETRACKBACKS) == '1';

        if ($this->commentsEnabled) {
            $this->commentName = $this->getOrOverrideSessionVarWithParam('_n');
            $this->commentMail = $this->getOrOverrideSessionVarWithParam('_e');
            $this->commentSite = $this->getOrOverrideSessionVarWithParam('_u');
            $this->commentLocation = $this->getOrOverrideSessionVarWithParam('_c');
            $this->commentHeadline = $this->getOrOverrideSessionVarWithParam('_h');
            $this->commentText = $this->getOrOverrideSessionVarWithParam('_t');
            $this->commentUseGravatar = $this->getOrOverrideSessionVarWithParam('_g');
            $this->commentAdditionalInput = $this->getOrOverrideSessionVarWithParam('_i');
        }

        // Vorschau ist dann aktiviert, wenn ein Benutzer angemeldet ist, der mindestens Redakteurs-Rechte besitzt
        $this->isPreviewEnabled = $this->ncm->isUserLoggedIn() && $this->ncm->getLoggedInUser()->usertype >= UserType::EDITOR;

        switch ($parts[0]) {

            // Verschiedene Datenabrufe
            case 'ajax':
                $this->setPageTemplate(self::PAGE_NONE);
                $this->setContentType('text/javascript');

                switch ($parts[1]) {

                    // Kommentar-Captcha anfordern
                    case 'captcha':
                        $captcha = $this->ncm->createCaptcha();
                        $tempContent = array(
                            'status'        => 0,
                            'message'       => 'comment-captcha',
                            'operand1'      => $captcha->valueA,
                            'operand2'      => $captcha->valueB,
                            'operator'      => $captcha->operator,
                            'captchaId'     => $captcha->captchaId
                        );
                        break;

                    // Kommentar zu Artikel speichern
                    case 'comment':
                        $articleId = $this->getParam('_a', 0);
                        $article = $this->orm->getArticleById($articleId);
                        if ($this->commentsEnabled && $article !== null && $article->enable_comments) {
                            $comment = $this->tryToSaveComment($articleId);
                            $tempContent = array(
                                'status' => ($comment instanceof Comment) ? 0 : 100,
                                'message' => ($comment instanceof Comment) ? 'comment saved' : $comment,
                                'comment' => $comment
                            );
                            $this->ncm->session->setVar('_h', '');
                            $this->ncm->session->setVar('_t', '');
                        } else {
                            $tempContent = array(
                                'status'    => 100,
                                'message'   => 'Kommentarfunktion ist deaktiviert',
                                'comment'   => null
                            );
                        }
                        break;

                    // Accepting privacy policy / cookie usage
                    case 'privacypolicy':
                        if (count($parts) >= 3 && $parts[2] === 'accept') {
                            $this->ncm->setPrivacyPolicyCookie();
                            $tempContent = array(
                                'status' => 0
                            );
                            break;
                        }

                    // Ungültiger bzw. undefiniert Aufruf
                    default:
                        $tempContent = array(
                            'status'        => 100,
                            'message'       => 'Invalid request'
                        );
                }
                $this->content = json_encode($tempContent);
                break;

            // Integrierte Weblog-Funktionen
            case 'weblog':

                // Artikel-Ansicht
                if ($parts[1] === 'article') {
                    $this->article = $this->orm->getArticleById(intval($parts[2]), !$this->isPreviewEnabled);
                    if ($this->article !== null) {
                        // Bestimmung Preview-Modus bei noch nicht freigeschalteten Artiklen
                        $now = new \DateTime('now');
                        $this->isPreview = $this->article->status_code != StatusCode::ACTIVE
                            || ($this->article->start_timestamp != null && $this->article->start_timestamp > $now)
                            || ($this->article->stop_timestamp != null && $this->article->stop_timestamp < $now);

                        // Darstellung des Artikels
                        $this->comments = $this->orm->getCommentsByArticleId($this->article->id);
                        $this->setTitle($this->getSiteTitle() . ' - ' . $this->article->headline);
                        $this->content = $this->renderUserTemplate('content-weblog-article.phtml');
                    }
                }

                // Archiv
                elseif ($parts[1] === 'archive') {
                    $this->setTitle($this->getSiteTitle() . ' - Archiv');
                    $this->articles = $this->orm->getArticleArchive();
                    $this->content = $this->renderUserTemplate('content-weblog-archive.phtml');
                }

                // Suche nach Artikeln mit bestimmten Tags
                elseif ($parts[1] === 'tags') {
                    $this->setTitle($this->getSiteTitle() . ' - Aritkelsuche nach Stichworten');
                    $this->searchTags = Tag::splitTagsString(urldecode($parts[2]));
                    $filter = new Article();
                    $filter->tags = $this->searchTags;
                    // TODO Paginierung!
                    $this->articles = $this->orm->searchArticles($filter);
                    $this->content = $this->renderUserTemplate('content-weblog-tags.phtml');
                }

                // Verschiedene Atom-Feeds
                elseif ($parts[1] === 'feed') {

                    // Neueste Artikel
                    if (count($parts) >= 2 && $parts[2] === 'index.php') {
                        $this->setPageTemplate(self::PAGE_NONE);
                        $this->setContentType('text/xml');
                        $generator = new FeedGenerator($this);
                        $feed = $generator->createFeedForArticles(
                            $this->ncm->orm->getLatestArticles(10),
                            $this->frontController->getHttpRequest()->requestUri->getRequestUrl(),
                            $this->ncm->orm->getSiteTitle() . ': Neueste Artikel'
                        );
                        $writer = new AtomWriter();
                        $this->content = $writer->writeFeed($feed);
                    }

                    // Neueste Kommentare
                    elseif (count($parts) >= 3 && $parts[2] === 'comments') {
                        $this->setPageTemplate(self::PAGE_NONE);
                        $this->setContentType('text/xml');

                        $this->setPageTemplate(self::PAGE_NONE);
                        $this->setContentType('text/xml');

                        $generator = new FeedGenerator($this);
                        $feed = $generator->createFeedForComments(
                            $this->orm->getLatestComments(10),
                            $this->frontController->getHttpRequest()->requestUri->getRequestUrl(),
                            $this->ncm->orm->getSiteTitle() . ': Neueste Kommentare'
                        );
                        $writer = new AtomWriter();
                        $this->content = $writer->writeFeed($feed);
                    }

                    // Artikel zu bestimmten Schlagworten
                    elseif (count($parts) >= 4 && $parts[2] === 'tags') {
                        $this->setPageTemplate(self::PAGE_NONE);
                        $this->setContentType('text/xml');

                        $tags = Tag::splitTagsString(urldecode($parts[3]));
                        $filter = new Article();
                        $filter->tags = $tags;
                        $articles = $this->orm->searchArticles($filter);

                        $generator = new FeedGenerator($this);
                        $feed = $generator->createFeedForArticles(
                            $articles,
                            $this->frontController->getHttpRequest()->requestUri->getRequestUrl(),
                            $this->ncm->orm->getSiteTitle() . ': Artikel mit Stichworten'
                        );
                        $writer = new AtomWriter();
                        $this->content = $writer->writeFeed($feed);
                    }
                }

                break;

            // E-Book-Export
            case 'ebook':
                if (count($parts) >= 2) {
                    $this->setPageTemplate(self::PAGE_NONE);
                    $this->targetFormat = Constants::FORMAT_XHTML;
                    $this->setContentType('application/epub+zip');

                    switch ($parts[1]) {
                        case 'article':
                            $article = $this->orm->getArticleById((int)$parts[2], !$this->isPreviewEnabled);
                            if ($article === null) {
                                throw new ContentNotFoundException("Could not find requested article!");
                            }

                            $ebookGenerator = new EbookGenerator($this);
                            $fname = Util::simplifyUrlString($article->headline) . '.epub';
                            $this->replaceMeta('content-disposition', "attachment; filename=\"$fname\"");
                            $this->content = $ebookGenerator->createEpubForArticle($article);
                            break;

                        case 'series':
                            // TODO implementieren
                            break;
                    }
                }
                break;

            // Mediendateien
            case 'media':
                $this->replaceMeta('Cache-control', 'private, must-revalidate');
                if (count($parts) >= 3) {
                    switch ($parts[2]) {

                        // Eine Datei herunterladen
                        case 'download':
                            $mediumHash = $parts[1];
                            $this->setPageTemplate(self::PAGE_NONE);
                            $this->setContentType('binary/octet-stream');
                            $file = $this->orm->getMediumByHash($mediumHash, Medium::TYPE_FILE, true);
                            if ($file != null) {
                                if (strlen($file->type) > 0) {
                                    $this->setContentType($file->type);
                                    $this->replaceMeta('Content-Disposition', "attachment; filename=\"" . urlencode($file->filename) . "\"");
                                    $this->replaceMeta('Content-Length', $file->filesize);
                                    $this->content = $this->orm->getMediumFileContents($file->id);
                                }
                            }
                            break;

                        // Gravatar
                        case 'gravatar':
                            $hash = $parts[1];
                            $size = (count($parts) >= 3)? (int)$parts[3] : 50;
                            $imgData = $this->ncm->mediaManager->getGravatar($hash, $size);

                            if ($imgData !== null) {
                                $this->setPageTemplate(self::PAGE_NONE);
                                $this->setContentType('image/png');
                                $this->replaceMeta('content-length', strlen($imgData));
                                $this->content = $imgData;
                            }
                            break;

                        // Youtube-Vorschau
                        case 'yt':
                            $youtubeId = $parts[1];
                            $formatKey = $parts[3];
                            $format = $this->orm->getImageFormatByKey($formatKey);

                            if ($format != null) {
                                $imgData = $this->ncm->mediaManager->createImageForYoutubeVideoWithFormat(
                                    $youtubeId, $format
                                );
                                $this->setPageTemplate(self::PAGE_NONE);
                                $this->setContentType('image/jpeg');
                                $this->replaceMeta('content-length', strlen($imgData));
                                $this->content = $imgData;
                            }
                            break;

                        // Ein Bild in einem bestimmten Format ausgeben
                        case 'image':
                            $mediumHash = $parts[1];
                            $formatKey = $parts[3];
                            $scaling = (count($parts) >= 5)? (int)$parts[4] : 1;
                            if ($scaling > 4) $scaling = 4;
                            $format = $this->orm->getImageFormatByKey($formatKey);
                            $medium = $this->orm->getMediumByHash($mediumHash, Medium::TYPE_FILE, true);

                            if ($medium !== null) {
                                $this->setPageTemplate(self::PAGE_NONE);
                                $this->setContentType('image/jpeg');

                                $filename = Util::createPath($this->ncm->mediadir, $medium->id);
                                $c = $this->ncm->mediaManager->createImageForMediumWithImageFormat(
                                    $medium, $filename, $format, 'jpeg', $scaling
                                );

                                $this->replaceMeta('content-length', strlen($c));
                                $this->content = $c;
                            }
                            break;
                    }
                }
                break;
            
            // Anmeldung
            case 'login.html':
            case 'login.php':
                $this->setTitle($this->getSiteTitle() . ' - Anmelden');
                if ($this->getAction() === 'login') {
                    $success = $this->ncm->tryToLoginUser(
                        $this->getParam('username', ''),
                        $this->getParam('password', '')
                    );
                    if ($success) {
                        $this->replaceMeta('location', $this->convUrl('index.php'));
                    } else {
                        $this->addUserMessage('Die Anmeldung war nicht erfolgreich. Bitte überprüfen Sie Ihre Anmeldedaten.', "Anmeldung fehlgeschlagen");
                    }
                }
                $this->content = $this->renderUserTemplate('content-login.phtml');
                break;
            
            // Abmeldung
            case 'logout.html':
            case 'logout.php':
                $this->ncm->logoutUser();
                $this->content = 'success';
                $this->replaceMeta('location', $this->convUrl('index.php'));
                break;
            
            // Startseite
            case 'index.html':
            case 'index.php';
                $this->setTitle($this->getSiteTitle());
                $this->content = $this->renderUserTemplate('content-start.phtml');
                break;

            // Frei definierbare Pages
            default:
                $this->page = $this->orm->getPageByUrl($this->getRelativeUrl());
                if ($this->page !== null) {
                    $this->setTitle($this->getSiteTitle() . ' - ' . $this->page->headline);
                    $this->content = $this->renderUserTemplate('content-page.phtml');
                }
        }

        $this->setContent($this->content);
    }


    // <editor-fold desc="Internal methods">

    /**
     * Validiert und speichert ggf. einen Kommentar
     *
     * @param integer $articleId
     * @return string|Comment
     * @todo Es sollten keine Strings zurückgegeben werden, sondern Status-Codes, die an anderer Stelle in
     *       lokalisierte Meldungen übersetzt werden können
     */
    private function tryToSaveComment($articleId) {
        $captchaId = $this->getParam('cpsid', '');
        $captchaInput = $this->getParam('sc', '');

        $comment = new Comment();
        $comment->article_id = $articleId;
        $comment->status_code = StatusCode::ACTIVE;
        $comment->spam_status = 0;
        $comment->username = $this->commentName;
        $comment->email = $this->commentMail;
        $comment->headline = $this->commentHeadline;
        $comment->content = $this->commentText;
        $comment->use_gravatar = $this->commentUseGravatar == '1';

        // Eingabe auf Vollständigkeit überprüfen
        if (strlen($comment->username) < 1
            || strlen(trim($comment->content)) < 1
            || strlen($comment->email) < 1) {
            return "Bitte gib mindestens einen Namen, eine E-Mail-Adresse und einen Text ein, um zu kommentieren";
        }

        // Captcha überprüfen
        if (!$this->ncm->isCaptchaSolved($captchaId, $captchaInput)) {
            return 'Du hast die Sicherheitsfrage nicht korrekt beantwortet. Bitte gib die Antwort in Ziffern und nicht in Worten ein.';
        }

        // E-Mail überprüfen
        if (!Util::isValidEmail($comment->email)) {
            return 'Du hast eine ungültige E-Mail-Adresse eingegeben';
        }

        // Spam-Prüfung und zeitliche Einschränkungen nur, wenn kein Benutzer angemeldet ist
        if (!$this->ncm->isUserLoggedIn()) {

            // IP-Adresse auf Sperre testen
            if ($this->ncm->isIpBlockedForComments($_SERVER['REMOTE_ADDR'])) {
                return 'Dein Kommentar wurde nicht übernommen. Bitte habe ein paar Minuten Geduld, bevor Du einen neuen Kommentar schreibst.';
            }

            // Spam-Begriffe prüfen
            if (Util::checkTextAgainstWordsList($comment->content, Util::getJunkWords())) {
                $comment->status_code = StatusCode::MARKED_AS_JUNK;
            }

            // Wenn Links enthalten sind, pauschal einstufen als "zu moderieren"
            if (preg_match('/https?\:/i', $comment->content) > 0) {
                $comment->status_code = StatusCode::MODERATION_REQUIRED;
            }
        }

        // Kommentar speichern
        $this->ncm->blockIpForComments($_SERVER['REMOTE_ADDR']);
        $comment->id = $this->orm->saveComment($comment);
        $this->ncm->session->setVar('commentText', '');
        $this->ncm->session->setVar('commentHeadline', '');
        $this->commentText = '';
        $this->commentHeadline = '';

        // TODO E-Mail-Notification senden
        // ...

        return $comment;
    }

    // </editor-fold>
}