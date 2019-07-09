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
            LIST_CONTAINER: '[data-excluded]',
            ROW: '[data-row]',
            DESCENDANTS: '[data-filter]',
            ID_ATTRIBUTE_NAME: 'data-learner-id'
        };

        var registerSelector = function(selector) {
            customEvents.define(selector, [customEvents.events.activate]);
            selector.on(
                customEvents.events.activate,
                SELECTORS.DESCENDANTS,
                function(e, data) {
                    var btn = $(e.target).closest(SELECTORS.DESCENDANTS);
                    var userId = btn.attr(SELECTORS.ID_ATTRIBUTE_NAME);
                    var datarow = btn.closest(SELECTORS.ROW);
                    datarow.toggleClass('d-none');
                    data.originalEvent.preventDefault();
            });
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
