$(document).ready(function () {
    $('#addSiren').click(function (e) {
        e.preventDefault();
        addNewSiren();
    });

    $('.reactivateSiren, .deactivateSiren').each(function () {
        var isActive = ($(this).hasClass('reactivateSiren'));
        var sr = new sirenRow($(this), isActive);
    });
});

var sirenRow = Class.extend({
    init:           function (link, isActive) {
        this.link = link;
        this.sirenId = this.link.attr('href');
        this.isActive = isActive ? 1 : 0;
        this.setupListeners();
    },
    setupListeners: function () {
        var _self = this;
        $(_self.link).click(function (e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url:  '/tools/changesirenstate',
                data: {
                    sirenId:   _self.sirenId,
                    is_active: _self.isActive
                }
            }).success(function () {
                location.href = "/tools/siren"
            });
        });
    }
});

function addNewSiren() {
    var message = $('#messageInput').val();
    if (!message) {
        $('#messageInputWrapper').addClass('has-error');
        return;
    }

    $.ajax({
        type: 'POST',
        url:  '/tools/addsiren',
        data: {
            message: message,
            app:     $('#appInput').val()
        }
    }).success(function () {
        location.href = "/tools/siren"
    });
    return false;
}