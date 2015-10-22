$(document).ready(function () {
    var navBarHeight = 70;
    var constantTop = navBarHeight + 75;

    $('.bodypart').css({
        height: (($(window).height() - constantTop) / 4) + 'px'
    });

    $.ajax({
        url:     '/fitness/getallexercises',
        type:    'get',
        success: function (response) {
            var parsedJSON = JSON.parse(response);
            var lm = new listManager('#listGroup', parsedJSON);
            $('#loadingGif').addClass('hidden');
        }
    });
});

var listManager = Class.extend({
    init:         function (container, data) {
        this.container = $(container);
        this.data = data;
        this.maxDepth = 3;
        this.depth = 1;

        // Array that tracks the path we've taken
        this.path = [null, data, null, null];

        this.populateList(data);
    },
    getTitle:     function () {
        var title = '';
        if (this.depth == 1) {
            title += 'Select Body Part';
        } else if (this.depth == 2) {
            title += 'Select Muscle';
        } else {
            title += 'Select Exercise';
        }

        var listTitle = $('<div>')
            .addClass('list-group-item active text-center')
            .text(title);

        return listTitle;
    },
    populateList: function (data) {
        var _self = this;
        this.container.empty();

        _self.container.append(this.getTitle());
        if ($.isArray(data) == false) {
            data = $.map(data, function (value) {
                return [value];
            });
        }
        data.forEach(function (d) {
            var href = '#';
            if (_self.depth == _self.maxDepth) {
                href = '/fitness/set/exercise/' + d.id + '/workoutId/' + $('#workoutId').val();
            }

            var listItem = $('<a>')
                .attr('href', href)
                .addClass('list-group-item')
                .text(d.name);

            listItem.click(function () {
                if (_self.depth != _self.maxDepth) {
                    _self.depth++;
                    var data = null;
                    if (_self.depth == 2) {
                        data = d.muscles;
                    } else {
                        data = d.exercises;
                    }
                    _self.path[_self.depth] = data;
                    _self.populateList(data);
                }
            });
            _self.container.append(listItem);
        });

        var backButton = $('<a>')
            .attr('href', (this.depth > 1 ? '#' : '/fitness/workout/workoutId/' + $('#workoutId').val()))
            .addClass('list-group-item')
            .text(' Go Back');
        backButton.prepend(
            $('<i>').addClass('fa fa-lg fa-arrow-circle-left')
        );

        backButton.click(function () {
            if (_self.depth > 1) {
                _self.depth--;
                _self.populateList(_self.path[_self.depth]);
            }
        });
        this.container.append(backButton);
    }
});