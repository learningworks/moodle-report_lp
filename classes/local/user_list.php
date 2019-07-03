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

use ArrayIterator;
use coding_exception;
use Countable;
use IteratorAggregate;
use stdClass;
use Traversable;
use context_course;

/**
 * Base for class for user lists.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class user_list implements Countable, IteratorAggregate {

    /**
     * @var array $users List of users keyed on user id. Can be ids and objects.
     */
    private $users = [];

    /**
     * @var stdClass $course Course object.
     */
    protected $course;

    /**
     * @var context_course Context class.
     */
    protected $context;

    /**
     * user_list constructor.
     *
     * @param stdClass $course
     */
    public function __construct(stdClass $course) {
        $this->course = $course;
        $this->context = context_course::instance($course->id);
    }

    protected function add_user(stdClass $user) : user_list {
        if (empty($user->id)) {
            throw new coding_exception("ID property required");
        }
        $this->users[$user->id] = $user;
        return $this;
    }

    protected function add_user_by_id(int $userid) {
        $this->users[$userid] = $userid;
        return $this;
    }

    /**
     * Return the number of users.
     */
    public function count() : int {
        return count($this->users);
    }

    /**
     * List of the default user fields.
     *
     * @return array
     */
    public static function get_default_user_fields() {
        $fields = [
            'id' => 'id',
            'email' => 'email',
            'idnumber' => 'idnumber',
            'firstaccess' => 'firstaccess',
            'lastaccess' => 'lastaccess',
            'lastlogin' => 'lastlogin',
            'currentlogin' => 'currentlogin',
            'picture' => 'picture',
            'imagealt' => 'imagealt'

        ];
        return array_merge($fields, get_all_user_name_fields());
    }

    /**
     * @return stdClass
     */
    public function get_course() : stdClass {
        return $this->course;
    }

    /**
     * @return context_course
     */
    public function get_context() : context_course {
        return $this->context;
    }

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->users);
    }

    /**
     * Get the list of user IDs.
     *
     * @return  int[]
     */
    public function get_userids() : array {
        return array_keys($this->users);
    }

}
