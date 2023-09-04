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
 * This plugin is used to show download links for files which are manages by the Nano CM media manager.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2023-09-05
 */
class DownloadPlugin extends PluginAdapter {

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'Download-Link';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string {
        return 'Bindet einen Download-Link in den Inhalt ein.';
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
        return 'download';
    }

    /**
     * @inheritDoc
     */
    public function getAvailableParameters(): array {
        return [
            'mediumid'      => PluginParameterDefinition::fromArray([
                'key'       => 'mediumid',
                'label'     => 'Download-Datei',
                'type'      => PluginParameterDefinition::TYPE_MEDIAENTRY,
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
        $options = new DownloadPluginOptions();
        $options->medium = $orm->getMediumById($mediumId);
        return $options;
    }
}