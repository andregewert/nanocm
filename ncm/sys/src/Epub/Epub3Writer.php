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

use SimpleXMLElement;
use Ubergeek\Epub\Document;
use Ubergeek\MarkupParser\MarkupParser;

class Epub3Writer {

    public function createDocumentFile(Document $document) : string {
        // TODO implementieren
        $archive = new \ZipArchive();
        $archive->open("/volume1/webhosts/uberdev/test.zip", \ZipArchive::CREATE);
        $archive->addFromString("mimetype", "application/epub+zip");
        $archive->setCompressionIndex(0, \ZipArchive::CM_STORE);

        $archive->addEmptyDir('META-INF');
        $archive->addFromString('META-INF/container.xml', $this->createContainerXml());

        $archive->addFromString('index.opf', $this->createIndexOpf($document));

        $archive->close();

        return "";
    }

    // <editor-fold desc="Internal methods">

    private function createContainerXml() : string {
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><container version=\"1.0\" xmlns=\"urn:oasis:names:tc:opendocument:xmlns:container\"></container>");
        $node = $xml->addChild('rootfiles');
        $node = $node->addChild('rootfile');
        $node->addAttribute('media-type', 'application/oebps-package+xml');
        $node->addAttribute('full-path', 'index.opf');
        return $xml->asXML();
    }

    private function createIndexOpf(Document $document) : string {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" 
         xmlns:opf="http://www.idpf.org/2007/opf"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         version="3.0"
         unique-identifier="id"
         prefix="rendition: http://www.idpf.org/vocab/rendition/#"></package>');
        $root->addAttribute('xml:lang', $document->language, 'xml');

        $metadata = $root->addChild('metadata');
        $node = $metadata->addChild('dc:identifier', 'dummyid', 'dc');
        $node->addAttribute('id', 'id');
        $metadata->addChild('dc:title', $document->title, 'dc');
        $metadata->addChild('dc:language', $document->language, 'dc');

        $manifest = $root->addChild('manifest');

        $spine = $root->addChild('spine');

        // TODO implementieren

        return $root->asXML();
    }

    // </editor-fold>
}