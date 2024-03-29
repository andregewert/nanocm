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

namespace Ubergeek\NanoCm\ContentConverter\Plugin;

use Ubergeek\Dictionary;
use Ubergeek\NanoCm\Exception\InvalidStateException;
use Ubergeek\NanoCm\Medium;

/**
 * Imlements a simple slideshow which displays images from a media folder.
 *
 * The slideshow is intended to show full size (full content width) images including
 * their descriptive texts.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2023-09-01
 */
class SlideshowPlugin extends PluginAdapter {

    /**
     * Loads addtional information according to the given placeholder options and fills three "extended" fields.
     *
     * 'folder' contains the medium dataset for the selected folder; 'media' containts an array of the included media and
     * 'format' contains information about the selected image (preview) format.
     *
     * @param string $placeholder
     * @param Dictionary $arguments
     * @return PluginOptions
     * @throws InvalidStateException
     */
    protected function createPluginOptions(Dictionary $arguments): PluginOptions {
        $orm = $this->getModule()->getOrm();
        if ($orm === null) {
            throw new InvalidStateException('No orm instance configured');
        }

        $folderId = (int)$arguments->getValue('folderid');
        $formatKey = $arguments->getValue('format');
        if (empty($formatKey)) $formatKey = 'preview';

        $options = new SlideshowPluginOptions();
        $options->folder = $orm->getMediumById($folderId);
        $options->media = $orm->getMediaByParentId($folderId);
        $options->format = $orm->getImageFormatByKey($formatKey);
        $options->autoplay = $arguments->getValue('autoplay', false) == true;
        return $options;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'Slideshow';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string {
        return 'Creates a slideshow from a media folder';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string {
        return '0.91';
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string {
        return 'slideshow';
    }

    /**
     * @inheritDoc
     */
    public function getAvailableParameters(): array {
        $params = array();
        $params['folderid'] = PluginParameterDefinition::fromArray(array(
            'key'       => 'folderid',
            'label'     => 'Ordner',
            'type'      => PluginParameterDefinition::TYPE_MEDIAFOLDER,
            'required'  => true
        ));
        $params['format'] = PluginParameterDefinition::fromArray(array(
            'key'       => 'format',
            'label'     => 'Vorschau-Format',
            'type'      => PluginParameterDefinition::TYPE_MEDIAFORMAT,
            'default'   => 'preview',
            'required'  => true
        ));
        $params['autoplay'] = PluginParameterDefinition::fromArray([
            'key'       => 'autoplay',
            'label'     => 'Automatisch abspielen',
            'type'      => PluginParameterDefinition::TYPE_TOGGLE,
            'default'   => 0
        ]);
        return $params;
    }
}