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

    protected $shortname = null;

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
     * @return string
     * @throws \ReflectionException
     */
    public function get_short_name() : string {
        if (is_null($this->shortname)) {
            $reflection = new ReflectionClass($this);
            $this->shortname = $reflection->getShortName();
        }
        return $this->shortname;
    }

    /**
     * The default text for heading or column heading for this item. This will likely be
     * dependent on information stored in $configuration.
     *
     * @return string
     */
    abstract public function get_default_label() : ? string;

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
     * Set the associated configuration persistent for item.
     *
     * @param item_configuration $configuration
     */
    final public function set_configuration(item_configuration $configuration) {
        $this->configuration = $configuration;
    }

    final public function get_class_name() : string {
        return get_class($this);
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
