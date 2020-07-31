<?php

// NanoCM
// Copyright (C) 2017 - 2020 André Gewert <agewert@ubergeek.de>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

namespace Ubergeek\NanoCm;

/**
 * Simple pdo for holding meta information to ncm templates
 *
 * @package Ubergeek\NanoCm
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2020-07-31
 */
class TemplateInfo {

    // <editor-fold desc="Public properties">

    /**
     * Version string
     * @var string
     */
    public $version;

    /**
     * Short title of this template
     * @var string
     */
    public $title;

    /**
     * Longer description for this template
     * @var string
     */
    public $description;

    /**
     * Author information
     * @var string
     */
    public $author;

    /**
     * Copyright / attribution text for this template
     * @var string
     */
    public $attribution;

    /**
     * Optional: e-mail address of the template's author
     * @var string
     */
    public $mail;

    /**
     * Optional: Website of the template's author or link to a website with detailed information
     * @var string
     */
    public $link;

    /**
     * Physically dirname of this template (relative to the nano|cm template directory)
     * @var string
     */
    public $dirname;

    // </editor-fold>


    // <editor-fold desc="Constructor">

    public function __construct($data = null) {
        if (is_array($data)) {
            if (array_key_exists('version', $data)) $this->version = (string)$data['version'];
            if (array_key_exists('title', $data)) $this->title = (string)$data['title'];
            if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
            if (array_key_exists('author', $data)) $this->author = (string)$data['author'];
            if (array_key_exists('attribution', $data)) $this->attribution = (string)$data['attribution'];
            if (array_key_exists('mail', $data)) $this->mail = (string)$data['mail'];
            if (array_key_exists('link', $data)) $this->link = (string)$data['link'];
            if (array_key_exists('dirname', $data)) $this->dirname = (string)$data['dirname'];
        }
    }

    public function toDisplayString() {
        return "$this->title: $this->description";
    }

    // </editor-fold>

}