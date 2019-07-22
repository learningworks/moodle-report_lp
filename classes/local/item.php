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
use stdClass;

/**
 * The base class that classes must extend.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class item {

    /** @var null SHORT_NAME Can be used to override unique short name. */
    public const SHORT_NAME = null;

    /** @var string COMPONENT_TYPE Used to identify core or plugin type. */
    public const COMPONENT_TYPE = null;

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. */
    public const COMPONENT_NAME = null;

    /** @var stdClass $course The associated course object. */
    private $course;

    /** @var item|null The parent item of this item. If no item will return null. */
    private $parent;

    /** @var item_configuration $configuration The associated persistent class. */
    private $configuration;

    /**
     * item constructor.
     *
     * @param stdClass|null $course
     * @param item_configuration|null $configuration
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function __construct(stdClass $course = null, item_configuration $configuration = null) {
        if (!is_null($course)) {
            $this->course = $course;
        }
        // Validate configuration.
        if (!is_null($configuration)) {
            $this->set_configuration($configuration);
        }
    }

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
     * @throws coding_exception
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
     * The default text for heading or column heading for this item.
     *
     * @return string
     */
    abstract public function get_default_label() : string;

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
     * Gets custom label if set else fall back to default label. To be called
     * by reports not report configuration.
     *
     * @return string
     * @throws coding_exception
     */
    final public function get_label() : string {
         $configuration = $this->get_configuration();
         if (is_null($configuration)) {
            throw new coding_exception("Configuration not loaded");
         }
         if ($configuration->get('usecustomlabel')) {
             $label = $configuration->get('customlabel');
             return format_text($label, FORMAT_PLAIN);
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
        if (is_null($this->course)) {
            throw new coding_exception('Course property must be set first');
        }
        if ($configuration->get('courseid') != $this->course->id) {
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

    final public function set_parent(item $parent = null) {
        $this->parent = $parent;
    }

    /**
     * Get associated course object.
     *
     * @return mixed
     * @throws coding_exception
     */
    final public function get_course() : stdClass {
        if (is_null($this->course)) {
            throw new coding_exception('Course property not set');
        }
        return $this->course;
    }

    /**
     * Set associated course object.
     *
     * @param stdClass $course
     */
    final public function set_course(stdClass $course) {
        $this->course = $course;
    }

    /**
     * Relative class name.
     *
     * @return string
     * @throws \ReflectionException
     * @throws coding_exception
     */
    final public static function get_class_name() : string {
        return get_class(new static());
    }

    public function get_depth() {
        if (is_null($this->configuration)) {
            return null;
        }
        return $this->configuration->get('depth');
    }

    public function get_sort_order() {
        if (is_null($this->configuration)) {
            return null;
        }
        return $this->configuration->get('sortorder');
    }

    /**
     * Items/Measures to override this method..
     *
     * Allow each item to determine if they are enabled. For modules the common
     * practise is to use plugin manager to determine if enabled or not, however
     * this method allows another level of control possibly disabling via a $CFG
     * variable.
     *
     * @return bool|null
     */
    public function is_enabled() {
        return false;
    }

    /**
     * @return bool|null
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function is_root_item() {
        if (is_null($this->configuration)) {
            return null;
        }
        $isgrouping = ($this->configuration->get('shortname') != grouping::get_short_name()) ? false : true;
        $firstleveldepth = ($this->configuration->get('depth') != 1) ? false : true;
        $noparent = ($this->configuration->get('parentitemid') != 0) ? false : true;
        $isfirstpath = ($this->configuration->get('id') != $this->configuration->get('path')) ? false : true;
        if ($isgrouping && $firstleveldepth && $noparent && $isfirstpath) {
            return true;
        }
        return false;
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
