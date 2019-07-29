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
use report_lp\local\contracts\extra_configuration;
use report_lp\local\grouping;
use report_lp\local\measure;
use report_lp\local\item_type_list;
use stdClass;
use report_lp\local\persistents\item_configuration;
use report_lp\local\factories\item as item_factory;

class item extends moodleform {

    protected $course;

    protected $item;

    protected $rootitem;

    protected $itemfactory;

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
     * @throws \ReflectionException
     * @throws coding_exception
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
        // Check and set course object.
        if (!array_key_exists('course', $customdata)) {
            throw new coding_exception("The custom data 'course' key must be set.");
        }
        $course = $customdata['course'];
        if (!($course instanceof stdClass)) {
            throw new coding_exception("The course variable must be valid course object.");
        }
        $this->course = $course;
        // Check and set item class.
        if (!array_key_exists('item', $customdata)) {
            throw new coding_exception("The custom data 'item' key must be set.");
        }
        $item = $customdata['item'];
        if (!is_subclass_of($item, 'report_lp\local\item')) {
            throw new coding_exception("The variable 'item' must be of subclass of item class.");
        }
        $this->item = $item;
        $this->itemfactory = new item_factory($this->course, new item_type_list());
        $this->rootitem = $this->itemfactory->get_root_grouping();
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
        $this->set_data($this->get_default_values());
    }

    /**
     * Form element definition.
     *
     * @throws coding_exception
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('generalsettings', 'report_lp'));

        if ($this->item instanceof grouping) {
            $mform->addElement('hidden', 'parentitemid');
            $mform->setType('parentitemid', PARAM_INT);
        } else {
            $options = $this->get_grouping_options();
            $mform->addElement('select', 'parentitemid', get_string('parentgrouping', 'report_lp'), $options);
        }

        $mform->addElement('static', 'description', get_string('description'));
        $mform->addElement('static', 'defaultlabel', get_string('defaultlabel', 'report_lp'));
        $mform->addElement('advcheckbox', 'usecustomlabel', '', get_string('usecustomlabel', 'report_lp'), null, [0, 1]);
        $mform->addElement('text', 'customlabel', get_string('customlabel', 'report_lp'));
        $mform->setType('customlabel', PARAM_TEXT);
        $mform->disabledIf('customlabel', 'usecustomlabel');
        $mform->addElement('advcheckbox', 'visibletosummary', '', get_string('visibletosummary', 'report_lp'), null, [0, 1]);
        $mform->disabledIf('visibletosummary', 'disabled', 'eq', 1);
        $mform->addElement('advcheckbox', 'visibletoinstance', '', get_string('visibletoinstance', 'report_lp'), null, [0, 1]);
        $mform->disabledIf('visibletoinstance', 'disabled', 'eq', 1);
        $mform->addElement('advcheckbox', 'visibletolearner', '', get_string('visibletolearner', 'report_lp'), null, [0, 1]);
        $mform->disabledIf('visibletolearner', 'disabled', 'eq', 1);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'shortname');
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        // Used for functionality not yet implemented.
        $mform->addElement('hidden', 'disabled');
        $mform->setType('disabled', PARAM_INT);
        $mform->setDefault('disabled', 1);

        // Has own configuration settings, apply form custom elements.
        if ($this->item instanceof extra_configuration) {
            $mform->addElement('header', 'specific', get_string('specificsettings', 'report_lp'));
            $this->item->moodlequickform_extend($mform);
        }

        $this->add_action_buttons();
    }

    /**
     * Set default values on form elements.
     *
     * @return array
     * @throws coding_exception
     */
    protected function get_default_values() {
        /** @var item_configuration $itemconfiguration */
        $itemconfiguration = $this->item->get_configuration();
        $data = $itemconfiguration->to_record();
        $defaults = [
            'description' => $this->item->get_description(),
            'defaultlabel' => $this->item->get_default_label(),
            'id' => $data->id,
            'courseid' => $data->courseid,
            'shortname' => $data->shortname,
            'usecustomlabel' => $data->usecustomlabel,
            'customlabel' => $data->customlabel,
            'parentitemid' => $data->parentitemid,
            'visibletosummary' => $data->visibletosummary,
            'visibletoinstance' => $data->visibletoinstance,
            'visibletolearner' => $data->visibletolearner,
        ];
        // Grouping always have a parentitemid of the root configurations as only depth of 2 levels supported.
        if ($this->item instanceof grouping) {
            $defaults['parentitemid'] = $this->rootitem->get_id();
        }
        // Include custom defaults for item with own configuration.
        if ($this->item instanceof extra_configuration) {
            $itemdefaults = $this->item->moodlequickform_get_extra_configuration_defaults();
            $defaults = array_merge($itemdefaults, $defaults);
        }
        return $defaults;
    }

    /**
     * Make a menu of groupings.
     *
     * @return array
     * @throws coding_exception
     */
    protected function get_grouping_options() {
        $options = [];
        $groupings = $this->itemfactory->get_grouping_from_item_configurations();
        foreach ($groupings as $grouping) {
            $options[$grouping->get_id()] = $grouping->get_label();
        }
        return $options;
    }

    /**
     * Validation, hook in validation call backs for any measures that have own
     * configuration.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $itemerrors = [];
        if ($this->item instanceof extra_configuration) {
            $itemerrors = $this->item->moodlequickform_validation($data, $files);
        }
        return array_merge($errors, $itemerrors);
    }

    /**
     * Get extra configuration data for any measures implementing their own
     * configuration via call backs. Based of get_data().
     *
     * @return array
     */
    public function get_extra_configuration_data() {
        if ($this->item instanceof extra_configuration) {
            $mform = $this->_form;
            if (!$this->is_cancelled() and $this->is_submitted() and $this->is_validated()) {
                $data = $mform->exportValues();
                unset($data['sesskey']);
                unset($data['_qf__'.$this->_formname]);
                unset($data['submitbutton']);
                return $this->item->moodlequickform_get_extra_configuration_data($data);
            }
        }
        return [];
    }

}
