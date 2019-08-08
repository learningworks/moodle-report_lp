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
    'core/sortable_list',
    'core/ajax',
    'core/templates',
    'core/notification',
    'core/log'
],
function(
    $,
    SortableList,
    Ajax,
    Templates,
    Notification,
    Log
) {
    //var TEMPLATES = {
    //    LOADING_ICON: 'core/loading'
    //};

    var init = function(id, moveaction) {
        // Initialise sortable for the given list.
        Log.debug('Loaded');
        var sort = new SortableList('#' + id);
        sort.getElementName = function(element) {
            return $.Deferred().resolve(element.attr('data-name'));
        };
        var origIndex;
        $('#' + id + ' .draggable').on(SortableList.EVENTS.DRAGSTART, function(_, info) {
            // Remember position of the element in the beginning of dragging.
            origIndex = info.sourceList.children().index(info.element);
            // Resize the "proxy" element to be the same width as the main element.
            setTimeout(function() {
                $('.sortable-list-is-dragged').width(info.element.width());
            }, 501);
        }).on(SortableList.EVENTS.DROP, function(_, info) {
            // When a list element was moved send AJAX request to the server.
            var response = this;
            var newIndex = info.targetList.children().index(info.element);
            var s = info.element.find('[data-action=' + moveaction + ']');
            var ul;
            var li;
            if (info.positionChanged && s.length) {
                ul = s.closest('ul');
                li = s.closest('li');
                li.removeClass('depth-1 depth-2 depth-3'); // Hmmm, ok rushing it, not great.
                var request = {
                    methodname: 'report_lp_report_configuration_move_item',
                    args: {
                        courseid: ul.attr('data-course-id'),
                        itemid: li.attr('data-item-id'),
                        position: newIndex,
                        moveby: newIndex - origIndex,
                        total: s.length
                    }
                };
                var promise = Ajax.call([request])[0];
                promise.done(function(data){
                    response.data = data;
                    li.addClass('depth-' + response.data.depth);

                });
                promise.fail(Notification.exception);
            }
        });
    };

    return {
        init: init
    };
});
