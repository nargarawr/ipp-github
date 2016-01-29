/**
 * Class PointListManager
 *
 * Manages all custom extensions to the L.Router.
 *
 * @author Craig Knott
 */
var PointsListManager = Class.extend({
    /**
     * Initialises the class, setting the values of private variables
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
        this.readOnly = ($('#mapReadOnly').val() != "");
        this.pointsNotAdded = true;

        this.setupListeners();
    },
    /**
     * Assigns listeners to the LHD
     *
     * @author Craig Knott
     */
    setupListeners: function() {
        $('#hide_lhd').click(function () {
            $('#left-hand-display').addClass('hidden');
            $('#left-hand-display-mini').removeClass('hidden');
        });

        $('#show_lhd').click(function () {
            $('#left-hand-display').removeClass('hidden');
            $('#left-hand-display-mini').addClass('hidden');
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
        left.append($('<i>').addClass('fa fa-map-marker'));

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
                .attr('readonly', plm.readOnly)
        );
        container.append(
            $('<textarea>').addClass('form-control in_desc')
                .attr('placeholder', plm.readOnly ? 'No description' : 'Enter a description')
                .text((data === undefined) ? ('') : data.description)
                .attr('readonly', plm.readOnly)
        );

        if (!plm.readOnly) {
            container.append(
                $('<textarea>').addClass('form-control in_media')
                    .attr('placeholder', 'Comma separated list of image links')
                    .text((data === undefined) ? ('') : data.media)
            );
        } else if (data.media !== '') {
            var images = data.media.split(',');

            var div = $('<div>').addClass('viewMedia');
            div.append($('<a>')
                .attr('href', '#')
                .text('View Photos (' + images.length + ')'));

            container.append(
                div
            );

            $(div).click(function (e) {
                e.preventDefault();
                $.alert({
                    title:           'Point Media',
                    icon:            'fa fa-picture-o',
                    content:         generateCarousel(images),
                    theme:           'black',
                    confirmButton:   'Close',
                    keyboardEnabled: true,
                    confirm:         function () {
                    }
                });
            });
        }

        container.append(
            $('<div>').addClass('hidden latHidden').text(e.lat.toString())
        );
        container.append(
            $('<div>').addClass('hidden lngHidden').text(e.lng.toString())
        );

        var buttons = $('<div>').addClass('buttons');
        if (!plm.readOnly) {
            buttons.append($('<button>').addClass('marker-delete-button btn btn-danger').html("<i class='fa fa-trash'></i>"));
        }
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
    /**
     * Display points on this route
     *
     * @param routeId The id of the route the points belong to
     */
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

                var latlng = {
                    _feature: null,
                    lat:      data[i].latitude,
                    lng:      data[i].longitude
                };

                _self.router.addWaypoint(
                    new L.Marker(latlng, {title: 'Waypoint. Drag to move; Click to see details.'}),
                    _self.router.getLast(),
                    null,
                    function (e, f) {
                    },
                    data[i]
                );

            }

            _self.centreMap(middlePoint.latitude, middlePoint.longitude)

            if (plm.readOnly) {
                _self.setReadOnly();
            }
            _self.pointsNotAdded = false;
        });
    },
    /**
     * Makes the map read-only (no ability to edit)
     *
     * @author Craig Knott
     */
    setReadOnly:       function () {
        $('.marker-delete-button-lhd').remove();
        $('.right-side').css("float", "right").css("padding-right", "40px").css("width", "26px");
        $('.middle-side').css("padding-left", "15px");
        $('.point_title').attr("readonly", "true");
        $('.popup-trigger').remove();
        $('.left-side').remove();
        $('.pointsTitle').text("Route Points");
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
     * Call back function for a way point drag ending, updates the popup and LHD for this marker
     *
     * @author Craig Knott
     *
     * @param marker The marker being dragged
     */
    onWayPointDragEnd: function (marker) {
        if (marker.marker == undefined) {
            return;
        }

        var markerId = marker.marker._leaflet_id;
        var newLat = marker.marker._latlng.lat;
        var newLng = marker.marker._latlng.lng;

        var lhd = $('.point[data-point-id=' + markerId + ']').find('.coords');
        lhd.text(
            newLat.toString().slice(0, 7) + ", " + newLng.toString().slice(0, 7)
        );

        var popup = map._layers[markerId]._popup;
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

/**
 * Generates the HTML for the media carousel
 */
function generateCarousel(images) {
    var myCarousel = $('<div>').attr('id', 'myCarousel').addClass('carousel slide').attr('data-ride', 'carousel');
    var indicators = $('<ol>').addClass('carousel-indicators');
    for (var i = 0; i < images.length; i++) {
        indicators.append(
            $('<li>').attr('data-target', '#myCarousel').attr('data-slide-to', i).addClass((i == 0) ? 'active' : '')
        )
    }
    myCarousel.append(indicators);

    var carouselInner = $('<div>').addClass('carousel-inner').attr('role', 'listbox');
    for (i = 0; i < images.length; i++) {
        carouselInner.append(
            $('<div>').addClass('item').addClass((i == 0) ? 'active' : '').append(
                $('<img>').attr('src', images[i])
            )
        )
    }
    myCarousel.append(carouselInner);


    var leftButton = $('<a>').addClass('left carousel-control').attr('href', '#myCarousel').attr('role', 'button').attr('data-slide', 'prev').append(
        $('<span>').addClass('glyphicon glyphicon-chevron-left').attr('aria-hidden', 'true')
    ).append(
        $('<span>').addClass('sr-only').text('Previous')
    );
    myCarousel.append(leftButton);

    var rightButton = $('<a>').addClass('right carousel-control').attr('href', '#myCarousel').attr('role', 'button').attr('data-slide', 'next').append(
        $('<span>').addClass('glyphicon glyphicon-chevron-right').attr('aria-hidden', 'true')
    ).append(
        $('<span>').addClass('sr-only').text('Next')
    );
    myCarousel.append(rightButton);

    return myCarousel.prop('outerHTML');
}