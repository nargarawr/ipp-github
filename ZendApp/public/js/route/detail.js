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

    var commentManager = new CommentManager();
});


/**
 * Class CommentManager
 *
 * Class in charge of adding, updating, and removing comments from the social stream
 *
 * @author Craig Knott
 */
var CommentManager = Class.extend({
    init:           function () {
        this.commentBtn = $('#comment-btn');
        this.emailConfirmed = $('#email-confirmed').val() == 1;

        this.setupListeners();
    },
    setupListeners: function () {
        var _self = this;
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

        });
    }
});

