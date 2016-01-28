/**
 * This is a heavily modified version of the L.Routing library, available at https://github.com/Turistforeningen/leaflet-routing
 * The main modifications are integration with the PointListManager class found in /user/create.js, which means I can
 * add to the functionality here when necessary. If you are looking for any thing to do with maps, search for 'plm', because
 * this is where I call functions from /user/create.js, and this is probably what you'll need.
 *
 * @author Craig Knott
 */

/*
 * L.Routing main class
 *
 * Main clase for the Leaflet routing module
 *
 * @dependencies L
 *
 * @usage new L.Routing(options);
 *
 */

var plm;

L.Routing = L.Control.extend({

    // INCLUDES
    includes:                [L.Mixin.Events]

    // CONSTANTS
    , statics:               {
        VERSION: '0.1.1-dev'
    }

    // OPTIONS
    , options:               {
        position:       'topleft'
        , tooltips:     {
            waypoint: 'Waypoint. Drag to move; Click to see details.',
            segment:  'Drag to create a new waypoint'
        }
        , icons:        {
            start:    new L.Icon.Default()
            , end:    new L.Icon.Default()
            , normal: new L.Icon.Default()
            , draw:   new L.Icon.Default()
        }
        , styles:       {
            trailer:  {}
            , track:  {}
            , nodata: {}
        }
        , zIndexOffset: 2000
        , routing:      {
            router: null       // function (<L.Latlng> l1, <L.Latlng> l2, <Function> cb)
        }
        , snapping:     {
            layers:        []         // layers to snap to
            , sensitivity: 10   // snapping sensitivity
            , vertexonly:  false // vertex only snapping
        }
        , shortcut:     {
            draw: {
                enable:  68,      // char code for 'd'
                disable: 81      // char code for 'q'
            }
        }
    }

    /**
     * Routing Constructor
     *
     * @access public
     *
     * @param <Object> options - non-default options
     *
     *  render display of segments and waypoints
     */
    , initialize:            function (options) {
        this._editing = false;
        this._drawing = false;
        plm = new PointsListManager(this);

        L.Util.setOptions(this, options);

        if ($('#routeId').val() != '') {
            plm.addExistingPoints($('#routeId').val());
        }

    }
    /**
     * Called when controller is added to map
     *
     * @access public
     *
     * @param <L.Map> map - map instance
     *
     * @return <HTMLElement> container
     */
    , onAdd:                 function (map) {
        this._map = map;
        this._container = this._map._container;
        this._overlayPane = this._map._panes.overlayPane;
        this._popupPane = this._map._panes.popupPane;

        this._router = this.options.routing.router;
        this._segments = new L.FeatureGroup().addTo(map);
        this._waypoints = new L.FeatureGroup().addTo(map);
        this._waypoints._first = null;
        this._waypoints._last = null;

        //L.DomUtil.disableTextSelection();
        //this._tooltip = new L.Tooltip(this._map);
        //this._tooltip.updateContent({ text: L.drawLocal.draw.marker.tooltip.start });

        if (this.options.shortcut) {
            L.DomEvent.addListener(this._container, 'keyup', this._keyupListener, this);
        }

        this._draw = new L.Routing.Draw(this, this.options);
        if (!plm.readOnly) {
            this._edit = new L.Routing.Edit(this, this.options);
            this._edit.enable();
        }

        this.on('waypoint:click', this._waypointClickHandler, this)
        this._segments.on('mouseover', this._fireSegmentEvent, this);

        var container = L.DomUtil.create('div', 'leaflet-routing');

        return container;
    }

    /**
     * Called when controller is removed from map
     *
     * @access public
     *
     * @param <L.Map> map - map instance
     */
    , onRemove:              function (map) {
        this.off('waypoint:click', this._waypointClickHandler, this)
        this._segments.off('mouseover', this._fireSegmentEvent, this);
        this._edit.off('segment:mouseout', this._fireSegmentEvent, this);
        this._edit.off('segment:dragstart', this._fireSegmentEvent, this);
        this._edit.off('segment:dragend', this._fireSegmentEvent, this);

        this._edit.disable();
        this._draw.disable();

        L.DomUtil.enableTextSelection();
        // this._tooltip.dispose();
        // this._tooltip = null;
        L.DomEvent.removeListener(this._container, 'keyup', this._keyupListener);

        delete this._draw;
        delete this._edit;
        delete this._map;
        delete this._router;
        delete this._segments;
        delete this._waypoints;
        delete this.options;
    }

    /**
     * Called whenever a waypoint is clicked
     * Called whenever a waypoint is clicked
     *
     * @access private
     *
     * @param <L.Event> e - click event
     */
    , _waypointClickHandler: function (e) {
    }

    /**
     * Add new waypoint to path
     *
     * @access public
     *
     * @param <L.Marker> marker - new waypoint marker (can be ll)
     * @param <L.Marker> prev - previous waypoint marker
     * @param <L.Marker> next - next waypoint marker
     * @param <Function> cb - callback method (err, marker)
     *
     * @return void
     */
    , addWaypoint:           function (marker, prev, next, cb) {
        if (plm.readOnly && !(plm.pointsNotAdded)) {
            return;
        }

        if (marker instanceof L.LatLng) {
            marker = new L.Marker(marker, {title: this.options.tooltips.waypoint});
        }

        marker._routing = {
            prevMarker:   prev
            , nextMarker: next
            , prevLine:   null
            , nextLine:   null
            , timeoutID:  null
        };
        var _self = this;
        var popupData = {
            name:        'Point ' + plm.numPoints,
            description: ''
        };
        marker.bindPopup(plm.getPopupHTML(marker._latlng, popupData))
            .on('popupopen', function () {
                plm.isPopupOpen = true;
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
                            plm.isPopupOpen = false;
                            plm.removePoint(tempMarker._leaflet_id);
                            _self.removeWaypoint(marker, function () {
                            });
                        }
                    });
                });

                $(".marker-update-button").click(function () {
                    var newName = $(marker._popup._content).find('.point_title').val();
                    plm.updatePoint(tempMarker._leaflet_id, newName);
                    marker.closePopup();
                });

                if (_self.readOnly) {
                    $('.point_title').attr("readonly", "true");
                    $('textarea').attr("readonly", "true");
                    $(".marker-delete-button").remove();
                }
            });

        if (this._waypoints._first === null && this._waypoints._last === null) {
            this._waypoints._first = marker;
            this._waypoints._last = marker;
        } else if (next === null) {
            this._waypoints._last = marker;
        } else if (prev === null) {
            this._waypoints._first = marker;
        }

        if (marker._routing.prevMarker !== null) {
            marker._routing.prevMarker._routing.nextMarker = marker;
            marker._routing.prevLine = marker._routing.prevMarker._routing.nextLine;
            if (marker._routing.prevLine !== null) {
                marker._routing.prevLine._routing.nextMarker = marker;
            }

            try {
                toggleMapLoadingIcon();
            } catch(err) {
            }
        }

        if (marker._routing.nextMarker !== null) {
            marker._routing.nextMarker._routing.prevMarker = marker;
            marker.nextLine = marker._routing.nextMarker._routing.prevLine;
            if (marker._routing.nextLine !== null) {
                marker._routing.nextLine._routing.prevMarker = marker;
            }
        }

        marker.on('mouseover', this._fireWaypointEvent, this);
        marker.on('mouseout', this._fireWaypointEvent, this);
        marker.on('dragstart', this._fireWaypointEvent, this);
        marker.on('dragend', this._fireWaypointEvent, this);
        marker.on('drag', this._fireWaypointEvent, this);
        marker.on('click', this._fireWaypointEvent, this);

        this.routeWaypoint(marker, cb);
        this._waypoints.addLayer(marker);
        if (!plm.readOnly) {
            marker.dragging.enable();
        }

        plm.addPoint(marker, marker._latlng);
    }

    /**
     * Remove a waypoint from path
     *
     * @access public
     *
     * @param <L.Marker> marker - new waypoint marker (can be ll)
     * @param <Function> cb - callback method
     *
     * @return void
     */
    , removeWaypoint:        function (marker, cb) {
        marker.off('mouseover', this._fireWaypointEvent, this);
        marker.off('mouseout', this._fireWaypointEvent, this);
        marker.off('dragstart', this._fireWaypointEvent, this);
        marker.off('dragend', this._fireWaypointEvent, this);
        marker.off('drag', this._fireWaypointEvent, this);
        marker.off('click', this._fireWaypointEvent, this);

        var prev = marker._routing.prevMarker;
        var next = marker._routing.nextMarker;

        if (this._waypoints._first && marker._leaflet_id === this._waypoints._first._leaflet_id) {
            this._waypoints._first = next;
        }

        if (this._waypoints._last && marker._leaflet_id === this._waypoints._last._leaflet_id) {
            this._waypoints._last = prev;
        }

        if (prev !== null) {
            prev._routing.nextMarker = next;
            prev._routing.nextLine = null;
        }

        if (next !== null) {
            next._routing.prevMarker = prev;
            next._routing.prevLine = null;
        }

        if (marker._routing.nextLine !== null) {
            this._segments.removeLayer(marker._routing.nextLine);
        }

        if (marker._routing.prevLine !== null) {
            this._segments.removeLayer(marker._routing.prevLine);
        }

        this._waypoints.removeLayer(marker);

        if (prev !== null) {
            this.routeWaypoint(prev, cb);
        } else if (next !== null) {
            this.routeWaypoint(next, cb);
        } else {
            this._draw.enable();
            cb(null, null);
        }

        try {
            toggleMapLoadingIcon();
        } catch(err) {
        }
    }

    /**
     * Route with respect to waypoint
     *
     * @access public
     *
     * @param <L.Marker> marker - marker to route on
     * @param <Function> cb - callback function
     *
     * @return void
     *
     *  add propper error checking for callback
     */
    , routeWaypoint:         function (marker, cb) {
        var i = 0;
        var firstErr;
        var $this = this;
        var callback = function (err, data) {
            i++;
            firstErr = firstErr || err;
            if (i === 2) {
                $this.fire('routing:routeWaypointEnd', {err: firstErr});
                cb(firstErr, marker);
            }
        }

        this.fire('routing:routeWaypointStart');

        this._routeSegment(marker._routing.prevMarker, marker, callback);
        this._routeSegment(marker, marker._routing.nextMarker, callback);
    }

    /**
     * Recalculate the complete route by routing each segment
     *
     * @access public
     *
     * @param <Function> cb - callback function
     *
     * @return void
     *
     *  add propper error checking for callback
     */
    , rerouteAllSegments:    function (cb) {
        var numSegments = this.getWaypoints().length - 1;
        var callbackCount = 0;
        var firstErr;
        var $this = this;

        var callback = function (err, data) {
            callbackCount++;
            firstErr = firstErr || err;
            if (callbackCount >= numSegments) {
                $this.fire('routing:rerouteAllSegmentsEnd', {err: firstErr});
                if (cb) {
                    cb(firstErr);
                }
            }
        };

        $this.fire('routing:rerouteAllSegmentsStart');

        if (numSegments < 1) {
            return callback(null, true);
        }

        this._eachSegment(function (m1, m2) {
            this._routeSegment(m1, m2, callback);
        });
    }

    /**
     * Route segment between two markers
     *
     * @access private
     *
     * @param <L.Marker> m1 - first waypoint marker
     * @param <L.Marker> m2 - second waypoint marker
     * @param <Function> cb - callback function (<Error> err, <String> data)
     *
     * @return void
     *
     *  logic if router fails
     */
    , _routeSegment:         function (m1, m2, cb) {
        var $this = this;

        if (m1 === null || m2 === null) {
            return cb(null, true);
        }

        this._router(m1.getLatLng(), m2.getLatLng(), function (err, layer) {
            if (typeof layer === 'undefined') {
                var layer = new L.Polyline([m1.getLatLng(), m2.getLatLng()], $this.options.styles.nodata);
            } else {
                layer.setStyle($this.options.styles.track);
            }

            layer._routing = {
                prevMarker:   m1
                , nextMarker: m2
            };

            if (m1._routing.nextLine !== null) {
                $this._segments.removeLayer(m1._routing.nextLine);
            }
            $this._segments.addLayer(layer);

            m1._routing.nextLine = layer;
            m2._routing.prevLine = layer;

            return cb(err, layer);
        });
    }

    /**
     * Iterate over all segments and execute callback for each segment
     *
     * @access private
     *
     * @param <function> callback - function to call for each segment
     * @param <object> context - callback execution context (this). Optional, default: this
     *
     * @return void
     */
    , _eachSegment:          function (callback, context) {
        var thisArg = context || this;
        var marker = this.getFirst();

        if (marker === null) {
            return;
        }

        while (marker._routing.nextMarker !== null) {
            var m1 = marker;
            var m2 = marker._routing.nextMarker;
            var line = marker._routing.nextLine;

            callback.call(thisArg, m1, m2, line);

            marker = marker._routing.nextMarker;
        }
    }

    /**
     * Fire events
     *
     * @access private
     *
     * @param <L.Event> e - mouse event
     *
     * @return void
     */
    , _fireWaypointEvent:    function (e) {
        this.fire('waypoint:' + e.type, {marker: e.target});
    }

    /**
     *
     */
    , _fireSegmentEvent:     function (e) {
        if (e.type.split(':').length === 2) {
            this.fire(e.type);
        } else {
            this.fire('segment:' + e.type);
        }
    }

    /**
     * Get first waypoint
     *
     * @access public
     *
     * @return L.Marker
     */
    , getFirst:              function () {
        return this._waypoints._first;
    }

    /**
     * Get last waypoint
     *
     * @access public
     *
     * @return L.Marker
     */
    , getLast:               function () {
        return this._waypoints._last;
    }

    /**
     * Get all waypoints
     *
     * @access public
     *
     * @return <L.LatLng[]> all waypoints or empty array if none
     */
    , getWaypoints:          function () {
        var latLngs = [];

        this._eachSegment(function (m1) {
            latLngs.push(m1.getLatLng());
        });

        if (this.getLast()) {
            latLngs.push(this.getLast().getLatLng());
        }

        return latLngs;
    }

    /**
     * Concatenates all route segments to a single polyline
     *
     * @access public
     *
     * @return <L.Polyline> polyline, with empty _latlngs when no route segments
     */
    , toPolyline:            function () {
        var latLngs = [];

        this._eachSegment(function (m1, m2, line) {
            latLngs = latLngs.concat(line.getLatLngs());
        });

        return L.polyline(latLngs);
    }

    /**
     * Export route to GeoJSON
     *
     * @access public
     *
     * @param <boolean> enforce2d - enforce 2DGeoJSON
     *
     * @return <object> GeoJSON object
     *
     */
    , toGeoJSON:             function (enforce2d) {
        var geojson = {type: "LineString", properties: {waypoints: []}, coordinates: []};
        var current = this._waypoints._first;

        if (current === null) {
            return geojson;
        }

        // First waypoint marker
        geojson.properties.waypoints.push({
            coordinates: [current.getLatLng().lng, current.getLatLng().lat],
            _index:      0
        });

        while (current._routing.nextMarker) {
            var next = current._routing.nextMarker;

            // Line segment
            var tmp = current._routing.nextLine.getLatLngs();
            for (var i = 0; i < tmp.length; i++) {
                if (tmp[i].alt && (typeof enforce2d === 'undefined' || enforce2d === false)) {
                    geojson.coordinates.push([tmp[i].lng, tmp[i].lat, tmp[i].alt]);
                } else {
                    geojson.coordinates.push([tmp[i].lng, tmp[i].lat]);
                }
            }

            // Waypoint marker
            geojson.properties.waypoints.push({
                coordinates: [next.getLatLng().lng, next.getLatLng().lat],
                _index:      geojson.coordinates.length - 1
            });

            // Next waypoint marker
            current = current._routing.nextMarker;
        }

        return geojson
    }

    /**
     * Import route from GeoJSON
     *
     * @access public
     *
     * @param <object> geojson - GeoJSON object with waypoints
     * @param <object> opts - parsing options
     * @param <function> cb - callback method (err)
     *
     * @return undefined
     *
     */
    , loadGeoJSON:           function (geojson, opts, cb) {
        var $this, oldRouter, index, waypoints;

        $this = this;

        // Check for optional options parameter
        if (typeof opts === 'function' || typeof opts === 'undefined') {
            cb = opts;
            opts = {}
        }

        // Set default options
        opts.waypointDistance = opts.waypointDistance || 50;
        opts.fitBounds = opts.fitBounds || true;

        // Check for waypoints before processing geojson
        if (!geojson.properties || !geojson.properties.waypoints) {
            if (!geojson.properties) {
                geojson.properties = {}
            }
            ;
            geojson.properties.waypoints = [];

            for (var i = 0; i < geojson.coordinates.length; i = i + opts.waypointDistance) {
                geojson.properties.waypoints.push({
                    _index:      i,
                    coordinates: geojson.coordinates[i].slice(0, 2)
                });
            }

            if (i > geojson.coordinates.length - 1) {
                geojson.properties.waypoints.push({
                    _index:      geojson.coordinates.length - 1,
                    coordinates: geojson.coordinates[geojson.coordinates.length - 1].slice(0, 2)
                });
            }
        }

        index = 0;
        oldRouter = $this._router;
        waypoints = geojson.properties.waypoints;

        // This is a fake router.
        //
        // It is currently not possible to add a waypoint with a known line segment
        // manually. We are hijacking the router so that we can intercept the
        // request and return the correct linesegment.
        //
        // It you want to fix this; please make a patch and submit a pull request on
        // GitHub.
        $this._router = function (m1, m2, cb) {
            var start =
                waypoints[index - 1]._index;
            var end = waypoints[index]._index + 1;

            return cb(null, L.GeoJSON.geometryToLayer({
                type:        'LineString',
                coordinates: geojson.coordinates.slice(start, end)
            }));
        };

        // Clean up
        end = function () {
            $this._router = oldRouter; // Restore router
            // Set map bounds based on loaded geometry
            setTimeout(function () {
                if (opts.fitBounds) {
                    $this._map.fitBounds(L.polyline(L.GeoJSON.coordsToLatLngs(geojson.coordinates)).getBounds());
                }

                if (typeof cb === 'function') {
                    cb(null);
                }
            }, 0);
        }

        // Add waypoints
        add = function () {
            if (!waypoints[index]) {
                return end()
            }

            var coords = waypoints[index].coordinates;
            var prev = $this._waypoints._last;

            $this.addWaypoint(L.latLng(coords[1], coords[0]), prev, null, function (err, m) {
                add(++index);
            });
        }

        add();
    }

    /**
     * Start (or continue) drawing
     *
     * Call this method in order to start or continue drawing. The drawing handler
     * will be activate and the user can draw on the map.
     *
     * @access public
     *
     * @return void
     *
     *  check enable
     */
    , draw:                  function (enable) {
        if (typeof enable === 'undefined') {
            var enable = true;
        }

        if (enable) {
            this._draw.enable();
        } else {
            this._draw.disable();
        }
    }

    /**
     * Enable or disable routing
     *
     * @access public
     *
     * @return void
     *
     *  check enable
     */
    , routing:               function (enable) {
        throw new Error('Not implemented');
    }

    /**
     * Enable or disable snapping
     *
     * @access public
     *
     * @return void
     *
     *  check enable
     */
    , snapping:              function (enable) {
        throw new Error('Not implemented');
    }

    /**
     * Key up listener
     *
     * * `ESC` to cancel drawing
     * * `M` to enable drawing
     *
     * @access private
     *
     * @return void
     */
    , _keyupListener:        function (e) {
        if (e.keyCode === this.options.shortcut.draw.disable) {
            this._draw.disable();
        } else if (e.keyCode === this.options.shortcut.draw.enable) {
            this._draw.enable();
        }
    }

});

L.Util.extend(L.LineUtil, {

    /**
     * Snap to all layers
     *
     * @param <Latlng> latlng - original position
     * @param <Number> id - leaflet unique id
     * @param <Object> opts - snapping options
     *
     * @return <Latlng> closest point
     */
    snapToLayers: function (latlng, id, opts) {
        var i, j, keys, feature, res, sensitivity, vertexonly, layers, minDist, minPoint, map;


        sensitivity = opts.sensitivity || 10;
        vertexonly = opts.vertexonly || false;
        layers = opts.layers || [];
        minDist = Infinity;
        minPoint = latlng;
        minPoint._feature = null; // containing layer

        if (!opts || !opts.layers || !opts.layers.length) {
            return minPoint;
        }

        map = opts.layers[0]._map; //  check for undef

        for (i = 0; i < opts.layers.length; i++) {
            keys = Object.keys(opts.layers[i]._layers);
            for (j = 0; j < keys.length; j++) {
                feature = opts.layers[i]._layers[keys[j]];

                // Don't even try snapping to itself!
                if (id === feature._leaflet_id) {
                    continue;
                }

                // GeometryCollection
                if (feature._layers) {
                    var newLatlng = this.snapToLayers(latlng, id, {
                        'sensitivity': sensitivity,
                        'vertexonly':  vertexonly,
                        'layers':      [feature]
                    });
                    // What if this is the same?
                    res = {'minDist': latlng.distanceTo(newLatlng), 'minPoint': newLatlng};

                    // Marker
                } else if (feature instanceof L.Marker) {
                    res = this._snapToLatlngs(latlng, [feature.getLatLng()], map, sensitivity, vertexonly, minDist);

                    // Polyline
                } else if (feature instanceof L.Polyline) {
                    res = this._snapToLatlngs(latlng, feature.getLatLngs(), map, sensitivity, vertexonly, minDist);

                    // MultiPolyline
                } else if (feature instanceof L.MultiPolyline) {
                    console.error('Snapping to MultiPolyline is currently unsupported', feature);
                    res = {'minDist': minDist, 'minPoint': minPoint};

                    // Polygon
                } else if (feature instanceof L.Polygon) {
                    res = this._snapToPolygon(latlng, feature, map, sensitivity, vertexonly, minDist);

                    // MultiPolygon
                } else if (feature instanceof L.MultiPolygon) {
                    res = this._snapToMultiPolygon(latlng, feature, map, sensitivity, vertexonly, minDist);

                    // Unknown
                } else {
                    console.error('Unsupported snapping feature', feature);
                    res = {'minDist': minDist, 'minPoint': minPoint};
                }

                if (res.minDist < minDist) {
                    minDist = res.minDist;
                    minPoint = res.minPoint;
                    minPoint._feature = feature;
                }

            }
        }

        return minPoint;
    },

    /**
     * Snap to Polygon
     *
     * @param <Latlng> latlng - original position
     * @param <L.Polygon> feature -
     * @param <L.Map> map -
     * @param <Number> sensitivity -
     * @param <Boolean> vertexonly -
     * @param <Number> minDist -
     *
     * @return <Object> minDist and minPoint
     */
    _snapToPolygon: function (latlng, polygon, map, sensitivity, vertexonly, minDist) {
        var res, keys, latlngs, i, minPoint;

        minPoint = null;

        latlngs = polygon.getLatLngs();
        latlngs.push(latlngs[0]);
        res = this._snapToLatlngs(latlng, polygon.getLatLngs(), map, sensitivity, vertexonly, minDist);
        if (res.minDist < minDist) {
            minDist = res.minDist;
            minPoint = res.minPoint;
        }

        keys = Object.keys(polygon._holes);
        for (i = 0; i < keys.length; i++) {
            latlngs = polygon._holes[keys[i]];
            latlngs.push(latlngs[0]);
            res = this._snapToLatlngs(latlng, polygon._holes[keys[i]], map, sensitivity, vertexonly, minDist);
            if (res.minDist < minDist) {
                minDist = res.minDist;
                minPoint = res.minPoint;
            }
        }

        return {'minDist': minDist, 'minPoint': minPoint};
    },

    /**
     * Snap to MultiPolygon
     *
     * @param <Latlng> latlng - original position
     * @param <L.Polygon> feature -
     * @param <L.Map> map -
     * @param <Number> sensitivity -
     * @param <Boolean> vertexonly -
     * @param <Number> minDist -
     *
     * @return <Object> minDist and minPoint
     */
    _snapToMultiPolygon: function (latlng, multipolygon, map, sensitivity, vertexonly, minDist) {
        var i, keys, res, minPoint;

        minPoint = null;

        keys = Object.keys(multipolygon._layers);
        for (i = 0; i < keys.length; i++) {
            res = this._snapToPolygon(latlng, multipolygon._layers[keys[i]], map, sensitivity, vertexonly, minDist);

            if (res.minDist < minDist) {
                minDist = res.minDist;
                minPoint = res.minPoint;
            }
        }

        return {'minDist': minDist, 'minPoint': minPoint};
    },


    /**
     * Snap to <Array> of <Latlang>
     *
     * @param <LatLng> latlng - cursor click
     * @param <Array> latlngs - array of <L.LatLngs> to snap to
     * @param <Object> opts - snapping options
     * @param <Boolean> isPolygon - if feature is a polygon
     *
     * @return <Object> minDist and minPoint
     */
    _snapToLatlngs: function (latlng, latlngs, map, sensitivity, vertexonly, minDist) {
        var i, tmpDist, minPoint, p, p1, p2, d2;

        p = map.latLngToLayerPoint(latlng);
        p1 = minPoint = null;

        for (i = 0; i < latlngs.length; i++) {
            p2 = map.latLngToLayerPoint(latlngs[i]);

            if (!vertexonly && p1 !== null) {
                tmpDist = L.LineUtil.pointToSegmentDistance(p, p1, p2);
                if (tmpDist < minDist && tmpDist <= sensitivity) {
                    minDist = tmpDist;
                    minPoint = map.layerPointToLatLng(L.LineUtil.closestPointOnSegment(p, p1, p2));
                }
            } else if ((d2 = p.distanceTo(p2)) && d2 <= sensitivity && d2 < minDist) {
                minDist = d2;
                minPoint = latlngs[i];
            }

            p1 = p2;
        }

        return {'minDist': minDist, 'minPoint': minPoint};
    }

});

L.Marker.include({
    snapTo: function (a) {
        return L.LineUtil.snapToLayers(a, this._leaflet_id, this.options.snapping)
    }
});

(function () {
    L.Routing.Storage = L.MultiPolyline.extend({
        initialize: function (a, b) {
            this._layers = {};
            this._options = b;
            this.setLatLngs(a);
            this.on("layeradd", function () {
                console.log("layeradd", arguments)
            }, this)
        }
    });
    L.Routing.storage = function (a, b) {
        return new L.MultiPolyline(a, b)
    }
}());

L.Routing.Draw = L.Handler.extend({
    includes:               [L.Mixin.Events], options: {}, initialize: function (b, a) {
        this._parent = b;
        this._map = b._map;
        this._enabled = false;
        L.Util.setOptions(this, a)
    }, enable:              function () {
        if (this._enabled) {
            return
        }
        this._enabled = true;
        this._hidden = false;
        this._dragging = false;
        this._addHooks();
        this.fire("enabled");
        this._map.fire("routing:draw-start");
        if (this._parent._segments._layers.length === 0) {
            this._map.fire("routing:draw-new")
        } else {
            this._map.fire("routing:draw-continue")
        }
    }, disable:             function () {
        if (!this._enabled) {
            return
        }
        this._enabled = false;
        this._removeHooks();
        this.fire("disabled");
        this._map.fire("routing:draw-end")
    }, _addHooks:           function () {
        if (!this._map || plm.readOnly) {
            return
        }
        if (!this._marker) {
            this._marker = new L.Marker(this._map.getCenter(), {
                icon:         (this.options.icons.draw ? this.options.icons.draw : new L.Icon.Default()),
                opacity:      (this.options.icons.draw ? 1 : 0),
                zIndexOffset: this.options.zIndexOffset,
                clickable:    false
            })
        }
        this._parent.on("waypoint:mouseover", this._catchWaypointEvent, this);
        this._parent.on("waypoint:mouseout", this._catchWaypointEvent, this);
        this._parent.on("waypoint:dragstart", this._catchWaypointEvent, this);
        this._parent.on("waypoint:dragend", this._catchWaypointEvent, this);
        this._map.on("mousemove", this._onMouseMove, this);
        this._map.on("click", this._onMouseClick, this);
        this._marker.addTo(this._map);
    }, _removeHooks:        function () {
        if (!this._map) {
            return
        }
        this._parent.off("waypoint:mouseover", this._catchWaypointEvent, this);
        this._parent.off("waypoint:mouseout", this._catchWaypointEvent, this);
        this._parent.off("waypoint:dragstart", this._catchWaypointEvent, this);
        this._parent.off("waypoint:dragend", this._catchWaypointEvent, this);
        this._map.off("click", this._onMouseClick, this);
        this._map.off("mousemove", this._onMouseMove, this);
        this._map.removeLayer(this._marker);
        delete this._marker;
    }, _catchWaypointEvent: function (b) {
        var a = b.type.split(":")[1];
        if (a === "dragstart" && plm.readOnly) {
            return;
        }

        if (this._hidden) {
            if (this._dragging) {
                if (a === "dragend") {
                    plm.onWayPointDragEnd(b);
                    this._dragging = false
                }
            } else {

                if (a === "mouseout") {
                    this._show()
                } else {
                    if (a === "dragstart") {
                        if (plm.readOnly) {
                            return;
                        }
                        this._dragging = true;
                    }
                }
            }
        } else {
            if (a === "mouseover") {
                this._hide()
            }
        }
    }, _hide:               function () {
        this._hidden = true;
        this._marker.setOpacity(0);
    }, _show:               function () {
        this._hidden = false;
        this._marker.setOpacity(this.options.icons.draw ? 1 : 0);
        this._showTrailer()
    }, _showTrailer:        function () {
    }, _setTrailer:         function (b, a) {
    }, _onMouseMove:        function (b) {
        if (this._hidden) {
            return
        }
        var c = b.latlng;
        var a = this._parent.getLast();
        if (this.options.snapping) {
            c = L.LineUtil.snapToLayers(c, null, this.options.snapping)
        }
        this._marker.setLatLng(c);
        if (a !== null) {
            this._setTrailer(a.getLatLng(), c)
        }
    }, _onMouseClick: function(e) {
        if (this._hidden) { return; }

        var marker, latlng, last;

        latlng = e.latlng;
        if (this.options.snapping) {
            latlng = L.LineUtil.snapToLayers(latlng, null, this.options.snapping);
        }
        marker = new L.Marker(latlng, {title: this.options.tooltips.waypoint });
        last = this._parent.getLast();

        this._setTrailer(latlng, latlng);
        this._parent.addWaypoint(marker, last, null, function(err, data) {
            // console.log(err, data);
        });
    }
});

L.Routing.Edit = L.Handler.extend({
    includes:                [L.Mixin.Events], options: {}, initialize: function (b, a) {
        this._parent = b;
        this._map = b._map;
        this._enabled = false;
        L.Util.setOptions(this, a)
    }, enable:               function () {
        if (this._enabled) {
            return
        }
        this._enabled = true;
        this._addHooks();
        this.fire("enabled");
        this._map.fire("routing:edit-start")
    }, disable:              function () {
        if (!this._enabled) {
            return
        }
        this._enabled = false;
        this._removeHooks();
        this.fire("disabled");
        this._map.fire("routing:edit-end")
    }, _addHooks:            function () {
        if (!this._map) {
            return
        }
        if (!this._mouseMarker) {
            this._mouseMarker = new L.Marker(this._map.getCenter(), {
                icon:         L.divIcon({
                    className:  "line-mouse-marker",
                    iconAnchor: [5, 5],
                    iconSize:   [10, 10]
                }),
                clickable:    true,
                draggable:    true,
                opacity:      0,
                zIndexOffset: this.options.zIndexOffset,
                title:        this.options.tooltips.segment
            })
        }

        this._mouseMarker.addTo(this._map);

        this._parent.on("waypoint:dragstart", this._waypointOnDragstart, this);
        this._parent.on("waypoint:drag", this._waypointOnDrag, this);
        this._parent.on("waypoint:dragend", this._waypointOnDragend, this)
    }, _removeHooks:         function () {
        if (!this._map) {
            return
        }
        this._parent.off("waypoint:dragstart", this._waypointOnDragstart, this);
        this._parent.off("waypoint:drag", this._waypointOnDrag, this);
        this._parent.off("waypoint:dragend", this._waypointOnDragend, this)
    }, _segmentOnMouseover:  function (a) {
        this._mouseMarker.setOpacity(1);
        this._map.on("mousemove", this._segmentOnMousemove, this)
    }, _segmentOnMouseout:   function (a) {
        if (this._dragging) {
            return
        }
        this._mouseMarker.setOpacity(0);
        this._map.off("mousemove", this._segmentOnMousemove, this);
        this.fire("segment:mouseout")
    }, _segmentOnMousemove:  function (a) {
        if (this._dragging) {
            return
        }
        var b = L.LineUtil.snapToLayers(a.latlng, null, {
            layers:      [this._parent._segments],
            sensitivity: 40,
            vertexonly:  false
        });
        if (b._feature === null) {
            this._segmentOnMouseout(a)
        } else {
            this._mouseMarker._snapping = b._feature._routing;
            this._mouseMarker.setLatLng(b)
        }
    }, _segmentOnDragstart:  function (c) {
        var d = c.target.getLatLng();
        var a = c.target._snapping.nextMarker;
        var b = c.target._snapping.prevMarker;
        this._setTrailers(d, a, b, true);
        this._dragging = true;
        this.fire("segment:dragstart")
    }, _segmentOnDrag:       function (c) {
        var d = c.target.getLatLng();
        var a = c.target._snapping.nextMarker;
        var b = c.target._snapping.prevMarker;
        if (this.options.snapping) {
            d = L.LineUtil.snapToLayers(d, null, this.options.snapping)
        }
        c.target.setLatLng(d);
        this._setTrailers(d, a, b)
    }, _segmentOnDragend:    function (c) {
        var a = this._mouseMarker._snapping.nextMarker;
        var b = this._mouseMarker._snapping.prevMarker;
        var d = this._mouseMarker.getLatLng();
        this._parent.addWaypoint(d, b, a, function (e, f) {
        });
        this._dragging = false;
        this._setTrailers(null, null, null, false);
        this.fire("segment:dragend")
    }, _waypointOnDragstart: function (c) {
        var a = c.marker._routing.nextMarker;
        var b = c.marker._routing.prevMarker;
        this._setTrailers(c.marker.getLatLng(), a, b, true)
    }, _waypointOnDrag:      function (c) {
        var d = c.marker._latlng;
        var a = c.marker._routing.nextMarker;
        var b = c.marker._routing.prevMarker;
        if (this.options.snapping) {
            d = L.LineUtil.snapToLayers(d, null, this.options.snapping)
        }
        c.marker.setLatLng(d);
        this._setTrailers(d, a, b)
    }, _waypointOnDragend:   function (a) {
        this._setTrailers(null, null, null, false);
        this._parent.routeWaypoint(a.marker, function (b, c) {
        })
    }, _waypointOnClick:     function (a) {

    }, _setTrailers:         function (d, b, c, a) {

    }
});