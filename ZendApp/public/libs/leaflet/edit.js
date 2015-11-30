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
        console.log('?')

        this._mouseMarker.addTo(this._map);
        if (!this._trailer1) {
            var b = this._map.getCenter();
            this._trailerOpacity = this.options.styles.trailer.opacity || 0.2;
            var a = L.extend({}, this.options.styles.trailer, {opacity: 0, clickable: false});
            this._trailer1 = new L.Polyline([b, b], a);
            this._trailer2 = new L.Polyline([b, b], a)
        }
        this._trailer1.addTo(this._map);
        this._trailer2.addTo(this._map);
        this._parent.on("segment:mouseover", this._segmentOnMouseover, this);
        this._mouseMarker.on("dragstart", this._segmentOnDragstart, this);
        this._mouseMarker.on("drag", this._segmentOnDrag, this);
        this._mouseMarker.on("dragend", this._segmentOnDragend, this);
        this._parent.on("waypoint:dragstart", this._waypointOnDragstart, this);
        this._parent.on("waypoint:drag", this._waypointOnDrag, this);
        this._parent.on("waypoint:dragend", this._waypointOnDragend, this)
    }, _removeHooks:         function () {
        if (!this._map) {
            return
        }
        this._parent.off("segment:mouseover", this._segmentOnMouseover, this);
        this._mouseMarker.off("dragstart", this._segmentOnDragstart, this);
        this._mouseMarker.off("drag", this._segmentOnDrag, this);
        this._mouseMarker.off("dragend", this._segmentOnDragend, this);
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
        if (typeof a !== "undefined") {
            if (a === false) {
                this._trailer1.setStyle({opacity: 0});
                this._trailer2.setStyle({opacity: 0});
                return
            } else {
                if (b !== null) {
                    this._trailer1.setStyle({opacity: this._trailerOpacity})
                }
                if (c !== null) {
                    this._trailer2.setStyle({opacity: this._trailerOpacity})
                }
            }
        }
        if (b) {
            this._trailer1.setLatLngs([d, b.getLatLng()])
        }
        if (c) {
            this._trailer2.setLatLngs([d, c.getLatLng()])
        }
    }
});