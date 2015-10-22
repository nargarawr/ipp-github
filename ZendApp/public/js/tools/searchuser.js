$(document).ready(function () {
    $('#searchAccount').click(function (e) {
        e.preventDefault();
        searchAccount();
    });
});

function searchAccount() {
    var numInputs = 0;
    $('input[type=text]').each(function (i, obj) {
        if ($(obj).val() !== "") {
            numInputs++;
        }
    });

    var url = "/tools/findaccount/";

    if (numInputs == 0) {
        $('input[type=text]').each(function (i, obj) {
            $("#" + obj.id + "Wrapper").addClass('has-error');
        });
        return;
    } else {
        $('input[type=text]').each(function (i, obj) {
            $("#" + obj.id + "Wrapper").removeClass('has-error');
            var id = obj.id;
            id = id.substring(0, id.length - 5);
            if ($(obj).val() !== "") {
                url += (id + "/" + $(obj).val() + "/");
            }
        });
    }

    cacheAndCallAjax(url, searchAccountCallback);
}

function searchAccountCallback(response) {
    var obj = JSON.parse(response);

    if (obj.length > 0) {
        $('#userInfoDisplay').removeClass('hidden');
        $('#userInfoDisplayTableBody').empty();
        for (var i = 0; i < obj.length; i++) {
            $('#userInfoDisplayTableBody')
                .append($('<tr>')
                    .append($('<td>').append($('<span>').text(obj[i].id)))
                    .append($('<td>').append($('<span>').text(obj[i].username)))
                    .append($('<td>').append($('<span>').text(obj[i].fname + " " + obj[i].lname)))
                    .append($('<td>').append($('<span>').text(obj[i].email)))
                    .append($('<td>').append($('<span>').text((obj[i].datetime_created).substring(0, 10))))
                    .append($('<td>').append($('<span>').text(obj[i].is_active)))
            )
                .append($('<tr class="actionBar">')
                    .append($('<td colspan="6">')
                        .append($('<span>')
                            .html('<a href="/tools/suppressions/lsfu/' + obj[i].id + '">Manage Suppressions</a>')))
            )
        }
    } else {
        $('#errorDisplay').removeClass('hidden');
        setTimeout(function () {
            $('#errorDisplay').addClass('hidden');
        }, 1500);
    }
}
