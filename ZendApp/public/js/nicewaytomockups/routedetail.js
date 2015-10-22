$(document).ready(function() {
    if ( $('#socialStream').height() > window.innerHeight ) {
        $('#socialStream').css("height", window.innerHeight - 10)
        $('#socialStream').css("max-height", window.innerHeight - 10)
        $('#socialStream').css("overflow", "auto")
    } else {
        $('#socialStream').css("overflow", "none")
    }
});

$(window).resize(function() {
    if ( $('#socialStream').height() > window.innerHeight ) {
        $('#socialStream').css("height", window.innerHeight - 10)
        $('#socialStream').css("max-height", window.innerHeight - 10)
        $('#socialStream').css("overflow", "auto")
    } else {
        $('#socialStream').css("overflow", "none")
    }
});

