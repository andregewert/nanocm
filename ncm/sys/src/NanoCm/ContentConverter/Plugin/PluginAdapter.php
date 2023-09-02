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
use Ubergeek\NanoCm\Module\AbstractModule;

/**
 * Adapter class for content converter plugins that implements basic functions.
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2021-08-22
 */
abstract class PluginAdapter implements PluginInterface {

    // <editor-fold desc="Properties">

    /**
     * Reference to the currently executed NanoCM module
     * @var AbstractModule Currently executed NanoCm module
     */
    private AbstractModule $module;

    /**
     * Execution priority
     * @var int
     */
    private int $priority = 0;

    /**
     * True if this plugin is enabled
     * @var bool
     */
    private bool $enabled = true;

    // </editor-fold>


    // <editor-fold desc="Interface implementation">

    /**
     * @inheritDoc
     */
    public function setModule(AbstractModule $module) : void {
        $this->module = $module;
    }

    /**
     * @inheritDoc
     */
    public function getModule(): AbstractModule {
        return $this->module;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function setPriority(int $newPriority): void {
        $this->priority = $newPriority;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    /**
     * The base implementation should be reusable. It just passed the needed options to
     * the template renderer. The template file is named by the plugin.
     * @inheritDoc
     */
    public function replacePlaceholder(string $placeholder, Dictionary $arguments): string {
        try {
            $templateName = 'plugin' . DIRECTORY_SEPARATOR . strtolower($this->getPlaceholder()) . '.phtml';
            $options = $this->preparePluginOptions($placeholder, $arguments);
            $content = $this->getModule()->renderUserTemplate($templateName, $options);
        } catch (\Exception $ex) {
            $content = '[Error while rendering plugin]';
            $this->getModule()->err('Error while rendering plugin', $ex);
        }
        return $content;
    }

    /**
     * @inheritDoc
     */
    abstract public function getName(): string;

    /**
     * @inheritDoc
     */
    abstract public function getDescription(): string;

    /**
     * @inheritDoc
     */
    abstract public function getVersion(): string;

    /**
     * @inheritDoc
     */
    abstract public function getPlaceholder(): string;

    /**
     * @inheritDoc
     */
    public function getAvailableParameters(): array {
        return array();
    }

    // </editor-fold>


    // <editor-fold desc="Additional methods">

    /**
     * Prepares the options which are passed to the template renderer.
     * @param string $placeholder
     * @param Dictionary $arguments
     * @return PluginOptions
     */
    protected function preparePluginOptions(string $placeholder, Dictionary $arguments): PluginOptions {
        $options = new PluginOptions();
        $options->plugin = $this;
        $options->placeholder = $placeholder;
        $options->arguments = $arguments;
        $options->extended = new Dictionary();
        return $options;
    }

    // </editor-fold>

}