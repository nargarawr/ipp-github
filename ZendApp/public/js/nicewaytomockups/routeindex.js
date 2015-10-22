$(document).ready(function() {
    positionPopover();

    $('body').click(function(){
        $('#helpPopover').hide();
    });
});

$(window).resize(function() {
    positionPopover();
});

function positionPopover () {
    var sw = window.innerWidth;
    var sh = window.innerHeight;

    if (sh < 635) {
        $('#helpPopover').css("top", -250);
    } else {
        $('#helpPopover').css("top", -60);
        
    }
    $('#helpPopover').css("left", sw - 278);    
    

}