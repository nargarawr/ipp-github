/**
 * Document ready function. Gets the geocodes the submitted data and submits the form
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    // If a user submits the form, and goes back the value of the hidden fields remain, so we clear them here
    $("#formError").val("");
    $("#start_lat").val("");
    $("#start_lng").val("");
    $("#end_lat").val("");
    $("#end_lng").val("");

    var userLocation = $('#userLocation');
    var location = "";
    if ($('#userLoggedIn').val() == 1 && userLocation.val() != "" && userLocation.val() != null) {
        location = userLocation.val();
    }

    // If user has location set, centre the map there
    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: location,
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        var centreOn = {lat: 0, lng: 0};
        if (response.status == "ZERO_RESULTS") {
            // Center on Nottingham if user location not set
            centreOn.lat = 52.95338;
            centreOn.lng = -1.18689;
        } else {
            // Center on user location if set
            var latlng = response.results[0].geometry.location;
            centreOn.lat = latlng.lat;
            centreOn.lng = latlng.lng;
        }

        drawMap(centreOn.lat, centreOn.lng);
    });

    // Submit search form
    $('#submit_addresses').click(function () {
        submitSearchForm();
    });

    $('#start_address').keypress(function(e) {
        handleEnterPress(e);
    });
    $('#end_address').keypress(function(e) {
        handleEnterPress(e);
    });
});


function handleEnterPress(event) {
    // If we press return, and the start box is not empty, submit
    if (event.which == 13 && $('#start_address').val() !== '') {
        submitSearchForm();
    }
}

/**
 * Draws the MapBox map behind the search box
 *
 * @author Craig Knott
 *
 * @param lat Latitude of the centre of the map
 * @param lng Longitude of the centre of the map
 */
function drawMap(lat, lng) {
    $('#map').css('height', window.innerHeight - 63);

    map = L.map('map', {zoomControl: false}).setView([lat, lng], 13);

    // Disable scroll + touch zoom
    map.touchZoom.disable();
    map.scrollWheelZoom.disable();

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

            $("#searchForm").submit();
        });

    });
}
