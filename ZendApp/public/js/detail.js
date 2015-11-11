/**
 * Document ready function. Gets the start an end locations for the route
 *
 * @author Craig Knott
 */
$(document).ready(function () {

    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: 'Nottingham',
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
            console.log(response);
    });
});
