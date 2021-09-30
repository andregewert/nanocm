<?php
/*
 * NanoCM
 * Copyright (c) 2017 - 2021 André Gewert <agewert@ubergeek.de>
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

use Ubergeek\NanoCm\Medium;

/**
 * Imlements a simple slideshow which displays images from a media folder.
 * The slideshow is intended to show full size (full content width) images including
 * their descriptive texts.
 */
class SlideshowPlugin extends PluginAdapter {

    /**
     * @inheritDoc
     */
    public function replacePlaceholder(string $placeholder, array $parameters): string {
        $orm = $this->getModule()->getOrm();
        if ($orm === null) return '';

        try {
            $folderId = (int)$parameters['folderid']->value;
            $folder = $orm->getMediumById($folderId, Medium::TYPE_FOLDER);
            $media = $orm->getMediaByParentId($folderId, Medium::TYPE_FILE);
            $format = $this->getModule()->getOrm()->getImageFormatByKey('preview');

            $params = array(
                'plugin'                => $this,
                'plugin.placeholder'    => $placeholder,
                'plugin.parameters'     => $parameters,
                'folderid'              => $folderId,
                'folder'                => $folder,
                'media'                 => $media,
                'format'                => $format
            );
            $content = $this->getModule()->renderUserTemplate('plugin/slideshow.phtml', $params);
        } catch (\Exception $ex) {
            $content = '[Error while rendering slideshow]';
            $this->getModule()->err('Error while rendering slideshow', $ex);
        }
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'Image slideshow';
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
        return '0.9';
    }

    /**
     * @inheritDoc
     */
    public function getPlaceholder(): string {
        return 'slideshow';
    }

    /**
     * @inheritDoc
     */
    public function getAvailableParameters(): array {
        $params = array();
        $params['folderid'] = PluginParameter::fromArray(array(
            'key'       => 'folderid',
            'type'      => PluginParameter::TYPE_MEDIAFOLDER,
            'required'  => true
        ));
        $params['type'] = PluginParameter::fromArray(array(
            'key'       => 'type',
            'type'      => PluginParameter::TYPE_SELECTION,
            'options'   => array('standard'),
            'default'   => 'standard',
            'required'  => false
        ));
        return $params;
    }
}