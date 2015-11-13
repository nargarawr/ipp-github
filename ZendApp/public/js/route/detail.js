/*
 * Set up callbacks for the share buttons
 */
var a2a_config = a2a_config || {};
a2a_config.callbacks = a2a_config.callbacks || [];
a2a_config.callbacks.push({
    share: my_addtoany_onshare
});

/**
 * Document ready function. Gets the start an end locations for the route
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: $('#start_point').attr('data-latlng'),
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        if (response.status == "OK") {
            $('#start_point').text(response.results[0].formatted_address)
        }
    });

    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: $('#end_point').attr('data-latlng'),
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        if (response.status == "OK") {
            $('#end_point').text(response.results[0].formatted_address)
        }
    });

    var cm = new CommentManager();
    var rm = new RatingManager();

    $('#reportRoute').click(function () {
        $.confirm({
            title:           'Report route?',
            icon:            'fa fa-flag',
            content:         'Please explain your reason for reporting this route' +
            '<textarea class="form-control" id="routeReportMessage"></textarea>',
            theme:           'black',
            confirmButton:   'Submit',
            keyboardEnabled: true,
            confirm:         function () {
                $.ajax({
                    type: 'POST',
                    url:  '/report/add',
                    data: {
                        id:     $('#routeId').val(),
                        type:   'route',
                        reason: $('#routeReportMessage').val()
                    }
                }).success(function (response) {
                    $.alert({
                        title:   'Thank you!',
                        icon:    'fa fa-smile-o',
                        content: 'Thank you for helping to make the Niceway.to community a better place!',
                        theme:   'black'
                    });
                });
            }
        });
    });

    //resizeSocialStream();
});

/**
 * Window resize function. Resizes the social steram
 *
 * @author Craig Knott
 */
$(window).resize(function () {
    //resizeSocialStream();
});

/**
 * Used to resize the social stream height based on space available
 *
 * @author Craig Knott
 */
function resizeSocialStream() {
    var socialStream = $('#socialStream');
    var streamElements = socialStream.find('.streamElements');

    var wiHeight = window.innerHeight - 80; // Minus padding/nav at the top of the page
    var tiHeight = socialStream.find('.title').outerHeight(true);
    var strHeight = socialStream.find('.shareThisRoute').outerHeight(true);
    var cbHeight = socialStream.find('.commentBox').outerHeight(true);
    if (cbHeight == null) {
        cbHeight = 0;
    }

    var availableSpace = wiHeight - tiHeight - strHeight - cbHeight;

    if (window.innerWidth < 991) {
        streamElements.css("max-height", "none");
    } else {
        streamElements.css("max-height", availableSpace + "px");
        streamElements.css("overflow", "auto");
    }
}

/**
 * Class RatingManager
 *
 * Class in charge of the rating system on the routes page
 *
 * @author Craig Knott
 */
var RatingManager = Class.extend({
    /**
     * Initialises this class and assigns member variables
     *
     * @author Craig Knott
     */
    init: function () {
        this.stars = $('.yourRating').find('.starDisplay');
        this.starStates = [];
        this.emailConfirmed = $('#email-confirmed').val() == 1;
        this.routeId = $('#routeId').val();
        this.socialStream = $('#socialStream').find('.streamElements').find('.elements');
        this.clearBtn = $('.clearBtn');

        for (var i = 0; i < this.stars.length; i++) {
            this.starStates.push(
                ($(this.stars[i]).hasClass('fa-star')) ? 'fa-star' : 'fa-star-o'
            );
        }

        this.setupListeners();
    },

    /**
     * Set up the listeners for the stars
     *
     * @author Craig Knott
     */
    setupListeners: function () {
        var _self = this;

        this.stars.mouseenter(function () {
            var index = $(this).attr('data-index');
            _self.fillStar($(this), index);

            for (var i = 0; i < index; i++) {
                _self.fillStar($(_self.stars[i]), i);
            }
        });

        this.stars.mouseleave(function () {
            var index = $(this).attr('data-index');
            _self.resetStar($(this), index);

            for (var i = 0; i < index; i++) {
                _self.resetStar($(_self.stars[i]), i);
            }
        });

        this.stars.click(function () {
            if (!(_self.emailConfirmed)) {
                $.alert({
                    title:           'Email address is not yet confirmed!',
                    icon:            'fa fa-envelope-o',
                    content:         'Unfortunately, commenting on routes, rating routes, and creating routes are reserved for users' +
                    ' with confirmed email addresses. Please check your email inbox for an email from us to confirm your email address.',
                    theme:           'black',
                    confirmButton:   'Okay',
                    keyboardEnabled: true
                });
                return;
            }

            var index = $(this).attr('data-index');
            _self.selectStar($(this), index);

            for (var i = 0; i < _self.stars.length; i++) {
                if (i <= index) {
                    _self.selectStar($(_self.stars[i]), i);
                } else {
                    _self.deselectStar($(_self.stars[i]), i);
                }
            }

            var rating = parseInt(index) + 1;
            $.ajax({
                type: 'POST',
                url:  '/rating/add',
                data: {
                    id:     _self.routeId,
                    rating: rating
                }
            }).success(function (response) {
                response = JSON.parse(response);
                _self.updateSocialStream(rating, response.username);
            });
        });

        this.clearBtn.click(function () {
            $.ajax({
                type: 'POST',
                url:  '/rating/remove',
                data: {
                    id: $('#ratingId').val()
                }
            }).success(function () {
                $('#userRating').parent().remove();
                for (var i = 0; i < _self.stars.length; i++) {
                    _self.deselectStar($(_self.stars[i]), i);
                }
            });
        });
    },

    /**
     * Sets a star as hovered
     *
     * @author Craig Knott
     *
     * @param star The star object to fill
     * @param index The index of this star object
     */
    fillStar: function (star, index) {
        star.addClass('starSelected');
        star.removeClass(this.starStates[index]);
        star.addClass('fa-star');
    },

    /**
     * Sets a star as non-hovered
     *
     * @author Craig Knott
     *
     * @param star The star object to fill
     * @param index The index of this star object
     */
    resetStar: function (star, index) {
        star.removeClass('starSelected');
        star.removeClass('fa-star');
        star.addClass(this.starStates[index]);
    },

    /**
     * Sets a star as selected but non-hovered
     *
     * @author Craig Knott
     *
     * @param star The star object to fill
     * @param index The index of this star object
     */
    selectStar: function (star, index) {
        star.removeClass('starSelected');
        star.removeClass(this.starStates[index]);
        this.starStates[index] = 'fa-star';
        star.addClass('fa-star');
    },

    /**
     * Sets a star as non-selected and non-hovered
     *
     * @author Craig Knott
     *
     * @param star The star object to fill
     * @param index The index of this star object
     */
    deselectStar: function (star, index) {
        star.removeClass('starSelected');
        star.removeClass(this.starStates[index]);
        this.starStates[index] = 'fa-star-o';
        star.addClass('fa-star-o');
    },

    /**
     * Includes a message in the social stream about this user rating the route
     *
     * @author Craig Knott
     *
     * @param rating The rating the user gave
     * @param username The username of the current user
     */
    updateSocialStream: function (rating, username) {
        var userRating = $('#userRating').parent();

        // If the user rating already exists, we should delete it so we can add a new one at the top
        if (userRating !== undefined) {
            userRating.remove();
        }

        var ratingForStream = $('<div>').addClass('streamElement rate');
        ratingForStream.html(
            '<i class="fa fa-star"></i>' +
            '<span class="bold"> ' + username + '</span> gave this route a rating of ' +
            '<span class="userRatingValue">' + rating + '</span> ' +
            '<i class="fa fa-star"></i>' +
            '<div class="hidden" id="userRating"></div>'
        );

        this.socialStream.prepend(ratingForStream);

    }
});

/**
 * Class CommentManager
 *
 * Class in charge of adding, updating, and removing comments from the social stream
 *
 * @author Craig Knott
 */
var CommentManager = Class.extend({
    /**
     * Initialises this class and assigns member variables
     *
     * @author Craig Knott
     */
    init:           function () {
        this.commentBtn = $('#comment-btn');
        this.comment = $('#comment-input');
        this.emailConfirmed = $('#email-confirmed').val() == 1;
        this.routeId = $('#routeId').val();
        this.socialStream = $('#socialStream').find('.streamElements').find('.elements');
        this.filterComments = $('#filterComments');
        this.filterCheckBox = $(filterComments).find('input');
        this.comments = [];

        this.setupListeners();
        this.getAllComments();
    },
    /**
     * Set up the click listener for the comment button
     *
     * @author Craig Knott
     */
    setupListeners: function () {
        var _self = this;

        this.commentBtn.unbind("click");
        this.commentBtn.click(function () {
            if (!(_self.emailConfirmed)) {
                $.alert({
                    title:           'Email address is not yet confirmed!',
                    icon:            'fa fa-envelope-o',
                    content:         'Unfortunately, commenting on routes, rating routes, and creating routes are reserved for users' +
                    ' with confirmed email addresses. Please check your email inbox for an email from us to confirm your email address.',
                    theme:           'black',
                    confirmButton:   'Okay',
                    keyboardEnabled: true
                });
                return;
            }

            _self.addComment(_self.comment.val());
            _self.getAllComments();
        });

        this.filterComments.unbind("click");
        this.filterComments.click(function(){
            $('.elements').children().each(function(){
                var checked = _self.filterCheckBox.prop('checked');

                if (!($(this).hasClass('comment'))) {
                    if (checked) {
                        $(this).addClass('hidden');
                    } else {
                        $(this).removeClass('hidden');
                    }
                }
            });
        });
    },
    /**
     * Adds the given comment to the database
     *
     * @author Craig Knott
     *
     * @param comment (string) The comment to add
     */
    addComment:     function (comment) {
        var _self = this;
        if (_self.comment.val() == "") {
            $.alert({
                title:           'Blank Comment!',
                icon:            'fa fa-comment-o',
                content:         'Your comment cannot be blank!',
                theme:           'black',
                confirmButton:   'Okay',
                keyboardEnabled: true
            });
        }

        _self.comment.val("");

        $.ajax({
            type: 'POST',
            url:  '/comment/add',
            data: {
                id:   this.routeId,
                text: comment
            }
        }).success(function (response) {
            response = JSON.parse(response);
            var newComment = $('<div>').addClass('streamElement comment').html(
                '<i class="fa fa-comment"></i> ' +
                '<span class="bold">' + response.username + '</span> ' +
                '<span class="right commentAction"> <i class="fa fa-times"></i></span>' +
                'says: <p> ' + comment + ' </p>' +
                '<input type="hidden" class="commentId" value="' + response.id + '">'
            );

            _self.socialStream.prepend(newComment);
            _self.getAllComments();
        });
    },
    /**
     * Finds all comments in the social stream and creates a comment object for them.
     * Be sure to call this after adding a new comment
     *
     * @author Craig Knott
     */
    getAllComments: function () {
        var comments = [];
        var commentNodes = this.socialStream.find('.comment');

        for (var i = 0; i < commentNodes.length; i++) {
            var c = new Comment(commentNodes[i]);
            comments.push(c);
        }

        this.comments = comments;
    }
});

/**
 * Class Comment
 *
 * Use to manage a specific comment and assign listeners to it
 *
 * @author Craig Knott
 */
var Comment = Class.extend({
    /**
     * Initialises the class and assigns member variables and listeners
     *
     * @author Craig Knott
     *
     * @param comment The comment DOM node this object represents
     */
    init:           function (comment) {
        this.comment = $(comment);
        this.deleteButton = this.comment.find('.fa-times');
        this.reportButton = this.comment.find('.fa-flag');
        this.commentId = this.comment.find('.commentId').val();

        this.setupListeners();
    },
    /**
     * Sets up the report and delete button listeners
     *
     * @author Craig Knott
     */
    setupListeners: function () {
        var _self = this;

        this.deleteButton.unbind('click');
        this.deleteButton.click(function () {
            $.confirm({
                title:           'Delete comment?',
                icon:            'fa fa-warning',
                content:         'Are you sure you wish to delete this comment? This action is irreversible.',
                theme:           'black',
                confirmButton:   'Delete',
                keyboardEnabled: true,
                confirm:         function () {
                    $.ajax({
                        type: 'POST',
                        url:  '/comment/delete',
                        data: {
                            id: _self.commentId
                        }
                    }).success(function () {
                        _self.comment.remove();
                    });
                }
            });
        });

        this.reportButton.unbind('click');
        this.reportButton.click(function () {
            $.confirm({
                title:           'Report comment?',
                icon:            'fa fa-flag',
                content:         'Please explain your reason for reporting this comment' +
                '<textarea class="form-control" id="commentReportMessage"></textarea>',
                theme:           'black',
                confirmButton:   'Submit',
                keyboardEnabled: true,
                confirm:         function () {
                    $.ajax({
                        type: 'POST',
                        url:  '/report/add',
                        data: {
                            id:     _self.commentId,
                            type:   'comment',
                            reason: $('#commentReportMessage').val()
                        }
                    }).success(function () {
                        $.alert({
                            title:   'Thank you!',
                            icon:    'fa fa-smile-o',
                            content: 'Thank you for helping to make the Niceway.to community a better place!',
                            theme:   'black'
                        });
                    });
                }
            });
        });

        this.reportButton.unbind('hover');
        this.reportButton.hover(function () {
            $(this).addClass('reportSelected');
        }, function () {
            $(this).removeClass('reportSelected');
        });

        this.deleteButton.unbind('hover');
        this.deleteButton.hover(function () {
            $(this).addClass('deleteSelected');
        }, function () {
            $(this).removeClass('deleteSelected');

        });
    }
});


/**
 * Callback function for when a route is shared
 *
 * @author Craig Knott
 *
 * @param data Information about the share
 */
function my_addtoany_onshare(data) {
    $.ajax({
        type: 'POST',
        url:  '/route/share',
        data: {
            id:       $('#routeId').val(),
            sharedTo: (data.service).toLowerCase()
        }
    }).success(function (response) {
        response = JSON.parse(response);

        var ele = $('<div>').addClass('streamElement share').html(
            '<i class="fa fa-share"></i> ' +
            '<i class="fa fa-' + (data.service).toLowerCase() + '-square"></i>' +
            '<span class="bold"> ' + response.username +  '</span> shared this route to ' + data.service
        );
        $('#socialStream').find('.elements').prepend(ele);
    });

}
