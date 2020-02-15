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

    // <editor-fold desc="Properties">

    /**
     * @var string Pfad zur Ablage von temporären Dateien
     */
    private $tempDir = '/tmp';

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct($tempDir = null) {
        if ($tempDir != null) {
            $this->tempDir = $tempDir;
        }
    }

    // </editor-fold>


    // <editor-fold desc="Public methods">

    /**
     * Erstellt aus dem übergebenen E-Book-Datenmodell eine ePub3-Datei und gibt den Inhalt
     * als String zurück
     *
     * @param Document $document Das zu schreibende Dokument
     * @return string Das E-Book im ePub3-Format
     */
    public function createDocumentFile(Document $document) : string {

        $filename = $this->createTempFileName();
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

        $contents = file_get_contents($filename);
        unlink($filename);
        return $contents;
    }

    // </editor-fold>


    // <editor-fold desc="Internal methods">

    /**
     * Kodiert den übergebenen String für die Ausagbe in einer XML-Datei
     * @param string $content Der zu kodierende String
     * @return string Der XML-kodierte String
     */
    private function quoteXmlContent(string $content) {
        return htmlspecialchars($content, ENT_COMPAT | ENT_XML1);
    }

    /**
     * Erzeugt einen Namen für eine neu anzulegende temporäre Datei
     * @return string Absoluter Dateipfad zur temporären Datei
     */
    private function createTempFileName() : string {
        if (!file_exists($this->tempDir)) {
            throw new CouldNotCreateTempFileException("Configured temp dir does exist!");
        }
        if (!is_writable($this->tempDir)) {
            throw new CouldNotCreateTempFileException("Configured temp dir is not writable");
        }

        $i = 0;
        do {
            if ($i == 0) {
                $path = $this->tempDir . DIRECTORY_SEPARATOR . 'epub-temp';
            } else {
                $path = $this->tempDir . DIRECTORY_SEPARATOR . 'epub-temp-' . $i;
            }
            $i++;
        } while (file_exists($path) && $i <= 99);

        if ($i == 99) {
            throw new CouldNotCreateTempFileException("Could not find unused file name!");
        }

        return $path;
    }

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
        $supportedSpineTypes = array('nav', 'svg');
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $rootNode = $dom->appendChild($dom->createElementNS('http://www.idpf.org/2007/opf', 'package'));
        $rootNode->appendChild($dom->createAttribute('version'))->nodeValue = '3.0';
        $rootNode->appendChild($dom->createAttribute('unique-identifier'))->nodeValue = 'pub-id';
        $rootNode->appendChild($dom->createAttribute('xmlns:dc'))->nodeValue = 'http://purl.org/dc/elements/1.1/';

        $metadata = $rootNode->appendChild($dom->createElement('metadata'));
        $metadata->appendChild($dom->createElement('dc:title'))->nodeValue = $this->quoteXmlContent($document->title);
        $idNode = $dom->createElement('dc:identifier');
        $idNode->nodeValue = $document->identifier;
        $idNode->appendChild($dom->createAttribute('id'))->nodeValue = 'pub-id';
        $metadata->appendChild($idNode);
        $metadata->appendChild($dom->createElement('dc:language'))->nodeValue = $this->quoteXmlContent($document->language);
        if (strlen($document->description) > 0) {
            $metadata->appendChild($dom->createElement('dc:description'))->nodeValue = $this->quoteXmlContent($document->description);
        }

        if ($document->creator != null) {
            $metadata->appendChild($dom->createElement('dc:creator'))->nodeValue = $this->quoteXmlContent($document->creator);
        }
        if ($document->publisher != null) {
            $metadata->appendChild($dom->createElement('dc:publisher'))->nodeValue = $this->quoteXmlContent($document->publisher);
        }
        if ($document->rights != null) {
            $metadata->appendChild($dom->createElement('dc:rights'))->nodeValue = $this->quoteXmlContent($document->rights);
        }
        if ($document->subject != null) {
            $metadata->appendChild($dom->createElement('dc:subject'))->nodeValue = $this->quoteXmlContent($document->subject);
        }
        if ($document->date instanceof \DateTime) {
            $metadata->appendChild($dom->createElement('dc:date'))->nodeValue = $document->date->format('Y-m-d');
        }

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
            $props = array();
            if (is_array($content->properties)) {
                foreach ($content->properties as $property) {
                    if (in_array($property, $supportedSpineTypes)) $props[] = $property;
                }
                if (count($props) > 0) {
                    $item->appendChild($dom->createAttribute('properties'))->nodeValue = join(' ', $props);
                }
            }
            $item->appendChild($dom->createAttribute('href'))->nodeValue = $content->filename;
            $item->appendChild($dom->createAttribute('media-type'))->nodeValue = $content->mimeType;
        }

        // Spine
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

        // Guide
        $guide = $rootNode->appendChild($dom->createElement('guide'));
        foreach (array('cover', 'title-page', 'toc') as $type) {
            $content = $document->getFirstContentWithProperty($type);
            if ($content != null) {
                $item = $guide->appendChild($dom->createElement('reference'));
                $item->appendChild($dom->createAttribute('href'))->nodeValue = $content->filename;
                $item->appendChild($dom->createAttribute('title'))->nodeValue = $this->quoteXmlContent($content->title);
                $item->appendChild($dom->createAttribute('type'))->nodeValue = $type;
            }
        }

        $content = $document->getFirstNonSpecialContent();
        if ($content != null) {
            $item = $guide->appendChild($dom->createElement('reference'));
            $item->appendChild($dom->createAttribute('href'))->nodeValue = $content->filename;
            $item->appendChild($dom->createAttribute('title'))->nodeValue = $this->quoteXmlContent($content->title);
            $item->appendChild($dom->createAttribute('type'))->nodeValue = 'text';
        }

        return $dom->saveXML();
    }

    // </editor-fold>
}