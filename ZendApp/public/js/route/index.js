/**
 * Document ready function. Gets the geocodes the submitted data and submits the form
 *
 * @author Craig Knott
 */
$(document).ready(function(){
 
    $('#submit_addresses').click(function(e){
        // Geocode the start and end points, so we can process them as lat and long values    
        $.ajax({
            type: 'GET',
            url:  "https://maps.googleapis.com/maps/api/geocode/json",
            data: {
                address: $("#start_address").val(),
                key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
            }
        }).success(function (response) {
            if (response.status == "OK") {
                $.ajax({
                    type: 'GET',
                    url:  "https://maps.googleapis.com/maps/api/geocode/json",
                    data: {
                        address: $("#end_address").val(),
                        key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
                    }
                }).success(function (response2) {
                    if (response2.status == "OK") {
                        var geocodedStart = response.results[0].geometry.location;
                        var geocodedEnd = response2.results[0].geometry.location;
                        
                        $("#start_lat").val(geocodedStart.lat);
                        $("#start_lng").val(geocodedStart.lng);
                        $("#end_lat").val(geocodedEnd.lat);
                        $("#end_lng").val(geocodedEnd.lng);
                       
                        $("#searchForm").submit();
                    } else {
                        console.log("Error with end")
                        // TODO ERROR HERE 
                    }
                });
            } else {
                console.log("Error with start")
                // TODO ERROR HERE 
            }
        });
    });
    
});
    