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
    var sm = new SavingManager();
    var rcm = new RecommendationManager();
    var cfm = new ConfirmationManager();

    $(".elements").css("height", window.innerHeight - 350);

    drawMap();
});

/**
 * Draws the map and adds the map points to it
 *
 * @author Craig Knott
 */
function drawMap() {
    $('#map').height('400px');

    map = L.map('map', {zoomControl: false}).setView([52, -1.1], 13);
    new L.Control.Zoom({position: 'topright'}).addTo(map);

    var mapId = 'nargarawr.cig6xoyv103gnvbkvyv7s6a0k';
    var token = 'pk.eyJ1IjoibmFyZ2FyYXdyIiwiYSI6ImNpZzZ4b3l6MzAzZzF2cWt2djg4d3llZDMifQ.k5f5mW8zW3VBH40GUYS-8A';

    var c = L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        maxZoom:     18,
        id:          mapId,
        accessToken: token
    }).addTo(map);

    // Snapping Layer
    var snapping = new L.geoJson(null, {
        style: {
            opacity:     0
            , clickable: false
        }
    }).addTo(map);

    map.on('moveend', function () {
        if (map.getZoom() > 12) {
            var proxy = 'http://www2.turistforeningen.no/routing.php?url=';
            var route = 'http://www.openstreetmap.org/api/0.6/map';
            var params = '&bbox=' + map.getBounds().toBBoxString() + '&1=2';
            $.get(proxy + route + params).always(function (osm, status) {
                if (status === 'success' && typeof osm === 'object') {
                    var geojson = osmtogeojson(osm);

                    snapping.clearLayers();
                    for (var i = 0; i < geojson.features.length; i++) {
                        var feat = geojson.features[i];
                        if (feat.geometry.type === 'LineString' && feat.properties.tags.highway) {
                            snapping.addData(geojson.features[i]);
                        }
                    }
                }
            });
        } else {
            snapping.clearLayers();
        }
    });
    map.fire('moveend');

    // OSM Router
    var router = function (m1, m2, cb) {
        var proxy = 'http://www2.turistforeningen.no/routing.php?url=';
        var route = 'http://www.yournavigation.org/api/1.0/gosmore.php&format=geojson&v=car&fast=1&layer=mapnik';
        var params = '&flat=' + m1.lat + '&flon=' + m1.lng + '&tlat=' + m2.lat + '&tlon=' + m2.lng;
        $.getJSON(proxy + route + params, function (geojson, status) {
            if (!geojson || !geojson.coordinates || geojson.coordinates.length === 0) {
                if (typeof console.log === 'function') {
                    console.log('OSM router failed', geojson);
                }
                return cb(new Error());
            }
            return cb(null, L.GeoJSON.geometryToLayer(geojson));
        });
    };

    var routing = new L.Routing({
        position:   'topleft'
        , routing:  {
            router: router
        }
        , snapping: {
            layers: []
        }
        , snapping: {
            layers:        [snapping]
            , sensitivity: 15
            , vertexonly:  false
        }
    });
    map.addControl(routing);
    routing.draw();
}

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
            '<span class="bold"> ' + response.username + '</span> shared this route to ' + data.service
        );
        $('#socialStream').find('.elements').prepend(ele);
    });
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
                _self.redrawStars();
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
                _self.redrawStars();
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
    },

    /**
     * Redraws the route rating display
     *
     * @author Craig Knott
     */
    redrawStars: function () {
        $.ajax({
            url:     '/rating/getrouteaverage/',
            type:    'post',
            data:    {
                id: $('#routeId').val()
            },
            success: function (response) {
                var container = $('#ratingContainer');
                container.empty();

                var rating = parseFloat(JSON.parse(response));

                for (var i = 0; i < 5; i++) {
                    if ((i + 0.5) == rating) {
                        container.append($('<i>').addClass("starDisplay fa fa-star-half-o"));
                    } else if (i < rating) {
                        container.append($('<i>').addClass("starDisplay fa fa-star"));
                    } else if (i >= rating) {
                        container.append($('<i>').addClass("starDisplay fa fa-star-o"));
                    }
                }
            }
        });
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
        this.filterComments.click(function () {
            $('.elements').children().each(function () {
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
 * Class SavingManager
 *
 * Used to manage the bookmarking of routes and the UI for this
 *
 * @author Craig Knott
 */
var SavingManager = Class.extend({
    /**
     * Initialises this class and assigns member variables
     *
     * @author Craig Knott
     */
    init:            function () {
        this.button = $('.bmButton');
        this.buttonIcon = this.button.find('.fa');
        this.buttonText = this.button.find('.txt');
        this.state = this.buttonIcon.hasClass('fa-bookmark') ? 'save' : 'saved';
        this.setupListeners();
    },
    /**
     * Set up the listeners for the button
     *
     * @author Craig Knott
     */
    setupListeners:  function () {
        var _self = this;

        this.button.mouseenter(function () {
            if (_self.state === 'saved') {
                _self.buttonIcon.removeClass('fa-check').removeClass('fa-bookmark').addClass('fa-times');
                _self.buttonText.text('Remove');
            }
        });

        this.button.mouseleave(function () {
            if (_self.state === 'saved') {
                _self.buttonIcon.addClass('fa-check').removeClass('fa-times');
                _self.buttonText.text('Saved');
            } else if (_self.state === 'save') {
                _self.buttonIcon.addClass('fa-bookmark').removeClass('fa-times');
                _self.buttonText.text('Save');
            }
        });

        this.button.click(function () {
            if (_self.state === 'saved') {
                _self.removeFavourite();
                _self.state = 'save';

                _self.buttonIcon.addClass('fa-bookmark').removeClass('fa-times');
                _self.buttonText.text('Save');
            } else if (_self.state === 'save') {
                _self.state = 'saved';
                _self.addFavourite();

                _self.buttonIcon.addClass('fa-check').removeClass('fa-bookmark');
                _self.buttonText.text('Saved');
            }
        })
    },
    /**
     * Adds favourite to the database
     *
     * @author Craig Knott
     */
    addFavourite:    function () {
        $.ajax({
            type: 'POST',
            url:  '/route/addsaved',
            data: {
                rid: $('#routeId').val(),
                uid: $('#userId').val()
            }
        }).success(function (response) {
        });
    },
    /**
     * Removes a favourite from the database
     *
     * @author Craig Knott
     */
    removeFavourite: function () {
        $.ajax({
            type: 'POST',
            url:  '/route/deletesaved',
            data: {
                rid: $('#routeId').val(),
                uid: $('#userId').val()
            }
        }).success(function (response) {
        });
    }
});

/**
 * Class RecommendationManager
 *
 * Used to manage the recommending of similar routes
 *
 * @author Craig Knott
 */
var RecommendationManager = Class.extend({
    /**
     * Initialises this class and assigns member variables
     *
     * @author Craig Knott
     */
    init:            function () {
        this.button = $('#recommendSimilar');
        this.idSelected = undefined;
        this.setupListeners();
    },
    /**
     * Set up the listeners for the button
     *
     * @author Craig Knott
     */
    setupListeners:  function () {
        var _self = this;

        this.button.click(function () {
            $.ajax({
                type: 'POST',
                url:  '/route/recommendable',
                data: {
                    userId: $('#userId').val()
                }
            }).success(function (response) {
                $.confirm({
                    title:         'Recommend a Route',
                    icon:          'fa fa-link',
                    content:       _self.getPopupContent(JSON.parse(response)),
                    theme:         'black',
                    confirmButton: 'Confirm',
                    cancelButton:  'Cancel',
                    columnClass:   'col-xs-10 col-sm-10 col-md-10 col-lg-10',
                    onOpen:        function () {
                        // Clear current id
                        _self.idSelected = undefined;

                        // Add listeners to the route selectors
                        var rRoutes = $('.rRoute');
                        rRoutes.each(function () {
                            $(this).click(function () {
                                rRoutes.removeClass('selected');
                                $(this).addClass('selected');
                                _self.idSelected = $(this).attr('data-id');
                            })
                        });
                    },
                    confirm:       function () {
                        if (_self.idSelected !== undefined) {
                            // Add temp element to social stream
                            var tmp = $('<div>').addClass('streamElement recommend').append(
                                $('<i>').addClass("fa fa-link")
                            ).append(
                                $('<span>').addClass("bold").text(' ' + $('#username').val())
                            ).append(
                                $('<span>').text(' recommended a ')
                            ).append(
                                $('<a>').attr("href", "/route/detail/id/" + _self.idSelected).text('similar route')
                            );

                            $("#socialStream").find('.elements').prepend(tmp);

                            $.ajax({
                                type: 'POST',
                                url:  '/route/recommend',
                                data: {
                                    routeId: $('#routeId').val(),
                                    recomId: _self.idSelected
                                }
                            }).success(function (response) {
                            });
                        }
                    }
                });
            });
        });
    },
    /**
     * Get content for the popup
     *
     * @author Craig Knott
     *
     * @param data JSON data for recent/saved/owned routes
     */
    getPopupContent: function (data) {
        var text = 'Do you know a similar route you think other users would enjoy? Simply select below for your recently visited routes to suggest it!';

        var container = $('<div>').addClass('row');

        // Explanation of what this is
        var explanation = $('<div>').addClass('explanation').text(text);

        // Your recently visited routes
        var recentRoutes = $('<div>').addClass('recent row');
        recentRoutes.append($('<div>').addClass('header').text('Recently Visited Routes'));
        for (var i = 0; i < data.recent.length; i++) {
            recentRoutes.append($('<div>').addClass('rRoute col-md-3').text(data.recent[i].name).attr('data-id', data.recent[i].id))
        }

        // Your saved routes
        var savedRoutes = $('<div>').addClass('saved row');
        savedRoutes.append($('<div>').addClass('header').text('Your Saved Routes'));
        for (i = 0; i < data.saved.length; i++) {
            savedRoutes.append($('<div>').addClass('rRoute col-md-3').text(data.saved[i].name).attr('data-id', data.saved[i].id))
        }

        // Your own routes
        var myRoutes = $('<div>').addClass('own row');
        myRoutes.append($('<div>').addClass('header').text('Your Routes'));
        for (i = 0; i < data.own.length; i++) {
            myRoutes.append($('<div>').addClass('rRoute col-md-3').text(data.own[i].name).attr('data-id', data.own[i].id))
        }

        container.append(explanation, recentRoutes, savedRoutes, myRoutes);
        return container.prop('outerHTML');
    }
});

/**
 * Class ConfirmationManager
 *
 * Used to manage reporting and deleting of routes
 *
 * @author Craig Knott
 */
var ConfirmationManager = Class.extend({
    init:           function () {
        this.reportRoute = $('#reportRoute');
        this.deleteRoute = $('#deleteRoute');

        this.setupListeners();
    },
    setupListeners: function () {
        this.reportRoute.click(function () {
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

        this.deleteRoute.click(function () {
            $.confirm({
                title:           'Delete comment?',
                icon:            'fa fa-warning',
                content:         'Are you sure you wish to delete this route? This action is irreversible.',
                theme:           'black',
                confirmButton:   'Delete',
                keyboardEnabled: true,
                confirm:         function () {
                    $.ajax({
                        type: 'POST',
                        url:  '/route/delete',
                        data: {
                            id: $('#routeId').val()
                        }
                    }).success(function () {
                        window.location = "/user/details/";
                    });
                }
            });
        });
    }
});

