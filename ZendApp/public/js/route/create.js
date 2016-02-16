var map;
var mm;

/**
 * Document ready function. Loads the route if a route id if given, and constructs the map manager, upload manager
 * and popup manager objects. Also sets the left hand display to hidden if on a small screen
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    if (window.innerWidth < 768) {
        $('#left-hand-display').addClass('hidden');
        $('#left-hand-display-mini').removeClass('hidden');
    }

    // If user has location set, centre the map there
    var location = $('#userLocation').val();
    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: location,
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        var routeId = $('#routeId').val();

        if (routeId != "") {
            // Center on route if we're editing a route
            var lat = $('#center_map_lat').val();
            var lng = $('#center_map_lng').val();
            mm = new MapManager(lat, lng, 13, routeId);
        } else if (response.status == "ZERO_RESULTS") {
            // Center on Nottingham if user location not set
            mm = new MapManager(52.95338, -1.18689, 13, routeId);
        } else {
            // Center on user location if set
            var latlng = response.results[0].geometry.location;
            mm = new MapManager(latlng.lat, latlng.lng, 13, routeId);
        }

        var uploadManager = new UploadManager();
        var popupManager = new PopupManager();
    });

    // 90% of the height of the screen (minus the header and top of the social stream)
    $('.pointsList').css('max-height', (innerHeight - 165) * 0.9);
});

/**
 * Class MapManager
 *
 * Manages the Leaflet/Mapbox map present on the pass
 *
 * @author Craig Knott
 */
var MapManager = Class.extend({
    /**
     * Initialises the map manager class, and draws the map
     *
     * @author Craig Knott
     *
     * @param lat      The default latitude of the map
     * @param long     The default longitude of the map
     * @param zoom     The default zoom level of the map
     * @param routeId  The Id of this route, if any
     */
    init: function (lat, long, zoom, routeId) {
        $('#map').css('height', window.innerHeight - 62);

        map = L.map('map', {
            zoomControl: false
        }).setView([lat, long], zoom);
        new L.Control.Zoom({position: 'topright'}).addTo(map);

        var mapDataCopy = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
        var creativeCommons = '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
        var mapBoxCopy = 'Imagery &copy; <a href="http://mapbox.com">Mapbox</a>';
        var token = 'pk.eyJ1IjoibmFyZ2FyYXdyIiwiYSI6ImNpZzZ4b3l6MzAzZzF2cWt2djg4d3llZDMifQ.k5f5mW8zW3VBH40GUYS-8A';

        var sat = L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: mapDataCopy + ', ' + creativeCommons + ', ' + mapBoxCopy,
            maxZoom:     18,
            minZoom:     7,
            id:          'nargarawr.oa864eol',
            accessToken: token
        }).addTo(map);

        var street = L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: mapDataCopy + ', ' + creativeCommons + ', ' + mapBoxCopy,
            maxZoom:     18,
            minZoom:     7,
            id:          'nargarawr.cig6xoyv103gnvbkvyv7s6a0k',
            accessToken: token
        }).addTo(map);

        var baseMaps = {
            "Satellite": sat,
            "Streets":   street

        };

        L.control.layers(baseMaps).addTo(map);

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
            }).success(function () {
                hideMapLoadingIcon();
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
});

/**
 * Class PopupManager
 *
 * Deals with the "save route" popup
 *
 * @author Craig Knott
 */
var PopupManager = Class.extend({
    /**
     * Initialises this class, as well as setting up the popup
     *
     * @author Craig Knott
     *
     */

    init: function () {
        this.mm = mm;

        $('.popup-trigger').magnificPopup({
            type:            'inline',
            fixedContentPos: false,
            fixedBgPos:      true,
            overflowY:       'auto',
            closeBtnInside:  true,
            preloader:       false,
            midClick:        true,
            removalDelay:    300,
            mainClass:       'my-mfp-zoom-in'
        });

        this.trigger = $('.popup-trigger.submit.pointsYet');
        this.submitButton = $('#submitRoute');
        this.cancelSubmit = $('#cancelSubmit');
        this.setupListeners();
    },

    /**
     * Assigns listeners to each of the interactive elements of the row
     *
     * @author Craig Knott
     */

    setupListeners: function () {
        var _self = this;

        this.cancelSubmit.click(function () {
            $.magnificPopup.close()
        });

        this.submitButton.click(function () {
            var valid = _self.checkValidInput();
            if (valid) {
                $('#submitRoute').html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                points = _self.getAllPoints();
                routeId = $('#routeId').val();

                // Get the start and end address for the points
                var startPoint = points[0];
                var endPoint = points[points.length - 1];

                var location = $('#userLocation').val();
                $.ajax({
                    type: 'GET',
                    url:  "https://maps.googleapis.com/maps/api/geocode/json",
                    data: {
                        latlng: startPoint.lat + "," + startPoint.lng,
                        key:    "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
                    }
                }).success(function (response) {
                    var start_address = response.results[0].address_components;
                    var start_address_name = (start_address[start_address.length - 1]).long_name;

                    $.ajax({
                        type: 'GET',
                        url:  "https://maps.googleapis.com/maps/api/geocode/json",
                        data: {
                            latlng: endPoint.lat + "," + endPoint.lng,
                            key:    "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
                        }
                    }).success(function (response) {
                        var end_address = response.results[0].address_components;
                        var end_address_name = (end_address[end_address.length - 1]).long_name;

                        var url = (routeId == "") ? '/route/new' : '/route/update';
                        $.ajax({
                            type: 'POST',
                            url:  url,
                            data: {
                                name:        $('#routeName').val(),
                                description: $('#routeDesc').val(),
                                privacy:     $('#routePrivacy').val(),
                                points:      points,
                                routeId:     routeId,
                                start_add:   start_address_name,
                                end_add:     end_address_name
                            }
                        }).error(function () {
                            window.location.href = '/user/routes';
                        }).success(function (response) {
                            window.location.href = '/route/create/id/' + response;
                        });
                    });
                });
            }
        });

        this.trigger.click(function(){
            $('#routeName').val($('#routeName_2').find('input').val());
        })
    },

    /**
     * Checks whether the user has entered a name for the route they are attempting to save
     *
     * @author Craig Knott
     *
     * @returns {boolean} Whether the user has entered a name for their route
     */

    checkValidInput: function () {
        var routeName = $('#routeName');
        if (routeName.val() == "") {
            $('#noNameError').removeClass('hidden');
            routeName.addClass('error');
            return false;
        } else {
            $('#noNameError').addClass('hidden');
            routeName.removeClass('error');
        }

        return true;
    },

    /**
     * Gets all points from the map in an array
     *
     * @author Craig Knott
     *
     * @returns {Array} Of all points on the map
     */

    getAllPoints: function () {
        // Get all points
        var pointsList = plm.pointsList.children();
        var points = [];
        for (var i = 0; i < pointsList.length; i++) {
            var pointId = $(pointsList[i]).attr('data-point-id');
            var pointPopup = $(map._layers[pointId]._popup._content);

            var point = {};
            point.name = pointPopup.find('.point_title').val();
            point.description = pointPopup.find('.in_desc').val();
            point.lat = pointPopup.find('.latHidden').text();
            point.lng = pointPopup.find('.lngHidden').text();
            point.media = pointPopup.find('.in_media').val();
            points.push(point);
        }

        return points;
    }
});


/**
 * Class UploadManager
 *
 * Class in charge of uploading a file, and importing the contents of the file
 *
 * @author Craig Knott
 */
var UploadManager = Class.extend({
    /**
     * Initialises the class, assigns a value to the private variables and calls the function to set up listeners
     */
    init:                function () {
        this.uploadForm = $("#uploadForm");
        this.fileUploadInput = $("#fileUploader");
        this.mapManager = mm;
        this.setupListeners();
    },
    /**
     * Assigns listeners to each of the interactive elements of the row
     *
     * @author Craig Knott
     */
    setupListeners:      function () {
        var _self = this;
        this.fileUploadInput.on('change', function () {
            _self.uploadForm.submit();
        });

        this.uploadForm.ajaxForm({
            success: function (data) {
                _self.formUploadSuccesful(data);
            }
        });
    },
    /**
     * Callback function called when the upload of a file is succesful. Reads the contents of the file and shows
     * them on the page
     *
     * @author Craig Knott
     *
     * @param data
     */
    formUploadSuccesful: function (data) {
        var route;

        // Try to parse the uploaded file as JSON. If we can't throw an error
        // TIL Javascript has try/catch
        try {
            route = JSON.parse(data);
        } catch (e) {
            $.alert({
                title:           'Invalid file',
                icon:            'fa fa-warning',
                content:         'The file you uploaded was not a valid Niceway.to route file',
                theme:           'black',
                keyboardEnabled: true
            });
            return;
        }

        $('#routeName').val(route.name);
        $('#routeDesc').val(route.description);
        $('#routePrivacy').val(route.is_private);

        var points = route.points;
        var highestLat = points[0].lat;
        var highestLon = points[0].lng;
        var lowestLat = points[0].lat;
        var lowestLon = points[0].lng;
        for (var i = 0; i < points.length; i++) {
            if (points[i].latitude > highestLat) {
                highestLat = points[i].latitude;
            }
            if (points[i].longitude > highestLon) {
                highestLon = points[i].longitude;
            }
            if (points[i].latitude < lowestLat) {
                lowestLat = points[i].latitude;
            }
            if (points[i].longitude < lowestLon) {
                lowestLon = points[i].longitude;
            }

            var latlng = {
                _feature: null,
                lat:      points[i].lat,
                lng:      points[i].lng
            };

            var popupData = {
                name:        points[i].name,
                description: points[i].description,
                media:       points[i].media
            };

            plm.router.addWaypoint(
                new L.Marker(latlng, {title: 'test'}),
                plm.router.getLast(),
                null,
                function (e, f) {
                },
                popupData
            );
        }
        plm.centreMap(
            highestLat,
            highestLon,
            lowestLat,
            lowestLon
        );
    }
});

/**
 * Used to show the "Loading Map" message/icon
 */
function showMapLoadingIcon() {
    $("#loading").removeClass("hidden");
}

/**
 * Used to hide the "Loading Map" message/icon
 */
function hideMapLoadingIcon() {
    $("#loading").addClass("hidden");
}