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

namespace report_lp\local\forms;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use moodleform;
use report_lp\local\persistents\item_configuration;

class item extends moodleform {

    protected $item;

    /**
     * Override parent constructor.
     *
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param null $ajaxformdata
     * @throws \coding_exception
     */
    public function __construct($action = null,
                                $customdata = null,
                                $method = 'post',
                                $target = '',
                                $attributes = null,
                                $editable = true,
                                $ajaxformdata = null) {
        global $CFG;
        // Need to have repository library and form element loaded for this to work.
        require_once($CFG->dirroot . '/repository/lib.php');
        if (!array_key_exists('item', $customdata)) {
            throw new coding_exception("The custom data 'item' key must be set.");
        }
        $item = $customdata['item'];
        $this->item = $item;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
        $this->set_data($this->get_default_values());
    }

    protected function definition() {
        global $DB;

        $mform = $this->_form;
        //$mform->addElement('header', 'general', get_string('general'));
        $mform->addElement('static', 'defaultlabel', get_string('defaultlabel', 'report_lp'));

        $mform->addElement('advcheckbox', 'usecustomlabel', '', get_string('usecustomlabel', 'report_lp'), null, [0, 1]);
        $mform->addElement('text', 'customlabel', get_string('customlabel', 'report_lp'));
        $mform->setType('customlabel', PARAM_TEXT);
        $mform->disabledIf('customlabel', 'usecustomlabel');

        $mform->addElement('advcheckbox', 'visibletosummary', '', get_string('visibletosummary', 'report_lp'), null, [0, 1]);
        $mform->addElement('advcheckbox', 'visibletoinstance', '', get_string('visibletoinstance', 'report_lp'), null, [0, 1]);
        $mform->addElement('advcheckbox', 'visibletolearner', '', get_string('visibletolearner', 'report_lp'), null, [0, 1]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    protected function get_default_values() {
        /** @var item_configuration $itemconfiguration */
        $itemconfiguration = $this->item->get_configuration();
        $data = $itemconfiguration->to_record();
        $defaults = [
            'defaultlabel' => $this->item->get_default_label(),
            'id' => $data->id,
            'courseid' => $data->courseid,
            'usecustomlabel' => $data->usecustomlabel,
            'customlabel' => $data->customlabel,
            'visibletosummary' => $data->visibletosummary,
            'visibletoinstance' => $data->visibletoinstance,
            'visibletolearner' => $data->visibletolearner
        ];
        return $defaults;

    }
}
