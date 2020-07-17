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
 * Implementiert einen FeedWriter, der eine Ausgabe nach Atom-Spezifikationen erzeugt
 *
 * @package Ubergeek\Feed
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2018-11-09
 */
class AtomWriter implements FeedWriterInterface  {

    /**
     * Wandelt den übergebenen Feed mit Hilfe der SimpleXML-Funktionen in einen Atom-Feed um
     *
     * @param Feed $feed Der zu wandelnde Feed
     * @return string Das Ergebnis im Atom-XML-Format
     */
    public function writeFeed(Feed $feed): string {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\" ?><feed xmlns=\"http://www.w3.org/2005/Atom\"></feed>");
        $xml->addChild('title', $feed->title);
        if (mb_strlen($feed->subtitle) > 0) {
            $xml->addChild('subtitle', $feed->subtitle);
        }
        $xml->addChild('id', $feed->id);
        if ($feed->updated instanceof \DateTime) {
            $xml->addChild('updated', $feed->updated->format('c'));
        } else {
            $xml->addChild('updated', date('c'));
        }

        // Autorendaten
        if ($feed->author != null) {
            $this->createPersonNode($xml, 'author', $feed->author);
        }

        // Weiterführende Links
        if (is_array($feed->links)) {
            foreach ($feed->links as $link) {
                $this->createLinkNode($xml, $link);
            }
        }

        // Eigentliche Feed-Inhalte
        if (is_array($feed->entries)) {
            foreach ($feed->entries as $entry) {
                $this->createEntryNode($xml, $entry);
            }
        }

        // Kategorien / Tags
        if (is_array($feed->categories)) {
            foreach ($feed->categories as $category) {
                $node = $xml->addChild('category');
                $node->addAttribute('term', htmlspecialchars($category));
            }
        }

        return $xml->asXML();
    }

    // <editor-fold desc="Internal methods">

    private function createEntryNode(\SimpleXMLElement $parentNode, Entry $entry) {

        /* @var $entryNode \SimpleXMLElement */
        /* @var $node \SimpleXMLElement */
        $entryNode = $parentNode->addChild('entry');
        $entryNode->addChild('id', $entry->id);
        $entryNode->addChild('title', $entry->title);
        if ($entry->published instanceof \DateTime) {
            $entryNode->addChild('published', $entry->published->format('c'));
        }
        if ($entry->updated instanceof \DateTime) {
            $entryNode->addChild('updated', $entry->updated->format('c'));
        }

        // Content
        $node = $entryNode->addChild('content', htmlspecialchars($entry->content));
        $node->addAttribute('type', $entry->contentType);

        // Summary
        if (!empty($entry->summary)) {
            $node = $entryNode->addChild('summary', htmlspecialchars($entry->summary));
            $node->addAttribute('type', $entry->contentType);
        }

        // Kategorien / Tags
        if (is_array($entry->categories)) {
            foreach ($entry->categories as $category) {
                $node = $entryNode->addChild('category');
                $node->addAttribute('term', htmlspecialchars($category));
            }
        }

        // Weiterführende Links
        if (is_array($entry->links)) {
            foreach ($entry->links as $link) {
                $this->createLinkNode($entryNode, $link);
            }
        }

        // Autorendaten
        if ($entry->author instanceof Person) {
            $this->createPersonNode($entryNode, 'author', $entry->author);
        }

        // Beitragende
        if (is_array($entry->contributors)) {
            foreach ($entry->contributors as $person) {
                $this->createPersonNode($entryNode, 'contributor', $person);
            }
        }

        if (!empty($entry->rights)) {
            $entryNode->addChild('rights', $entry->rights);
        }
    }

    private function createLinkNode(\SimpleXMLElement $parentNode, Link $link) {
        /* @var $node \SimpleXMLElement */
        $node = $parentNode->addChild('link');
        $node->addAttribute('href', $link->href);
        if (!empty($link->relation)) {
            $node->addAttribute('rel', $link->relation);
        }
        if (!empty($link->hrefLang)) {
            $node->addAttribute('hreflang', $link->hrefLang);
        }
        if (!empty($link->title)) {
            $node->addAttribute('title', $link->title);
        }
        if (!empty($link->length)) {
            $node->addAttribute('length', $link->length);
        }
    }

    private function createPersonNode(\SimpleXMLElement $parentNode, string $name, Person $person) {
        /* @var $node \SimpleXMLElement */
        $node = $parentNode->addChild($name);
        $node->addChild('name', $person->name);
        if (mb_strlen($person->email) > 0) {
            $node->addChild('email', $person->email);
        }
        if (mb_strlen($person->uri) > 0) {
            $node->addChild('uri', $person->uri);
        }
    }

    // </editor-fold>
}