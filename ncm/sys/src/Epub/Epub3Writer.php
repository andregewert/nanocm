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
use Ubergeek\Epub\Document;
use Ubergeek\MarkupParser\MarkupParser;

/**
 * Class Epub3Writer
 * @package Ubergeek\Epub
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-01-05
 */
class Epub3Writer {

    public function createDocumentFile(Document $document) : string {
        // TODO implementieren
        $archive = new ZipArchive();
        $archive->open("/volume1/webhosts/uberdev/test.zip", ZipArchive::CREATE);
        $archive->addFromString("mimetype", "application/epub+zip");
        $archive->setCompressionIndex(0, ZipArchive::CM_STORE);

        $archive->addEmptyDir('META-INF');
        $archive->addFromString('META-INF/container.xml', $this->createContainerXml());

        $archive->addFromString('index.opf', $this->createIndexOpf($document));

        $archive->close();

        return "";
    }

    // <editor-fold desc="Internal methods">

    private function createContainerXml() : string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $rootNode = $dom->appendChild($dom->createElementNS('urn:oasis:names:tc:opendocument:xmlns:container', 'container'));
        $rootNode->appendChild($dom->createAttribute('version'))->nodeValue = '1.0';
        $fileNode = $rootNode->appendChild($dom->createElement('rootfiles'))->appendChild($dom->createElement('rootfile'));
        $fileNode->appendChild($dom->createAttribute('media-type'))->nodeValue = 'application/oebps-package+xml';
        $fileNode->appendChild($dom->createAttribute('full-path'))->nodeValue = 'index.opf';
        $c = $dom->saveXML();
        //echo htmlspecialchars(wordwrap($c, 75, "\n", true));
        return $c;
    }

    private function createIndexOpf(Document $document) : string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $rootNode = $dom->appendChild($dom->createElementNS('http://www.idpf.org/2007/opf', 'package'));
        $rootNode->appendChild($dom->createAttribute('version'))->nodeValue = '3.0';
        $rootNode->appendChild($dom->createAttribute('unique-identifier'))->nodeValue = 'id';
        $rootNode->appendChild($dom->createAttribute('xmlns:dc'))->nodeValue = 'http://purl.org/dc/elements/1.1/';
        //$rootNode->appendChild($dom->createAttribute('xmlns:opf'))->nodeValue = 'http://www.idpf.org/2007/opf';

        $metadata = $rootNode->appendChild($dom->createElement('metadata'));
        $metadata->appendChild($dom->createElement('dc:title'))->nodeValue = $document->title;
        $metadata->appendChild($dom->createElement('dc:identifier'))->nodeValue = 'dummyid';
        $metadata->appendChild($dom->createElement('dc:language'))->nodeValue = $document->language;
        $metadata->appendChild($dom->createElement('dc:description'))->nodeValue = $document->description;

        // Alle Dateien einschl. Bildern und CSS-Dateien
        $manifest = $rootNode->appendChild($dom->createElement('manifest'));

        // Nur die anzuzeigenden Inhalts-Elemente, im Normalfall: (generiertes) TOC, Inhalt 1, Inhalt 2 ...
        $spine = $rootNode->appendChild($dom->createElement('spine'));

        $c = $dom->saveXML();
        echo htmlspecialchars(wordwrap($c, 75, "\n", true));
        return $c;
    }

    // </editor-fold>
}