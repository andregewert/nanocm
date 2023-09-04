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

/**
 * This plugin displays a single image from the media manager.
 * @created 2023-09-04
 * @author André Gewert <agewert@ubergeek.de>
 */
class ImagePlugin extends PluginAdapter {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'Image';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string {
        return 'Stellt ein einzelnes Bild aus der Medienverwaltung dar.';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string {
        return '1.0';
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string {
        return 'image';
    }

    /**
     * @inheritDoc
     */
    public function getAvailableParameters(): array {
        return [
            'mediumid'      => PluginParameterDefinition::fromArray([
                'key'       => 'mediumid',
                'label'     => 'Bilddatei',
                'type'      => PluginParameterDefinition::TYPE_MEDIAENTRY,
                'required'  => true
            ]),
            'format'        => PluginParameterDefinition::fromArray([
                'key'       => 'format',
                'label'     => 'Vorschau-Format',
                'type'      => PluginParameterDefinition::TYPE_MEDIAFORMAT,
                'default'   => 'preview',
                'required'  => true
            ])
        ];
    }

    /**
     * @inheritDoc
     * @throws InvalidStateException
     */
    protected function createPluginOptions(Dictionary $arguments): PluginOptions {
        $orm = $this->getModule()->getOrm();
        if ($orm === null) {
            throw new InvalidStateException('No orm instance configured');
        }

        $mediumId = (int)$arguments->getValue('mediumid');
        $formatKey = $arguments->getValue('format');
        if (empty($formatKey)) $formatKey = 'preview';

        $options = new ImagePluginOptions();
        $options->medium = $orm->getMediumById($mediumId);
        $options->previewImageFormat = $orm->getImageFormatByKey($formatKey);
        return $options;
    }
}