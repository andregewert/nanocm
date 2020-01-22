<?php
/**
 * NanoCM
 * Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
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

namespace Ubergeek\Epub;

use DOMDocument;
use ZipArchive;

/**
 * Class Epub3Writer
 * @package Ubergeek\Epub
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-01-05
 */
class Epub3Writer {

    // <editor-fold desc="Public methods">

    public function createDocumentFile(Document $document) : string {

        // TODO Brauchbare temporäre Datei verwenden, evtl. mit Caching verbinden

        $filename = '/volume1/webhosts/uberdev/test.epub';
        if (file_exists($filename)) unlink($filename);

        $archive = new ZipArchive();
        $archive->open($filename, ZipArchive::CREATE);
        $archive->addFromString("mimetype", "application/epub+zip");
        $archive->setCompressionName('mimetype', ZipArchive::CM_STORE);
        $archive->addFromString('META-INF/container.xml', $this->createContainerXml());
        $archive->addFromString('index.opf', $this->createIndexOpf($document));
        foreach ($document->contents as $content) {
            $archive->addFromString($content->filename, $content->contents);
        }
        $archive->close();
        return file_get_contents($filename);
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    private function createContainerXml() : string {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $rootNode = $dom->appendChild($dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:container', 'container'));
        $rootNode->appendChild($dom->createAttribute('version'))->nodeValue = '1.0';
        $fileNode = $rootNode->appendChild($dom->createElement('rootfiles'))->appendChild($dom->createElement('rootfile'));
        $fileNode->appendChild($dom->createAttribute('media-type'))->nodeValue = 'application/oebps-package+xml';
        $fileNode->appendChild($dom->createAttribute('full-path'))->nodeValue = 'index.opf';
        return $dom->saveXML();
    }

    private function createIndexOpf(Document $document) : string {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $rootNode = $dom->appendChild($dom->createElementNS('http://www.idpf.org/2007/opf', 'package'));
        $rootNode->appendChild($dom->createAttribute('version'))->nodeValue = '3.0';
        $rootNode->appendChild($dom->createAttribute('unique-identifier'))->nodeValue = 'pub-id';
        $rootNode->appendChild($dom->createAttribute('xmlns:dc'))->nodeValue = 'http://purl.org/dc/elements/1.1/';

        $metadata = $rootNode->appendChild($dom->createElement('metadata'));
        $metadata->appendChild($dom->createElement('dc:title'))->nodeValue = $document->title;
        $idNode = $dom->createElement('dc:identifier');
        $idNode->nodeValue = 'dummyid';
        $idNode->appendChild($dom->createAttribute('id'))->nodeValue = 'pub-id';
        $metadata->appendChild($idNode);
        $metadata->appendChild($dom->createElement('dc:language'))->nodeValue = $document->language;
        $metadata->appendChild($dom->createElement('dc:description'))->nodeValue = $document->description;

        $modified = ($document->modified === null)? new \DateTime() : $document->modified;
        $modifiedNode = $dom->createElement('meta');
        $modifiedNode->appendChild($dom->createAttribute('property'))->nodeValue = 'dcterms:modified';
        $modifiedNode->nodeValue = $modified->format('Y-m-d\TH:i:s\Z');
        $metadata->appendChild($modifiedNode);

        // Alle Dateien einschl. Bildern und CSS-Dateien
        $manifest = $rootNode->appendChild($dom->createElement('manifest'));
        foreach ($document->contents as $content) {
            $item = $manifest->appendChild($dom->createElement('item'));
            $item->appendChild($dom->createAttribute('id'))->nodeValue = $content->id;
            if (is_array($content->properties) && count($content->properties) > 0) {
                $item->appendChild($dom->createAttribute('properties'))->nodeValue = join(' ', $content->properties);
            }
            $item->appendChild($dom->createAttribute('href'))->nodeValue = $content->filename;
            $item->appendChild($dom->createAttribute('media-type'))->nodeValue = $content->type;
        }

        // Nur die anzuzeigenden Inhalts-Elemente, im Normalfall: (generiertes) TOC, Inhalt 1, Inhalt 2 ...
        $spine = $rootNode->appendChild($dom->createElement('spine'));
        if ($document->isNcxExisting()) {
            $spine->appendChild($dom->createAttribute('toc'))->nodeValue = 'ncx';
        }
        foreach ($document->contents as $content) {
            if ($content->includeInSpine) {
                $item = $spine->appendChild($dom->createElement('itemref'));
                $item->appendChild($dom->createAttribute('idref'))->nodeValue = $content->id;
                $item->appendChild($dom->createAttribute('linear'))->nodeValue = 'yes';
            }
        }

        return $dom->saveXML();
    }

    // </editor-fold>
}