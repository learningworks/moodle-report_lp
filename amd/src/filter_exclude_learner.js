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

define(
    [
        'jquery',
        'core/custom_interaction_events',
        'core/ajax',
        'core/notification',
        'core/str',
        'core/log'
    ],
    function(
        $,
        customEvents,
        Ajax,
        Notification,
        Str,
        Log
    ) {

        var SELECTORS = {
            LIST_CONTAINER: '[data-container="learners"]',
            EXCLUDED_LIST_CONTAINER: '[data-container="excluded"]',
            EXCLUDED_LIST: '[data-list="excluded"]',
            EXCLUDED_LIST_RESET: '[data-action="excluded-reset"]',
            ROW: '[data-row]',
            DESCENDANTS: '[data-filter="exclude-learner"]',
            USER_ID: 'data-learner-id',
            COURSE_ID: 'data-course-id'
        };

        var registerSelector = function(selector) {
            var response = this;
            customEvents.define(selector, [customEvents.events.activate]);
            selector.on(
                customEvents.events.activate,
                SELECTORS.DESCENDANTS,
                function(e, data) {
                    var row = $(e.target).closest(SELECTORS.ROW);
                    var userId = row.attr(SELECTORS.USER_ID);
                    var courseId = $(SELECTORS.LIST_CONTAINER).attr(SELECTORS.COURSE_ID);
                    var request = {
                        methodname: 'report_lp_filter_exclude_learner_add_learner',
                        args: {
                            courseid: courseId,
                            userid: userId,
                        }
                    };
                    if (userId === "") {
                        throw "Can not find data-attribute " + SELECTORS.USER_ID;
                    }
                    var promise = Ajax.call([request])[0];
                    promise.done(function(data){
                        response.data = data;
                        var fullnames = [];
                        if (data !== undefined || data.length > 0) {
                            fullnames = data.map(function(user){
                                return user.fullname;
                            });
                        }
                        $(SELECTORS.EXCLUDED_LIST).text(fullnames.join(", "));
                        $(SELECTORS.EXCLUDED_LIST_CONTAINER).removeClass('d-none');
                        row.addClass('d-none');
                    });
                    promise.fail(Notification.exception);
                    data.originalEvent.preventDefault();
            });
            selector.on(
                customEvents.events.activate,
                SELECTORS.EXCLUDED_LIST_RESET,
                function(e, data) {
                    var excludedContainer = $(SELECTORS.EXCLUDED_LIST_CONTAINER);
                    var courseId = excludedContainer.attr(SELECTORS.COURSE_ID);
                    var request = {
                        methodname: 'report_lp_filter_exclude_learner_reset',
                        args: {
                            courseid: courseId
                        }
                    };
                    var promise = Ajax.call([request])[0];
                    promise.done(function(data){
                        response.data = data;
                        document.location.reload();

                    });
                    promise.fail(Notification.exception);
                    data.originalEvent.preventDefault();
                }
            );
        };

        /**
         *
         */
        var init = function(selector) {
            Log.debug('Initialising ' + selector);
            selector = $(selector);
            registerSelector(selector);
        };

        return {
            init: init
        };
    }
);
