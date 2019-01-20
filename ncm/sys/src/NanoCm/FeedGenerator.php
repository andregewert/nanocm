<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2019 André Gewert <agewert@ubergeek.de>
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

use Ubergeek\Feed\Entry;
use Ubergeek\Feed\Link;
use Ubergeek\Feed\Feed;
use Ubergeek\Feed\Person;
use Ubergeek\NanoCm\Module\AbstractModule;

/**
 * Erstellt verschiedene Atom-Feeds für die CMS-Inhalte
 *
 * @package Ubergeek\NanoCm
 * @created 2019-01-20
 * @author André Gewert <agewert@ubergeek.de>
 */
class FeedGenerator
{
    /**
     * @var AbstractModule
     */
    private $module;

    /**
     * @var NanoCm
     */
    private $ncm;

    public function __construct(AbstractModule $module) {
        $this->module = $module;
        $this->ncm = $module->ncm;
    }

    /**
     * Erstellt ein Feed-Objekt für die übergebenen Artikel-Objekte
     *
     * @param $articles Die in den Feed einzubindenden Artikel
     * @param string|null $feedUrl Die Abruf-URL für den Feed
     * @param string|null $title Optionaler Titel für den Feed. Wird keiner angegeben, so wird der Seitentitel verwendet.
     * @return Feed Der generierte Feed; nicht als String, sondern in Objekt-Form. Kann über passende Writer-Instanzen
     * als (XML-)String formatiert werden.
     */
    public function createFeedForArticles($articles, $feedUrl = null, $title = null) {
        if ($title == null) {
            $title = $this->ncm->orm->getSiteTitle();
        }

        $feed = new Feed();
        $feed->title = $title;
        $feed->links = array();
        if ($feedUrl != null) {
            $feed->id = $feedUrl;
            $link = new Link();
            $link->href = $feedUrl;
            $link->relation = 'self';
            $link->title = $title;
            $link->type = 'text/xml';
            $feed->links[] = $link;
        }
        $feed->author = new Person();
        $feed->author->name = $this->ncm->orm->getSettingValue(Setting::SYSTEM_WEBMASTER_NAME);
        $feed->author->email = $this->ncm->orm->getSettingValue(Setting::SYSTEM_WEBMASTER_EMAIL);
        $feed->author->uri = $this->ncm->orm->getSettingValue(Setting::SYSTEM_WEBMASTER_URL);
        $feed->rights = $this->ncm->orm->getSettingValue(Setting::SYSTEM_COPYRIGHTNOTICE);
        $feed->entries = array();

        $updated = null;
        foreach ($articles as $article) {
            $dummy = ($article->modification_timestamp instanceof \DateTime)?
                $article->modification_timestamp : $article->creation_timestamp;
            if ($updated === null || $dummy > $updated) {
                $updated = $dummy;
            }
        }
        $feed->updated = $updated;

        foreach ($articles as $article) {
            $dt = ($article->modification_timestamp instanceof \DateTime)?
                $article->modification_timestamp : $article->creation_timestamp;
            $author = $this->ncm->orm->getUserById($article->author_id, true);

            $entry = new Entry();
            $entry->id = $this->module->convUrlToAbsolute($article->getArticleUrl());
            $entry->title = $article->headline;
            $entry->updated = $dt;
            $entry->published = $article->publishing_timestamp;
            $entry->author = new Person();
            $entry->author->name = $author->getFullName();
            $entry->author->email = $author->email;
            $entry->content = $this->module->convertTextWithFullMarkup($article->content, Constants::FORMAT_HTML);
            $entry->contentType = 'html';

            if (mb_strlen($article->teaser) > 0) {
                $entry->summary = $this->module->convertTextWithFullMarkup($article->teaser, Constants::FORMAT_HTML);
            }

            if (count($article->tags) > 0) {
                $entry->categories = $article->tags;
            }

            $feed->entries[] = $entry;
        }

        return $feed;
    }

    /**
     * Erstellt ein Feed-Objekt mit den übergebenen Kommentaren
     *
     * @param Comment[] $comments
     * @param string|null $feedUrl
     * @param string|null $title
     * @return Feed
     * @throws \Exception
     */
    public function createFeedForComments($comments, $feedUrl = null, $title = null) {
        if ($title == null) {
            $title = $this->ncm->orm->getSiteTitle();
        }

        $feed = new Feed();
        $feed->title = $title;
        $feed->links = array();
        if ($feedUrl != null) {
            $feed->id = $feedUrl;
            $link = new Link();
            $link->href = $feedUrl;
            $link->relation = 'self';
            $link->title = $title;
            $link->type = 'text/xml';
            $feed->links[] = $link;
        }

        $feed->author = new Person();
        $feed->author->name = $this->ncm->orm->getSettingValue(Setting::SYSTEM_WEBMASTER_NAME);
        $feed->author->email = $this->ncm->orm->getSettingValue(Setting::SYSTEM_WEBMASTER_EMAIL);
        $feed->author->uri = $this->ncm->orm->getSettingValue(Setting::SYSTEM_WEBMASTER_URL);
        $feed->rights = $this->ncm->orm->getSettingValue(Setting::SYSTEM_COPYRIGHTNOTICE);
        $feed->entries = array();

        $updated = null;
        foreach ($comments as $comment) {
            $dummy = ($comment->modification_timestamp instanceof \DateTime)?
                $comment->modification_timestamp : $comment->creation_timestamp;
            if ($updated === null || $dummy > $updated) {
                $updated = $dummy;
            }
        }
        $feed->updated = $updated;

        foreach ($comments as $comment) {
            $dt = ($comment->modification_timestamp instanceof \DateTime)?
                $comment->modification_timestamp : $comment->creation_timestamp;

            $article = $this->ncm->orm->getArticleById($comment->article_id, true);
            if ($article != null) {
                $entry = new Entry();
                $entry->id = $this->module->convUrlToAbsolute($article->getCommentUrl($comment));
                $entry->title = $comment->headline;
                $entry->updated = $dt;
                $entry->published = $comment->creation_timestamp;
                $entry->author = new Person();
                $entry->author->name = $comment->username;
                $entry->content = $this->module->convertTextWithBasicMarkup($comment->content, Constants::FORMAT_HTML);
                $entry->contentType = 'html';
                $feed->entries[] = $entry;
            }

        }

        return $feed;
    }
}