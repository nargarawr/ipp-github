var map;
var mm;

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
            _self.uploadForm.submit();
        });

        this.uploadForm.ajaxForm({
            success: function (data) {
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
        for (var i = 0; i < points.length; i++) {
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
        this.pointListManager = new PointListManager(this);
        this.routingControl;

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
            // Only add a new point if popup is NOT showing
            if (_self.isPopupOpen) {
                _self.isPopupOpen = false;
            } else {
                _self.addPointToMap(e);
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
                            _self.drawRoute();
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
        this.drawRoute();
    },
    drawRoute:          function () {
        var points = this.pointListManager.pointsList.children();
        if (points.length < 2) {
            return;
        }

        if (this.routingControl !== undefined) {
            this.routingControl.removeFrom(map);
        }

        var arrayOfPoints = [];
        for (var i = 0; i < points.length; i++) {
            var marker = map._layers[$(points[i]).attr('data-point-id')]
            arrayOfPoints.push(L.latLng(marker._latlng.lat, marker._latlng.lng))
        }

        this.routingControl = L.Routing.control({
            waypoints: arrayOfPoints
        }).addTo(map);

        $('.leaflet-routing-container').addClass('hidden');
        $('.leaflet-marker-icon').removeClass('leaflet-marker-draggable');
    },
    getPopupHTML:       function (e, data) {
        var container = $('<div>').addClass('pointContainer');
        container.append($('<div>').addClass('coords right')
            .text(e.latlng.lat.toString().slice(0, 7) + ", " + e.latlng.lng.toString().slice(0, 7)));
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
            $('<div>').addClass('hidden latHidden').text(e.latlng.lat.toString())
        );
        container.append(
            $('<div>').addClass('hidden lngHidden').text(e.latlng.lng.toString())
        );

        var buttons = $('<div>').addClass('buttons');
        buttons.append($('<button>').addClass('marker-delete-button btn btn-danger').html("<i class='fa fa-trash'></i>"));
        buttons.append($('<button>').addClass('marker-update-button btn btn-success').html("<i class='fa fa-check'></i>"));

        container.append(buttons);
        return container[0];
    }
});

var test = {};

var PointListManager = Class.extend({
    init:           function (mapManager) {
        var _self = this;

        this.container = $('#left-hand-display');
        this.pointsList = this.container.find('.pointsList');
        this.noPointsYet = this.container.find('.noPointsYet');
        this.pointsYet = this.container.find('.pointsYet');
        this.mapManager = mapManager;

        $('.pointsList').sortable({
            handle: ".left-side",
            update: function() {
                _self.mapManager.drawRoute();
            }
        });

        this.setupListeners();
    },
    setupListeners: function () {
        $('#hide_lhd').click(function () {
            $('#left-hand-display').addClass('hidden');
            $('#left-hand-display-mini').removeClass('hidden');
        });

        $('#show_lhd').click(function () {
            $('#left-hand-display').removeClass('hidden');
            $('#left-hand-display-mini').addClass('hidden');
        });
    },
    addPoint:       function (marker, e) {
        if (this.pointsList.children().length == 0) {
            this.noPointsYet.addClass('hidden');
            this.pointsYet.removeClass('hidden');
        }

        var _self = this;

        var left = $('<div>').addClass('left-side');
        left.append($('<i>').addClass('fa fa-arrows'));

        var mid = $('<div>').addClass('middle-side');
        mid.append($('<div>').addClass('title').text(
            $(marker._popup._content).find('.point_title').val()
        ));
        mid.append($('<div>').addClass('coords').text(
            e.latlng.lat.toString().slice(0, 7) + ", " + e.latlng.lng.toString().slice(0, 7)
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
                    map.removeLayer(marker);
                    _self.removePoint(marker._leaflet_id);
                    _self.mapManager.drawRoute();
                }
            });
        });
        right.append(editButton, deleteButton);

        var pointContainer = $('<div>').addClass('point').attr('data-point-id', marker._leaflet_id);
        pointContainer.append(left, mid, right);
        this.pointsList.append(pointContainer);


    },
    updatePoint:    function (markerId, newName) {
        var obj = this.findPointById(markerId);
        obj.find('.title').text(newName);
    },
    removePoint:    function (markerId) {
        var obj = this.findPointById(markerId);
        if (obj != null) {
            obj.remove();
        }

        if (this.pointsList.children().length == 0) {
            this.noPointsYet.removeClass('hidden');
            this.pointsYet.addClass('hidden');
        }
    },
    findPointById:  function (markerId) {
        var objToReturn = null;
        this.pointsList.children().each(function (i, obj) {
            if ($(obj).attr('data-point-id') == markerId) {
                objToReturn = $(obj);
            }
        });

        return objToReturn;
    }
});

function submitRoute(mm) {
    var routeName = $('#routeName').val();
    if (routeName == "") {
        $('#noNameError').removeClass('hidden');
        $('#routeName').addClass('error');
        return;
    } else {
        $('#noNameError').addClass('hidden');
        $('#routeName').removeClass('error');
    }

    $('#submitRoute').html('<i class="fa fa-spinner fa-spin"></i> Saving...');

    // Get all points
    var pointsList = mm.pointListManager.pointsList.children();
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

    var url = (routeName == "") ? '/route/new' : '/route/update';
    $.ajax({
        type: 'POST',
        url:  url,
        data: {
            name:        routeName,
            description: $('#routeDesc').val(),
            privacy:     $('#routePrivacy').val(),
            points:      points,
            routeId:     $('#routeId').val()
        }
    }).error(function () {
        window.location.href = '/user/routes';
    }).success(function (response) {
        window.location.href = '/route/create/id/' + response;
    });
}
