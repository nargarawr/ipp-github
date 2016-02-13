var maps = [];

/**
 * Document ready function. Gets the geocodes the submitted data and submits the form
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    var currentPage = $('#page_num').val();

    // Pagination buttons
    $('.prevPage').click(function (e) {
        e.preventDefault();
        if (currentPage != 0) {
            submitPaginiationForm('prev');
        }
    });

    $('.nextPage').click(function (e) {
        e.preventDefault();
        if (parseInt($('#page_num').val()) + 1 != $('.iPage').length) {
            submitPaginiationForm('next');
        }
    });

    $('.iPage').click(function (e) {
        e.preventDefault();
        var goTo = parseInt($(this).find('a').text()) - 1;
        submitPaginiationForm(goTo);
    });

    $('#submit_addresses').click(function (e) {
        submitSearchForm();
    });

    var routes = $('.route');
    routes.each(function () {
        var shrt = $(this).find('.shortDesc');
        var long = $(this).find('.fullDesc');
        var readMore = $(this).find('.readMore');
        var readLess = $(this).find('.readLess');

        readMore.click(function () {
            long.removeClass('hidden');
            shrt.addClass('hidden')
        });

        readLess.click(function () {
            shrt.removeClass('hidden');
            long.addClass('hidden')
        });
    });

    $('[data-toggle="tooltip"]').tooltip();

    var ratingManager = new RatingManager();
    for (var i = 0; i < routes.length; i++) {
        drawMap('map_' + i);
    }
});

/**
 * Process the entered user search terms, then submit the search form
 *
 * @author Craig Knott
 */
function submitPaginiationForm(type) {
    // Geocode the start and end points, so we can process them as lat and long values
    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: $("#start_address").val(),
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        if (response.status != "OK") {
            $("#formError").val(1);
        } else {
            var geocodedStart = response.results[0].geometry.location;
            $("#start_lat").val(geocodedStart.lat);
            $("#start_lng").val(geocodedStart.lng);
        }

        $.ajax({
            type: 'GET',
            url:  "https://maps.googleapis.com/maps/api/geocode/json",
            data: {
                address: $("#end_address").val(),
                key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
            }
        }).success(function (response2) {
            var end = $("#end_address");

            // If user entered something for the end point, but it doesn't exist, throw an error
            if (response2.status != "OK" && end.val() != "") {
                $("#formError").val(2);
            } else {
                if (end.val() != "") {
                    var geocodedEnd = response2.results[0].geometry.location;

                    $("#end_lat").val(geocodedEnd.lat);
                    $("#end_lng").val(geocodedEnd.lng);
                }
            }

            // Change pagination stuff here
            var pn = $('#page_num');
            var cur = pn.val();

            if (type == 'next') {
                pn.val(++cur);
            } else if (type == 'prev') {
                pn.val(--cur);
            } else if (jQuery.isNumeric(type)) {
                pn.val(type);
            }

            $("#pageForm").submit();
        });

    });
}

/**
 * Process the entered user search terms, then submit the search form
 *
 * @author Craig Knott
 */
function submitSearchForm() {
    // Geocode the start and end points, so we can process them as lat and long values
    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: $("#start_address_1").val(),
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        if (response.status != "OK") {
            $("#formError_1").val(1);
        } else {
            var geocodedStart = response.results[0].geometry.location;
            $("#start_lat_1").val(geocodedStart.lat);
            $("#start_lng_1").val(geocodedStart.lng);
        }

        $.ajax({
            type: 'GET',
            url:  "https://maps.googleapis.com/maps/api/geocode/json",
            data: {
                address: $("#end_address_1").val(),
                key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
            }
        }).success(function (response2) {
            var end = $("#end_address_1");

            // If user entered something for the end point, but it doesn't exist, throw an error
            if (response2.status != "OK" && end.val() != "") {
                $("#formError_1").val(2);
            } else {
                if (end.val() != "") {
                    var geocodedEnd = response2.results[0].geometry.location;

                    $("#end_lat_1").val(geocodedEnd.lat);
                    $("#end_lng_1").val(geocodedEnd.lng);
                }
            }

            $("#searchForm").submit();
        });

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
        this.stars = $('.clickableRating').find('.starDisplay');
        this.starStates = [];
        this.minStarsField = $('#min_stars');
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
            var index = $(this).attr('data-index');
            _self.selectStar($(this), index);
            _self.minStarsField.val(parseInt(index) + 1);

            for (var i = 0; i < _self.stars.length; i++) {
                if (i <= index) {
                    _self.selectStar($(_self.stars[i]), i);
                } else {
                    _self.deselectStar($(_self.stars[i]), i);
                }
            }
        });

        this.clearBtn.click(function () {
            _self.minStarsField.val(0);
            for (var i = 0; i < _self.stars.length; i++) {
                _self.deselectStar($(_self.stars[i]), i);
            }
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
    }
});


/**
 * Draws the map and adds the map points to it
 *
 * @author Craig Knott
 */
function drawMap(id) {
    $('#' + id).height('200px');

    map = L.map(id, {zoomControl: false}).setView([52, -1.1], 13);
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
            opacity:   0,
            clickable: false
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
        position:         'topleft'
        , routing:        {
            router: router
        }
        , snapping:       {
            layers: []
        }
        , snapping:       {
            layers:        [snapping]
            , sensitivity: 15
            , vertexonly:  false
        }
        , routeToDraw:    $('#' + id + '_routeId').val()
        , multiMapCentre: true
        , mapId:          id.split("map_")[1]
    });
    map.addControl(routing);
    routing.draw();

    maps.push(map);
}