var map;

$(document).ready(function () {
    mm = new MapManager(52.95338, -1.18689, 13);

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
        if ($('#routeName').val() == "") {
            $('#noNameError').removeClass('hidden');
            $('#routeName').addClass('error');
            return;
        } else {
            $('#noNameError').addClass('hidden');
            $('#routeName').removeClass('error');
        }

        $('#submitRoute').html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            type: 'POST',
            url:  '/route/save',
            data: {
                name: $('#routeName').val(),
                description: $('#routeDesc').val(),
                privacy: $('#routePrivacy').val()
            }
        }).success(function (response) {
            window.location.href = '/route/create/id/' + response;
        });
    });
});

var MapManager = Class.extend({
    init:           function (lat, long, zoom) {
        $('#map').css('height', window.innerHeight - 62);

        map = L.map('map').setView([lat, long], zoom);
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

        this.setupListeners();
    },
    setupListeners: function () {
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
    addPointToMap:  function (e) {
        var _self = this;

        _self.numPoints++;
        var marker = L.marker([e.latlng.lat, e.latlng.lng])
            .bindPopup(this.getPopupHTML(e))
            .on('popupopen', function () {
                _self.isPopupOpen = true;
                var tempMarker = this;

                $(".marker-delete-button").click(function () {
                    _self.isPopupOpen = false;
                    map.removeLayer(tempMarker);
                    _self.pointListManager.removePoint(tempMarker._leaflet_id);
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
    getPopupHTML:   function (e) {
        var container = $('<div>').addClass('pointContainer');
        container.append($('<div>').addClass('coords right')
            .text(e.latlng.lat.toString().slice(0, 7) + ", " + e.latlng.lng.toString().slice(0, 7)));
        container.append($('<input>').addClass('form-control point_title').attr('value', 'Point ' + this.numPoints));
        container.append($('<textarea>').addClass('form-control').attr('placeholder', 'Enter a description'));

        var buttons = $('<div>').addClass('buttons');
        buttons.append($('<button>').addClass('marker-delete-button btn btn-danger').html("<i class='fa fa-trash'></i>"));
        buttons.append($('<button>').addClass('marker-update-button btn btn-success').html("<i class='fa fa-check'></i>"));

        container.append(buttons);
        return container[0];
    }
});

var PointListManager = Class.extend({
    init:          function () {
        this.container = $('#left-hand-display');
        this.pointsList = this.container.find('.pointsList');
        this.noPointsYet = this.container.find('.noPointsYet');

        $('.pointsList').sortable({
            handle: ".left-side"
        });
    },
    addPoint:      function (marker, e) {
        if (this.pointsList.children().length == 0) {
            this.noPointsYet.hide();
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
            map.removeLayer(marker);
            _self.removePoint(marker._leaflet_id);
        });
        right.append(editButton, deleteButton);

        var pointContainer = $('<div>').addClass('point').attr('data-point-id', marker._leaflet_id);
        pointContainer.append(left, mid, right);
        this.pointsList.append(pointContainer);


    },
    updatePoint:   function (markerId, newName) {
        var obj = this.findPointById(markerId);
        obj.find('.title').text(newName);
    },
    removePoint:   function (markerId) {
        var obj = this.findPointById(markerId);
        if (obj != null) {
            obj.remove();
        }

        if (this.pointsList.children().length == 0) {
            this.noPointsYet.show();
        }
    },
    findPointById: function (markerId) {
        var objToReturn = null;
        this.pointsList.children().each(function (i, obj) {
            if ($(obj).attr('data-point-id') == markerId) {
                objToReturn = $(obj);
            }
        });

        return objToReturn;
    }
});
