var map;
var mm;

$(document).ready(function () {
    mm = new MapManager(52.95338, -1.18689, 13);
});

var MapManager = Class.extend({
    init:           function (lat, long, zoom) {
        map = L.map('map').setView([lat, long], zoom);

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

        this.setupListeners();
    },
    setupListeners: function () {
        var _self = this;
        /*
         TODO
         Show list of points to the left
         Remove points (on left and on click)
         Connect points
         */

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

        L.marker([e.latlng.lat, e.latlng.lng])
            .bindPopup(this.getPopupHTML(e))
            .on('click', function () {
                _self.isPopupOpen = true;
            })
            .addTo(map);
    },
    getPopupHTML:   function (e) {
        var title = "<p>TITLE</p>";
        var coordinates = e.latlng.lat + ", " + e.latlng.lng;
        var description = "<p>DESCRIPTION</p>";
        var deleteBtn = "<p>DELETE BTN</p>";

        return title + " " + coordinates + " " + description + " " + deleteBtn;
    }
});
