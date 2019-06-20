<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_lp\local;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use moodle_url;
use pix_icon;
use report_lp\local\persistents\item_configuration;
use ReflectionClass;

/**
 * The base class that classes must extend.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class item {

    /** @var null SHORT_NAME Can be used to override unique short name. */
    protected const SHORT_NAME = null;

    /**
     * @var item_configuration $configuration Holds configuration information associated with this item.
     */
    private $configuration;

    /**
     * Human readable name for item. For example, Grouping, Last course access.
     *
     * @return string
     */
    abstract public function get_name() : string;

    /**
     * Use reflection class to get extending classes shortname.
     *
     * @return string
     * @throws \ReflectionException
     */
    public static function get_short_name() : string {
        $item = new static();
        if (is_null(static::SHORT_NAME)) {
            $reflection = new ReflectionClass($item);
             return $reflection->getShortName();
        }
        return static::SHORT_NAME;
    }

    /**
     * The default text for heading or column heading for this item. This will likely be
     * dependent on information stored in $configuration.
     *
     * @param string $format
     * @return string
     */
    abstract public function get_default_label($format = FORMAT_PLAIN) : string;

    /**
     * Get human friendly description of what this item does.
     *
     * @return string
     */
    abstract public function get_description() : string;

    /**
     * Child class to overide if supports feature. Only call if has_icon().
     *
     * @return pix_icon|null
     */
    public function get_icon() : ? pix_icon {
        return null;
    }

    /**
     * Gets custom label if used or will get contretes default.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_label() : string {
        if (is_null($this->configuration)) {
            return $this->get_default_label();
        }
        if ($this->configuration->get('usecustomlabel')) {
            return $this->configuration->get('customlabel');
        }
        return $this->get_default_label();
    }

    /**
     * Child class to overide if supports feature. Only call if has_url().
     *
     * @return moodle_url|null
     */
    public function get_url() : ? moodle_url {
        return null;
    }

    /**
     * Return the associated configuration persistent for item.
     *
     * @return item_configuration
     */
    final public function get_configuration() : item_configuration {
        return $this->configuration ;
    }

    /**
     * Validate and set the associated configuration persistent for item.
     *
     * @param item_configuration $configuration
     * @throws \ReflectionException
     * @throws coding_exception
     */
    final public function set_configuration(item_configuration $configuration) {
        if ($configuration->get('courseid') <= 0) {
            throw new coding_exception('Invalid courseid in configuration');
        }
        if ($configuration->get('classname') != static::get_class_name()) {
            throw new coding_exception('Invalid class name in configuration');
        }
        if ($configuration->get('shortname') != static::get_short_name()) {
            throw new coding_exception('Invalid short name in configuration');
        }
        $this->configuration = $configuration;
    }

    /**
     * Relative class name.
     *
     * @return string
     */
    final public static function get_class_name() : string {
        return get_class(new static());
    }

    /**
     * Child class to overide if supports feature.
     *
     * @return bool
     */
    public function has_icon() : bool {
        return false;
    }

    /**
     * Child class to overide if supports feature.
     *
     * @return bool
     */
    public function has_url() : bool {
        return false;
    }

}
