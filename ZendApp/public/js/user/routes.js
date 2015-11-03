$(document).ready(function () {
    $('.delBtn').click(function (e) {
        var url = this.href;
        e.preventDefault();
        $.confirm({
            title:           'Delete point?',
            icon:            'fa fa-warning',
            content:         'Are you sure you wish to delete this route? This action is irreversible.',
            theme:           'black',
            confirmButton:   'Delete',
            keyboardEnabled: true,
            confirm:         function () {
                window.location = url;
            }
        });
    });
});