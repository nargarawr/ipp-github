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
});

/**
 *
 */
var RatingManager = Class.extend({
    /**
     *
     */
    init:           function () {
        this.stars = $('.yourRating').find('.fa');
        this.starStates = [];
        this.emailConfirmed = $('#email-confirmed').val() == 1;
        this.routeId = $('#routeId').val();

        for (var i = 0; i < this.stars.length; i++) {
            this.starStates.push(
                ($(this.stars[i]).hasClass('fa-star')) ? 'fa-star' : 'fa-star-o'
            );
        }

        this.setupListeners();
    },
    /**
     *
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
            _self.emptyStar($(this), index);

            for (var i = 0; i < index; i++) {
                _self.emptyStar($(_self.stars[i]), i);
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

            $.ajax({
                type: 'POST',
                url:  '/rating/add',
                data: {
                    id:     _self.routeId,
                    rating: parseInt(index) + 1
                }
            }).success(function (response) {
                console.log(response);
            });
        });
    },
    /**
     *
     * @param star
     * @param index
     */
    fillStar:       function (star, index) {
        star.addClass('starSelected');
        star.removeClass(this.starStates[index]);
        star.addClass('fa-star');
    },
    /*
     *
     * @param star
     * @param index
     */
    emptyStar:      function (star, index) {
        star.removeClass('starSelected');
        star.removeClass('fa-star');
        star.addClass(this.starStates[index]);
    },
    /**
     *
     * @param star
     * @param index
     */
    selectStar:     function (star, index) {
        star.removeClass('starSelected');
        star.removeClass(this.starStates[index]);
        this.starStates[index] = 'fa-star';
        star.addClass('fa-star');
    },
    /**
     *
     * @param star
     * @param index
     */
    deselectStar:   function (star, index) {
        star.removeClass('starSelected');
        star.removeClass(this.starStates[index]);
        this.starStates[index] = 'fa-star-o';
        star.addClass('fa-star-o');
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

