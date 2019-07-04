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
            LIST_CONTAINER: '[data-active-menu]',
            DESCENDANTS: '[data-filter]',
            ID_ATTRIBUTE_NAME: 'data-group-id',
            ACTIVE_TEXT: '[data-active-text]'
        };

        /**
         * Borrowed from Ryan Wyllie's JavaScript work in 3.6.
         */
        var registerSelector = function(selector) {
            customEvents.define(selector, [customEvents.events.activate]);
            selector.on(customEvents.events.activate, SELECTORS.DESCENDANTS, function(e, data) {
                var activeGroupNames = [];
                var activeGroupIds = [];
                var option = $(e.target);
                var listContainer = option.closest(SELECTORS.LIST_CONTAINER);
                // Ignore non Bootstrap dropdowns.
                if (!option.hasClass('dropdown-item')) {
                    return;
                }
                option.toggleClass('active');
                var dropdownItems = listContainer.find('.active');
                if (dropdownItems.length) {
                    dropdownItems.each(function(index, element){
                        var groupId = element.getAttribute(SELECTORS.ID_ATTRIBUTE_NAME);
                        activeGroupIds.push(groupId);
                        var groupName = element.innerText;
                        activeGroupNames.push(groupName);
                    });
                }
                var dropdownToggle = listContainer.parent().find('[data-toggle="dropdown"]');
                var dropdownToggleText = dropdownToggle.find(SELECTORS.ACTIVE_TEXT);
                if (dropdownToggleText.length) {
                    if (activeGroupNames.length) {
                        dropdownToggleText.html(activeGroupNames.join(', '));
                    } else {
                        dropdownToggleText.html('None');
                    }
                }
                // @todo Ajax.
                data.originalEvent.preventDefault();
            });
        };

        /**
         *
         */
        var init = function(selector) {
            Log.debug('Initialising filter ' + selector);
            selector = $(selector);
            registerSelector(selector);
        };

        /**
         *
         */
        return {
            init: init
        };
    });
