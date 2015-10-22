$(document).ready(function() {
    $('#landingPage').css({
        height: ($(window).height()) +'px'
    });

    var pages = [];
    $('.jumbotron').each(function() {
        if ($(this).height() < $(window).height()) {
            $(this).css({
                height: $(window).height() + 'px'
            });
        }
        pages.push('#' + (this.id));
    });

    $('.scrollButtonDown').each(function(i){
        $(this).click(function(){
            $('html, body').animate({
                scrollTop: $(pages[i+1]).offset().top
            }, 1000);
        })
    });

    $('.scrollButtonUp').each(function(i){
        $(this).click(function(){
            $('html, body').animate({
                scrollTop: $(pages[i]).offset().top
            }, 1000);
        })
    });
});