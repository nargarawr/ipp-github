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
        this.readOnly = $('#mapReadOnly').val();

        var mapDataCopy = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
        var creativeCommons = '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
        var mapBoxCopy = 'Imagery &copy; <a href="http://mapbox.com">Mapbox</a>';
        var mapId = 'nargarawr.cig6xoyv103gnvbkvyv7s6a0k';
        var token = 'pk.eyJ1IjoibmFyZ2FyYXdyIiwiYSI6ImNpZzZ4b3l6MzAzZzF2cWt2djg4d3llZDMifQ.k5f5mW8zW3VBH40GUYS-8A';

        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: mapDataCopy + ', ' + creativeCommons + ', ' + mapBoxCopy,
            maxZoom:     18,
            minZoom:     7,
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
                    } else {
                        console.log('Could not load snapping data');
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
            var route = 'http://www.yournavigation.org/api/1.0/gosmore.php&format=geojson&v=foot&fast=1&layer=mapnik';
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
                var url = (routeId == "") ? '/route/new' : '/route/update';
                $.ajax({
                    type: 'POST',
                    url:  url,
                    data: {
                        name:        $('#routeName').val(),
                        description: $('#routeDesc').val(),
                        privacy:     $('#routePrivacy').val(),
                        points:      points,
                        routeId:     routeId
                    }
                }).error(function () {
                    window.location.href = '/user/routes';
                }).success(function (response) {
                    window.location.href = '/route/create/id/' + response;
                });
            }
        });
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
            point.description = pointPopup.find('textarea').val();
            point.lat = pointPopup.find('.latHidden').text();
            point.lng = pointPopup.find('.lngHidden').text();
            points.push(point);
        }

        return points;
    }
});


/**
 * Class PointListManager
 *
 * Manages all custom extensions to the L.Router.
 *
 * @author Craig Knott
 */
var PointsListManager = Class.extend({
    /**
     * Initialises the class, setting the values of private variables and setting up the sortable fucntion
     *
     * @author Craig Knott
     *
     * @param router The L.Routing Object
     */
    init:        function (router) {
        var _self = this;

        this.router = router;
        this.popupOpen = false;
        this.container = $('#left-hand-display');
        this.pointsList = this.container.find('.pointsList');
        this.noPointsYet = this.container.find('.noPointsYet');
        this.pointsYet = this.container.find('.pointsYet');
        this.numPoints = 1;
        this.mapManager = mm;

        $('.pointsList').sortable({
            handle: ".left-side",
            update: function () {
                _self.mapManager.drawRoute();
            }
        });

    },
    /**
     * Adds a point to the left hand display
     *
     * @author Craig Knott
     *
     * @param marker The marker object (as defined in the Leaflet API)
     * @param e An object containing the lat and long of the point (as defined in the Leaflet API)
     */
    addPoint:    function (marker, e) {
        if (this.pointsList.children().length == 0) {
            this.noPointsYet.addClass('hidden');
            this.pointsYet.removeClass('hidden');
        }

        this.numPoints++;

        var _self = this;

        var left = $('<div>').addClass('left-side');
        left.append($('<i>').addClass('fa fa-arrows'));

        var mid = $('<div>').addClass('middle-side');
        mid.append($('<div>').addClass('title').text(
            $(marker._popup._content).find('.point_title').val()
        ));
        mid.append($('<div>').addClass('coords').text(
            e.lat.toString().slice(0, 7) + ", " + e.lng.toString().slice(0, 7)
        ));

        var right = $('<div>').addClass('right-side');
        var editButton = $('<button>').addClass('marker-edit-button btn btn-primary')
            .html("<i class='fa fa-pencil'></i>");
        editButton.click(function () {
            marker.openPopup();
        });

        var deleteButton = $('<button>').addClass('marker-delete-button-lhd btn btn-danger')
            .html("<i class='fa fa-trash'></i>");
        deleteButton.click(function () {
            $.confirm({
                title:           'Delete point?',
                icon:            'fa fa-warning',
                content:         'Are you sure you wish to delete this point? This action is irreversible.',
                theme:           'black',
                confirmButton:   'Delete',
                keyboardEnabled: true,
                confirm:         function () {
                    _self.removePoint(marker._leaflet_id);
                    _self.router.removeWaypoint(marker, function () {
                    });
                }
            });
        });
        right.append(editButton, deleteButton);

        var pointContainer = $('<div>').addClass('point').attr('data-point-id', marker._leaflet_id);
        pointContainer.append(left, mid, right);
        this.pointsList.append(pointContainer);
    },
    /**
     * Updates the name of a point on the left hand display
     *
     * @author Craig Knott
     *
     * @param markerId The id of this marker
     * @param newName The new name of this marker
     */

    updatePoint: function (markerId, newName) {
        var obj = this.findPointById(markerId);
        obj.find('.title').text(newName);
    },

    /**
     * Removes a point from the left hand display
     *
     * @author Craig Knott
     *
     * @param markerId The id of the marker to remove
     */

    removePoint:       function (markerId) {
        var obj = this.findPointById(markerId);
        if (obj != null) {
            obj.remove();
        }

        if (this.pointsList.children().length == 0) {
            this.noPointsYet.removeClass('hidden');
            this.pointsYet.addClass('hidden');
        }
    },
    /**
     * Generate the HTML for a given popup
     *
     * @author Craig Knott
     *
     * @param e An object containing the lat and long of the point (as defined in the Leaflet API)
     * @param data An object containing data about this point (name, desc)
     *
     * @returns {*} An HTML string for this popup
     */
    getPopupHTML:      function (e, data) {
        var container = $('<div>').addClass('pointContainer');
        container.append($('<div>').addClass('coords right')
            .text(e.lat.toString().slice(0, 7) + ", " + e.lng.toString().slice(0, 7)));
        container.append(
            $('<input>').addClass('form-control point_title')
                .attr('value', (data === undefined) ? ('Point ' + this.numPoints) : data.name)
        );
        container.append(
            $('<textarea>').addClass('form-control')
                .attr('placeholder', 'Enter a description')
                .text((data === undefined) ? ('') : data.description)
        );
        container.append(
            $('<div>').addClass('hidden latHidden').text(e.lat.toString())
        );
        container.append(
            $('<div>').addClass('hidden lngHidden').text(e.lng.toString())
        );

        var buttons = $('<div>').addClass('buttons');
        buttons.append($('<button>').addClass('marker-delete-button btn btn-danger').html("<i class='fa fa-trash'></i>"));
        buttons.append($('<button>').addClass('marker-update-button btn btn-success').html("<i class='fa fa-check'></i>"));

        container.append(buttons);
        return container[0];
    },
    /**
     * Finds and returns a specific marker from the list, by it's Id
     *
     * @param markerId The id of the market
     *
     * @returns {*} The marker object
     */
    findPointById:     function (markerId) {
        var objToReturn = null;
        this.pointsList.children().each(function (i, obj) {
            if ($(obj).attr('data-point-id') == markerId) {
                objToReturn = $(obj);
            }
        });

        return objToReturn;
    },
    addExistingPoints: function (routeId) {
        var _self = this;
        $.ajax({
            type: 'GET',
            url:  '/route/getpoints',
            data: {
                id: routeId
            }
        }).success(function (response) {
            var data = JSON.parse(response);
            var middlePoint;

            for (var i = 0; i < data.length; i++) {
                if (i == Math.floor(data.length / 2)) {
                    middlePoint = data[i];
                }

                var d = {
                    _feature: null,
                    lat:      data[i].latitude,
                    lng:      data[i].longitude
                };

            }

            _self.centreMap(middlePoint.latitude, middlePoint.longitude)
        });
    },
    /**
     * Centres the map at a certain point
     *
     * @author Craig Knott
     *
     * @param lat Latitude to centre at
     * @param lng Longitude to centre at
     */
    centreMap:         function (lat, lng) {
        map.setView({
            lat: lat,
            lng: lng
        });
    },
    /**
     * TODO
     */
    onWayPointDragEnd: function (marker) {
        var markerId = marker.marker._leaflet_id;
        var newLat = marker.marker._latlng.lat;
        var newLng = marker.marker._latlng.lng;

        var lhd = $('.point[data-point-id=' + markerId + ']').find('.coords');
        lhd.text(
            newLat.toString().slice(0, 7) + ", " + newLng.toString().slice(0, 7)
        );

        var popup = map._layers[markerId]._popup;
        console.log(popup._content.innerHTML);
        popup._content.innerHTML = popup._content.innerHTML.replace(
            /<div class=\"coords right\">-?\d*\.\d*, -?\d*\.\d*<\/div>/,
            '<div class="coords right">' + newLat.toString().slice(0, 7) + ', ' + newLng.toString().slice(0, 7) + '</div>'
        );

        popup._content.innerHTML = popup._content.innerHTML.replace(
            /<div class=\"hidden latHidden\">-?\d*\.\d*<\/div><div class=\"hidden lngHidden\">-?\d*\.\d*<\/div>/,
            '<div class="hidden latHidden">' + newLat + '</div><div class="hidden lngHidden">' + newLng + '</div>'
        );
    }
});