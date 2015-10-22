
$(document).ready(function () {
    $('.filterOpt').each(function (i, obj) {
        $(this).click(function (e) {
            e.preventDefault();
            $(this).toggleClass('btn-default');
            $(this).toggleClass('btn-primary');
            getAllPurchases();
        });
    });

    $('#checkAll').click(function (e) {
        e.preventDefault();
        $('.filterOpt').each(function (i, obj) {
            $(this).addClass('btn-primary');
            $(this).removeClass('btn-default');
        });
        getAllPurchases();
    });

    $('#uncheckAll').click(function (e) {
        e.preventDefault();
        $('.filterOpt').each(function (i, obj) {
            $(this).removeClass('btn-primary');
            $(this).addClass('btn-default');
        });
        getAllPurchases();
    });

    if ($("#datepickerMin").length) {
        $("#datepickerMin").datepicker({
            dateFormat: 'dd/mm/yy',
            onSelect:   function (dateText) {
                getAllPurchases();
            }
        });

        $("#datepickerMax").datepicker({
            dateFormat: 'dd/mm/yy',
            onSelect:   function (dateText) {
                getAllPurchases();
            }
        });

        setUpSlider();
        getAllPurchases();
    }

    $("#sortByDir").change(function () {
        getAllPurchases();
    });

    $("#sortBy").change(function () {
        getAllPurchases();
    });
});

function setUpSlider() {
    var minCost = parseInt($("#costSlider").attr("min"));
    var maxCost = parseInt($("#costSlider").attr("max"));

    $("#costSlider").slider({
        range:  true,
        min:    minCost,
        max:    maxCost,
        values: [minCost, maxCost],
        slide:  function (event, ui) {
            $("#amount").text("£" + ui.values[0] + " - £" + ui.values[1]);
        },
        change: function (event, ui) {
            getAllPurchases();
        }
    });

    $("#amount").text("£" + $("#costSlider").slider("values", 0) + " - £" + $("#costSlider").slider("values", 1));
}

function deletePurchase(purchaseId) {
    var confirmed = confirm("Are you sure you which to delete this purchase?");
    if (confirmed) {
        $.ajax({
            url: "/purchase/removepurchase/purchaseId/" + purchaseId,
        }).done(function () {
            cache["/purchase/getallpurchases/" + getFilters()] = undefined;
            getAllPurchases();
        });
    }
    return false;
}

function getFilters() {
    var filters = "cats/";

    if ($(".filterOpt.btn-primary").length > 0) {
        $('.filterOpt').each(function (i, obj) {
            if ($(this).hasClass('btn-primary')) {
                filters += (this.id) + "&"
            }
        });
        filters = filters.slice(0, -1);
    } else {
        filters += "0";
    }
    filters += "/";

    filters += "mincost/" + $("#costSlider").slider("values", 0) + "/";
    filters += "maxcost/" + $("#costSlider").slider("values", 1) + "/";
    filters += "startdate/" + convertToAmericanDate($("#datepickerMin").val()) + "/";
    filters += "enddate/" + convertToAmericanDate($("#datepickerMax").val()) + "/";
    filters += "sortby/" + $("#sortBy").val() + "/";
    filters += "sortbydir/" + $("#sortByDir").val() + "/";

    return filters;
}

function getAllPurchases(modifiedRow, pageNum, disabled) {
    if (disabled) {
        return;
    }
    var pageLength = 0;
    if (pageNum === undefined) {
        pageNum = 0;
    }

    var url = "/purchase/getallpurchases/" + getFilters();
    cacheAndCallAjax(
        url,
        getAllPurchasesCallBack, {
            modifiedRow: modifiedRow,
            pageNum:     pageNum,
            pageLength:  pageLength
        }
    );
    return false;
}

function getAllPurchasesCallBack(response, params) {
    var parsedJSON = JSON.parse(response);

    $("#archive_tbody").empty();
    $("#rowCount").empty();
    $("#rowCount").text(parsedJSON.length);

    drawPurchasesTable(parsedJSON, params.modifiedRow, params.pageNum, params.pageLength);

    $('#row_' + params.modifiedRow).animate({
            backgroundColor: '#FFFFFF'
        }, 3000
    );
}

function drawPurchasesTable(parsedJSON, modifiedRow, pageNum, pageLength) {

    pageLength = (pageLength == 0) ? parsedJSON.length : pageLength;
    var startPosition = pageNum * pageLength;
    var finishPosition = (pageNum * pageLength) + pageLength;
    var numberOfPages = parseInt((parsedJSON.length - 1) / pageLength);

    for (var i = startPosition; (i < finishPosition) && (i < parsedJSON.length); i++) {
        var purchase = parsedJSON[i];
        var dateParts = (purchase.datetime_purchased).split(/-| /);
        var date = dateParts[2] + "/" + dateParts[1] + "/" + dateParts[0];

        $("#archive_tbody")
            .append($('<tr>')
                .attr("id", "row_" + purchase.id)
                .append($('<td>')
                    .append($('<span>')
                        .text(date)
                        .attr("id", "date_value_" + purchase.id)
                )
                    .append($('<input>')
                        .addClass("form-control hidden")
                        .attr("type", "text")
                        .attr("id", "date_input_" + purchase.id)
                )
            )
                .append($('<td>')
                    .append($('<span>')
                        .text(purchase.items)
                        .attr("id", "items_value_" + purchase.id)
                )
                    .append($('<input>')
                        .addClass("form-control hidden")
                        .attr("type", "text")
                        .attr("id", "items_input_" + purchase.id)
                )
            )
                .append($('<td>')
                    .append($('<span>')
                        .text("£" + (parseFloat(purchase.cost).toFixed(2)))
                        .attr("id", "cost_value_" + purchase.id)
                )
                    .append($('<input>')
                        .addClass("form-control hidden")
                        .attr("type", "number")
                        .attr("step", "any")
                        .attr("id", "cost_input_" + purchase.id)
                )
            )
                .append($('<td>')
                    .append($('<span>')
                        .text(purchase.category)
                        .attr("id", "category_value_" + purchase.id)
                )
                    .append($('<select>')
                        .addClass("form-control hidden")
                        .attr("id", "category_input_" + purchase.id)
                )
            )
                .append($('<td>')
                    .append($('<input>')
                        .addClass("btn btn-primary thinBtn notSaveBtn")
                        .attr("type", "button")
                        .attr("value", "Edit")
                        .attr("id", "editBtn_" + purchase.id)
                        .attr("onClick", "editPurchase(" + purchase.id + ")")
                )
                    .append("&nbsp;")
                    .append($('<input>')
                        .addClass("btn btn-danger thinBtn notSaveBtn")
                        .attr("type", "button")
                        .attr("value", "Delete")
                        .attr("id", "deleteBtn_" + purchase.id)
                        .attr("onClick", "deletePurchase(" + purchase.id + ")")
                )
                    .append($('<input>')
                        .addClass("btn btn-success thinBtn hidden")
                        .attr("type", "button")
                        .attr("value", "Save")
                        .attr("id", "saveChangesBtn_" + purchase.id)
                        .attr("onClick", "saveChanges(" + purchase.id + ")")
                )
                    .append($('<div>')
                        .addClass("filler")
                )
            )
        );
        $('#row_' + modifiedRow).addClass("modifiedRow");
    }

    $('#archive_pagination').empty();

    $('#archive_pagination')
        .append($('<li>')
            .addClass((pageNum == 0) ? 'disabled' : '')
            .append($('<a>')
                .attr('href', '#')
                .attr('aria-label', 'Previous')
                .attr('onClick', 'getAllPurchases(undefined, ' + (pageNum - 1) + ', ' + (pageNum == 0) + '); return false;')
                .append($('<span>')
                    .attr('aria-hidden', 'true')
                    .html('&laquo;')
            )
        )
    );

    for (var i = 0; i <= numberOfPages; i++) {
        $('#archive_pagination')
            .append($('<li>')
                .addClass((i == pageNum) ? 'active' : '')
                .append($('<a>')
                    .attr('href', '#')
                    .attr('onClick', 'getAllPurchases(undefined, ' + i + '); return false;')
                    .html(i + 1)
            )
        )
    }

    $('#archive_pagination')
        .append($('<li>')
            .addClass((pageNum == numberOfPages) ? 'disabled' : '')
            .append($('<a>')
                .attr('href', '#')
                .attr('aria-label', 'Next')
                .attr('onClick', 'getAllPurchases(undefined, ' + (pageNum + 1) + ', ' + (pageNum == numberOfPages) + '); return false;')
                .append($('<span>')
                    .attr('aria-hidden', 'true')
                    .html('&raquo;')
            )
        )
    );
}

function saveChanges(purchaseId) {
    $.ajax({
        type: 'POST',
        url:  '/purchase/updatepurchase',
        data: {
            items:      $("#items_input_" + purchaseId).val(),
            cost:       parseFloat($("#cost_input_" + purchaseId).val()),
            date:       convertToAmericanDate($("#date_input_" + purchaseId).val()),
            category:   $("#category_input_" + purchaseId).val(),
            purchaseId: purchaseId
        }
    }).success(function () {
        cache["/purchase/getallpurchases/" + getFilters()] = undefined;
        getAllPurchases(purchaseId);
    });
    return false;
}

function editPurchase(purchaseId) {
    $("#editBtn_" + purchaseId).addClass("hidden");
    $("#deleteBtn_" + purchaseId).addClass("hidden");
    $("#saveChangesBtn_" + purchaseId).removeClass("hidden");

    $("#items_input_" + purchaseId).val($("#items_value_" + purchaseId).text());
    $("#items_input_" + purchaseId).removeClass("hidden");
    $("#items_value_" + purchaseId).addClass("hidden");

    $("#cost_input_" + purchaseId).val(parseFloat(($("#cost_value_" + purchaseId).text()).replace("£", "")));
    $("#cost_input_" + purchaseId).removeClass("hidden");
    $("#cost_value_" + purchaseId).addClass("hidden");

    $("#date_input_" + purchaseId).val($("#date_value_" + purchaseId).text());
    $("#date_input_" + purchaseId).removeClass("hidden");
    $("#date_value_" + purchaseId).addClass("hidden");

    // Displays all possible categories on edit
    $('.filterOpt').each(function () {
        $('#category_input_' + purchaseId)
            .append($("<option></option>")
                .attr("value", $(this).text().trim())
                .text(($(this).text()).trim()));
    });

    $("#category_input_" + purchaseId).val($("#category_value_" + purchaseId).text());
    $("#category_input_" + purchaseId).removeClass("hidden");
    $("#category_value_" + purchaseId).addClass("hidden");

    $(".notSaveBtn").each(function (i, obj) {
        $("#" + obj.id).addClass('hidden');
    })

    $("#date_input_" + purchaseId).datepicker({
        dateFormat: 'dd/mm/yy'
    });
}