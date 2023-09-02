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

namespace Ubergeek;

use Exception;
use Iterator;

/**
 * Simple dictionary implementation.
 *
 * @author André Gewert <agewert@ubergeek.de>
 * @created 2023-09-03
 */
class Dictionary implements Iterator {

    /**
     * @var KeyValuePair[]
     */
    private $values = array();

    private $iteratorPosition = 0;

    /**
     * Returns all dictionary keys.
     * @return string[]
     */
    public function keys(): array {
        return array_keys($this->values);
    }

    /**
     * Return all dictionary values.
     * @return array
     */
    public function values(): array {
        $values = array();
        foreach ($this->values as $keyValuePair) {
            $values[] = $keyValuePair->value;
        }
        return $values;
    }

    /**
     * Adds a new item to the dictionary.
     * If the given key already exists, an exception will be thrown. Use set() if you want to overwrite existing values.
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function add(string $key, mixed $value): void {
        if (in_array($key, $this->keys())) {
            throw new Exception("Key already exists: $key");
        }
        $this->values[$key] = new KeyValuePair($key, $value);
    }

    /**
     * Adds or sets the value of a dictionary item.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void {
        $this->values[$key] = new KeyValuePair($key, $value);
    }

    /**
     * Gets the value of a specified dictionary entry.
     * @param string $key
     * @param mixed|null $default Default value if the dictionary entry does not exist
     * @return mixed|null
     */
    public function getValue(string $key, mixed $default = null): mixed {
        if (!in_array($key, $this->keys())) {
            return $default;
        }
        return $this->values[$key]->value;
    }

    /**
     * Gets the specified dictionary entry if it exists.
     * If the specified entry does not exist an exception will be thrown.
     * @param string $key
     * @return KeyValuePair
     * @throws Exception
     */
    public function get($key) {
        if (!in_array($key, $this->keys())) {
            throw new Exception("Key does not exist: $key");
        }
        return $this->values[$key];
    }

    /**
     * Returns the complete list of dictionary entries.
     * @return KeyValuePair[]
     */
    public function getItems() {
        return $this->values;
    }

    // <editor-fold desc="Interface Iterator">

    /**
     * Returns the current key value pair.
     * @return KeyValuePair
     */
    public function current(): ?KeyValuePair {
        if ($this->iteratorPosition < count($this->values)) {
            $key = $this->keys()[$this->iteratorPosition];
            return $this->values[$key];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function next(): void {
        ++$this->iteratorPosition;
    }

    /**
     * Returns the current key string.
     * @return string
     */
    public function key(): ?string {
        if ($this->iteratorPosition < count($this->values)) {
            return $this->keys()[$this->iteratorPosition];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool {
        return $this->iteratorPosition < count($this->values);
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void {
        $this->iteratorPosition = 0;
    }

    // </editor-fold>

}
