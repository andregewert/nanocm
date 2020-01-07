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

require 'ncm/sys/src/autoload.php';
if ($_SERVER['HTTP_HOST'] == 'www.ubergeek.de' || $_SERVER['HTTP_HOST'] == 'ubergeek.de') {
    $var = new Ubergeek\NanoCm\FrontController(substr(__DIR__, strlen('/data')));
} else {
    $var = new Ubergeek\NanoCm\FrontController(__DIR__);
}
$var->execute();

echo '<pre>';
$creator = new \Ubergeek\Epub\Epub3Writer();
$doc = new \Ubergeek\Epub\Document();
$doc->title = "Testdokument";
$doc->description = "Das hier ist ein Testdokument";
$doc->language = 'de';

$toc = $doc->createContentFromString(
    'contents/toc.xhtml',
    file_get_contents("toc.xhtml"),
    array('nav'),
    'toc'
);
//$toc->includeInSpine = false;
$doc->addContent($toc);

$doc->addContent(
    $doc->createContentFromString(
        'contents/test.xhtml',
        file_get_contents("test.xhtml")
    )
);

$creator->createDocumentFile($doc);
echo '</pre>';