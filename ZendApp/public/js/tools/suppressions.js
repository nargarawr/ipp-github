$(document).ready(function () {
    $('#loadSuppressions').click(function (e) {
        var uid = $('#userid').val();
        if (uid === "") {
            return;
        }

        var url = "/account/getuserdetails/userid/" + uid;
        cache["/account/getuserdetails/userid/" + uid] = undefined;
        cacheAndCallAjax(
            url,
            loadSuppressions,
            {
                url: url,
                uid: uid
            }
        );
    });

    $('#saveSuppressions').click(function (e) {
        var uid = $('#userid').val();
        if (uid === "") {
            return;
        }

        saveSuppressions();
    });

    if ($('#lsfuVal').length) {
        if ($('#lsfuVal').val() !== '') {
            var uid = $('#userid').val();
            if (uid === "") {
                return;
            }

            var url = "/account/getuserdetails/userid/" + uid;
            cache["/account/getuserdetails/userid/" + uid] = undefined;
            cacheAndCallAjax(
                url,
                loadSuppressions,
                {
                    url: url,
                    uid: uid
                }
            );
        }
    }
});

function saveSuppressions() {

    var suppressionArray = [];
    $('input[type=checkbox]').each(function () {
        var idLength = (this.id).length;
        var id = (this.id).substr(3, idLength - 5);

        suppressionArray.push({
            'appid':      id,
            'suppressed': ((this.checked) ? 1 : 0)
        });
    });

    $.ajax({
        type: 'POST',
        url:  '/tools/updateusersuppressions',
        data: {
            userid:       $('#userid').val(),
            suppressions: suppressionArray
        }
    }).success(function (response) {
        $('#confirmSave').removeClass('hidden');
        setTimeout(function () {
            $('#confirmSave').addClass('hidden');
        }, 1500);
    });
}

function loadSuppressions(response, params) {
    cache[params.url] = response;
    var obj = JSON.parse(response);
    if (obj !== false) {
        $("#suppression_userInfoDisplayTableBody").empty();
        $('#suppression_userInfoDisplayTableBody')
            .append($('<tr>')
                .append($('<td>').append($('<span>').text(obj.id)))
                .append($('<td>').append($('<span>').text(obj.username)))
                .append($('<td>').append($('<span>').text(obj.fname + " " + obj.lname)))
        );

        $.ajax({
            url: "/account/getuserappswithsuppressions/userid/" + params.uid
        }).success(function (response) {
            $('#suppressionsCheckboxContainer').empty();
            var parsedJSON = JSON.parse(response);

            for (var i = 0; i < parsedJSON.length; i++) {
                var obj = parsedJSON[i];

                $("#suppressionsCheckboxContainer")
                    .append($('<div class="col-md-3 col-sm-6">')
                        .append($('<div class="checkbox-inline">')
                            .attr('class', (obj.is_active == 0) ? 'deactive' : '')
                            .append($('<label>')
                                .append($('<input>')
                                    .attr('type', 'checkbox')
                                    .attr('id', 'cb_' + obj.pk_app_id + '_' + obj.suppressed)
                                    .attr('disabled', (obj.is_active == 0))
                                    .prop('checked', (obj.suppressed == 1))
                            )
                                .append(" " + obj.name)
                        )
                    )
                )
            }
            $('#suppressionDisplay').removeClass('hidden');
        });
    } else {
        $('#errorDisplay').removeClass('hidden');
        setTimeout(function () {
            $('#errorDisplay').addClass('hidden');
        }, 1500);
        $('#suppressionDisplay').addClass('hidden');
    }
}
