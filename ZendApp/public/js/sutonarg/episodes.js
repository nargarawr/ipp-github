$(document).ready(function(){

    $("#semi_final_game_1").click(function(){
        if ($('#semi_final_game_1_maps').hasClass('hidden')) {
            $('#semi_final_game_1_maps').removeClass('hidden');
        } else {
            $('#semi_final_game_1_maps').addClass('hidden');
        }
    });

    $("#semi_final_game_2").click(function(){
        if ($('#semi_final_game_2_maps').hasClass('hidden')) {
            $('#semi_final_game_2_maps').removeClass('hidden');
        } else {
            $('#semi_final_game_2_maps').addClass('hidden');
        }
    });

    $("#final_game").click(function(){
        if ($('#final_game_maps').hasClass('hidden')) {
            $('#final_game_maps').removeClass('hidden');
        } else {
            $('#final_game_maps').addClass('hidden');
        }
    });

    $(document).ready(function () {
        $('.popup-trigger').magnificPopup({
            type:            'inline',
            fixedContentPos: false,
            fixedBgPos:      true,
            overflowY:       'auto',
            closeBtnInside:  true,
            preloader:       false,
            midClick:        true,
            removalDelay:    300,
            mainClass:       'my-mfp-zoom-in'
        });
    });

});