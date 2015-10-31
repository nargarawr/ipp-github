var map;

$(document).ready(function () {
    var mm;

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

        var uploadManager = new UploadManager(mm);
    });

    $('.pointsList').css('max-height', (innerHeight - 165) * 0.9);

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

    $('#submitRoute').click(function () {
        submitRoute(mm);
    });
});

var UploadManager = Class.extend({
    init:                function (mm) {
        this.uploadForm = $("#uploadForm");
        this.fileUploadInput = $("#fileUploader");
        this.mapManager = mm;
        this.setupListeners();
    },
    setupListeners:      function () {
        var _self = this;
        this.fileUploadInput.on('change', function () {
            console.log('submit')
            _self.uploadForm.submit();
        });

        this.uploadForm.ajaxForm({
            success: function(data){
                console.log('upload success')
                _self.formUploadSuccesful(data);
            }
        });
    },
    formUploadSuccesful: function (data) {
        var route = JSON.parse(data);
        $('#routeName').val(route.name);
        $('#routeDesc').val(route.description);
        $('#routePrivacy').val(route.is_private);

        var points = route.points;
        for (var i = 0; i < points.length && i < 12; i++) {
            var p = points[i];
            // Construct fake 'e' object with latlng information
            var e = {
                latlng: {
                    lat: p.lat,
                    lng: p.lng
                }
            };

            // Construct object with popup data
            var popupData = {
                name:        p.name,
                description: p.description
            };

            this.mapManager.addPointToMap(e, popupData);
        }
    }
});

var MapManager = Class.extend({
    init:               function (lat, long, zoom, routeId) {
        $('#map').css('height', window.innerHeight - 62);

        map = L.map('map', {zoomControl: false}).setView([lat, long], zoom);
        new L.Control.Zoom({position: 'topright'}).addTo(map);
        this.pointListManager = new PointListManager();

        var mapDataCopy = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
        var creativeCommons = '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
        var mapBoxCopy = 'Imagery &copy; <a href="http://mapbox.com">Mapbox</a>';
        var mapId = 'nargarawr.cig6xoyv103gnvbkvyv7s6a0k';
        var token = 'pk.eyJ1IjoibmFyZ2FyYXdyIiwiYSI6ImNpZzZ4b3l6MzAzZzF2cWt2djg4d3llZDMifQ.k5f5mW8zW3VBH40GUYS-8A';

        L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
            attribution: mapDataCopy + ', ' + creativeCommons + ', ' + mapBoxCopy,
            maxZoom:     18,
            minZoom:     8,
            id:          mapId,
            accessToken: token
        }).addTo(map);

        this.isPopupOpen = false;
        this.numPoints = 0;

        // If we are editing a route, we need to get all of that routes information
        if (routeId != '') {
            this.loadExistingPoints(routeId);
        }

        this.setupListeners();
    },
    setupListeners:     function () {
        var _self = this;
        map.on('click', function (e) {
            // We can only use 12 points on the mapbox free API
            if (_self.pointListManager.pointsList.children().length < 12) {
                // Only add a new point if popup is NOT showing
                if (_self.isPopupOpen) {
                    _self.isPopupOpen = false;
                } else {
                    _self.addPointToMap(e);
                }
            } else {
                $.alert({
                    title:   'Point Limit Reached',
                    icon:    'fa fa-warning',
                    content: 'Unfortunately, Niceway.to currently only supports a maximum of 12 points per route',
                    theme:   'black'
                });
            }
        });
    },
    loadExistingPoints: function (routeId) {
        var _self = this;
        $.ajax({
            type: 'GET',
            url:  '/route/getpoints',
            data: {
                id: routeId
            }
        }).success(function (response) {
            var data = JSON.parse(response);
            for (var i = 0; i < data.length; i++) {
                var p = data[i];
                // Construct fake 'e' object with latlng information
                var e = {
                    latlng: {
                        lat: p.latitude,
                        lng: p.longitude
                    }
                };

                // Construct object with popup data
                var popupData = {
                    name:        p.name,
                    description: p.description
                };

                _self.addPointToMap(e, popupData);
            }
        });
    },
    addPointToMap:      function (e, popupData) {
        var _self = this;

        _self.numPoints++;
        var marker = L.marker([e.latlng.lat, e.latlng.lng])
            .bindPopup(this.getPopupHTML(e, popupData))
            .on('popupopen', function () {
                _self.isPopupOpen = true;
                var tempMarker = this;

                $(".marker-delete-button").click(function () {
                    $.confirm({
                        title:           'Delete point?',
                        icon:            'fa fa-warning',
                        content:         'Are you sure you wish to delete this point? This action is irreversible.',
                        theme:           'black',
                        confirmButton:   'Delete',
                        keyboardEnabled: true,
                        confirm:         function () {
                            _self.isPopupOpen = false;
                            map.removeLayer(tempMarker);
                            _self.pointListManager.removePoint(tempMarker._leaflet_id);
                        }
                    });
                });

                $(".marker-update-button").click(function () {
                    var newName = $(marker._popup._content).find('.point_title').val();
                    _self.pointListManager.updatePoint(tempMarker._leaflet_id, newName);
                    marker.closePopup();
                });
            })
            .addTo(map);

        this.pointListManager.addPoint(marker, e);
    },
