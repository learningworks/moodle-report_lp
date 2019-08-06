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

use coding_exception;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class pagination implements renderable, templatable {

    public const MAXIMUM_PAGES_TO_DISPLAY = 10;

    public const MAXIMUM_RETURNED_RECORDS_LIMIT = 250;

    public const DEFAULT_RETURNED_RECORDS_LIMIT = 10;

    public $pageparameter = 'page';

    public $perpageparameter = 'perpage';

    private $total = 0;

    private $uniqueidentifier;

    /** @var moodle_url $url */
    private $url;

    public function __construct($uniqueidentifier = null, int $total = null, moodle_url $url = null) {
        if (is_null($uniqueidentifier)) {
            $this->uniqueidentifier = $this->generate_unique_identifier();
        } else {
            $this->uniqueidentifier = $uniqueidentifier;
        }
        $this->initialise_session_pagination();
        if (!is_null($total)) {
            $this->set_total($total);
        }
        if (!is_null($url)) {
            $this->set_url($url);
        }
        return $this;
    }

    public function clear_session_pagination() {
        global $SESSION;
        unset($SESSION->report_lp_pagination[$this->uniqueidentifier]);
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $pages = [];
        $url = $this->url;

        $currentpage = $this->get_current_page();
        // Build previous page.
        $previouspage = new stdClass();
        $previouspage->text = get_string('previous');
        if ($currentpage != $this->get_first_page()) {
            $previous = $currentpage - 1;
            $url->param($this->pageparameter, $previous);
            $previouspage->url = $url->out(false);
        } else {
            $previouspage->url = '#';
            $previouspage->disabled = true;
        }
        $data->previouspage = $previouspage;
        // Build pages.
        for ($page = 0; $page <= $this->get_last_page(); $page++) {
            $link = new stdClass();
            $text = $page + 1;
            $link->text = $text;
            $url->param($this->pageparameter, $page);
            $link->url = $url->out(false);
            if ($page == $currentpage) {
                $link->active = true;
            }
            $pages[] = $link;

        }
        // Add pages.
        $data->pages = $pages;
        // Build next page.
        $nextpage = new stdClass();
        $nextpage->text = get_string('next');
        if ($currentpage != $this->get_last_page()) {
            $next = $currentpage + 1;
            $url->param($this->pageparameter, $next);
            $nextpage->url = $url->out(false);
        } else {
            $nextpage->url = '#';
            $nextpage->disabled = true;
        }
        // Add next page.
        $data->nextpage = $nextpage;

        return $data;
    }

    /**
     * Available page count. As this stage 0 is also considered
     * a page.
     *
     * @return int
     */
    public function get_available_page_count() : int {
        return ceil($this->get_total() / $this->get_current_limit());
    }

    public function get_current_page() {
        $pagination = $this->get_session_pagination();
        return $pagination->page;
    }

    public function get_current_limit() {
        $pagination = $this->get_session_pagination();
        return $pagination->limit;
    }

    public function get_first_page() {
        return 0;
    }

    public function get_last_page() {
        return $this->get_available_page_count() - 1;
    }


    public function get_total() : ? int {
        return $this->total;
    }

    private function get_session_pagination() {
        global $SESSION;
        if (!isset($SESSION->report_lp_pagination[$this->uniqueidentifier])) {
            throw new coding_exception('No session data found');
        }
        $pagination = $SESSION->report_lp_pagination[$this->uniqueidentifier];
        if (!isset($pagination->page) || !isset($pagination->limit)) {
            throw new coding_exception('Session parameters not set');
        }
        return $pagination;
    }

    public function get_unique_identifier() {
        return $this->uniqueidentifier;
    }

    public function generate_unique_identifier() {
        return uniqid('pagination_');
    }

    public function has_next() {
        $pagination = $this->get_session_pagination();
        $next = $pagination->page + 1;
        $availablepagecount = $this->get_last_page();
        if ($next <= $availablepagecount) {
            return true;
        }
        return false;
    }

    private function initialise_session_pagination() {
        global $SESSION;
        if (!isset($SESSION->report_lp_pagination[$this->uniqueidentifier])) {
            $pagination = new stdClass();
            $pagination->page = 0;
            $pagination->limit = self::DEFAULT_RETURNED_RECORDS_LIMIT;
            $this->set_session_pagination($pagination);
        }
    }

    private function set_session_pagination(stdClass $pagination) {
        global $SESSION;
        $SESSION->report_lp_pagination[$this->uniqueidentifier] = $pagination;
    }

    public function set_page(int $page) {
        if (empty($this->total)) {
            throw new coding_exception('Total must must be set first');
        }
        if ($page < 0) {
            throw new coding_exception("Value less than minimum available page");
        }
        $availablepagecount = $this->get_available_page_count();
        if ($page > $availablepagecount) {
            throw new coding_exception("Value greater than maximum available page");
        }
        $pagination = $this->get_session_pagination();
        $pagination->page = $page;
        $this->set_session_pagination($pagination);
    }

    public function set_limit(int $limit) {
        if (empty($this->total)) {
            throw new coding_exception('Total must must be set first');
        }
        if ($limit > self::MAXIMUM_RETURNED_RECORDS_LIMIT) {
            $limit = self::MAXIMUM_RETURNED_RECORDS_LIMIT;
        }
        $pagination = $this->get_session_pagination();
        // If limit has changed we need to reset current page.
        if ($pagination->limit != $limit) {
            $pagination->page = 0;
        }
        $pagination->limit = $limit;
        $this->set_session_pagination($pagination);
        return $this;
    }

    /**
     * Set total avaiable records used to calculate pages.
     *
     * @param int $total
     * @return $this
     * @throws coding_exception
     */
    public function set_total(int $total) {
        if ($total < 0) {
            throw new coding_exception("Value must be greater than or equal to zero");
        }
        $this->total = $total;
        return $this;
    }

    private function set_url(moodle_url $url) {
        $this->url = $url;
        $perpageparameter = optional_param($this->perpageparameter, self::DEFAULT_RETURNED_RECORDS_LIMIT, PARAM_INT);
        $this->set_limit($perpageparameter);
        $pageparametervalue = optional_param($this->pageparameter, $this->get_first_page(), PARAM_INT);
        $this->set_page($pageparametervalue);
    }

}
