$(document).ready(function () {
    $("body").tooltip({selector: '[data-toggle=tooltip]'});
    var dataManager = new DataManager();

    $('#purchasePeriodSelector').change(function () {
        dataManager.drawCharts();
        $('.periodTitle').each(function (i, obj) {
            $(obj).html($('#purchasePeriodSelector').val());
        });
    });
});

var DataManager = Class.extend({
    init:                function () {
        this.chartTypes = [
            'spwlc',
            'cbcpc',
            'bbcac',
            'spwtb'
        ];
        this.drawCharts();
    },
    drawCharts:          function (chartsToDraw) {
        /*
         * Draws (or redraws) some or all of the charts on the page, based on the contents of the array given.
         * Possible values of the array are those in this.chartTypes - will redraw all if blank
         */
        var _self = this;

        // Parameter omitted or empty, redraw all
        if (chartsToDraw === undefined || chartsToDraw.length == 0) {
            chartsToDraw = this.chartTypes;
        }

        // make this smarter (i.e, group ajax calls, and only draw certain graphs)
        if (chartsToDraw.indexOf('spwlc') > -1) {
            cacheAndCallAjax(
                "/purchase/getspendingsperperiod/period/" + ($('#purchasePeriodSelector').val()).toLowerCase(),
                this.processSpwLineChart,
                {self: this}
            );
        }

        // Extra stuff here to avoid making the Ajax call twice
        var containsCbcPieChart = chartsToDraw.indexOf('cbcpc') > -1;
        var containsBbcAreaChart = chartsToDraw.indexOf('bbcac') > -1;
        if (containsCbcPieChart || containsBbcAreaChart) {
            cacheAndCallAjax(
                "/purchase/getspendbycategory",
                function (response) {
                    if (containsCbcPieChart) {
                        _self.processCbcPieChart(response);
                    }
                    if (containsBbcAreaChart) {
                        _self.processBbcAreaChart(response);
                    }
                },
                null
            );
        }

        if (chartsToDraw.indexOf('spwtb') > -1) {
            this.drawSpwTable(0);
        }
    },
    drawSpwTable:        function (pageNum) {
        var _self = this;
        cacheAndCallAjax(
            "/purchase/getspendingsperperiod/period/" + ($('#purchasePeriodSelector').val()).toLowerCase(),
            function (response) {
                _self.processSpwTable(response, pageNum);
            }
        );
    },
    processSpwTable:     function (response, pageNum) {
        var _self = this;
        var costTable = $("#costPerWeekTable");
        var paginationContainer = $("#spw_pagination");

        paginationContainer.empty();
        costTable.empty();

        var pageLength = 15;
        if (response === "" || response == "[]") {
            $('#spwT_loading').addClass("hidden");
            $('#ftuBanner').removeClass("hidden");
            $('#index_content').addClass('hidden');
            return;
        }
        var parsedJSON = JSON.parse(response);
        var totalLength = parsedJSON.length;

        var startPosition = totalLength - (pageNum * pageLength) - 1;
        var finishPosition = totalLength - ((pageNum + 1) * pageLength) - 1;

        for (var i = startPosition; i >= 0 && i > finishPosition; i--) {
            $("#costPerWeekTable")
                .append($('<tr>')
                    .attr('class', 'redirectingTableRow')
                    .append($('<td>')
                        .append($('<span>')
                            .text(this.handleDates(parsedJSON[i].beginning, $('#purchasePeriodSelector').val()))
                    )
                )
                    .append($('<td>')
                        .append($('<span>')
                            .text("£" + parseFloat(parsedJSON[i].cost).toFixed(2))
                    )
                )
            );
        }

        $('.redirectingTableRow').each(function (i, rtr) {
            $(rtr).click(function () {
                _self.redirectToArchive(parsedJSON[(parsedJSON.length - 1 - i)].beginning);
            });
        });

        var tableContainer = $('#table_container');
        if (tableContainer.css('min-height') === '0px') {
            tableContainer.css('min-height', tableContainer.height() - 32)
        }

        paginationContainer
            .append($('<li>')
                .addClass((pageNum == 0) ? 'disabled' : '')
                .append($('<a>')
                    .attr('href', '#')
                    .attr('aria-label', 'Previous')
                    .append($('<span>')
                        .attr('aria-hidden', 'true')
                        .html('&laquo;')
                )
            ).click(function () {
                    if (pageNum != 0) {
                        _self.drawSpwTable(pageNum - 1);
                    }
                })
        );
        var numPages = parseInt(totalLength / pageLength);
        if (totalLength % pageLength == 0) {
            numPages--;
        }

        for (var j = 0; j <= numPages; j++) {
            $('#spw_pagination')
                .append($('<li>')
                    .attr('class', 'li-paginate')
                    .addClass((j == pageNum) ? 'active' : '')
                    .append($('<a>')
                        .attr('href', '#')
                        .html(j + 1)
                )
            )
        }
        $('.li-paginate').each(function (i, li) {
            $(li).click(function () {
                _self.drawSpwTable(i);
            });
        });

        paginationContainer
            .append($('<li>')
                .addClass((pageNum == numPages) ? 'disabled' : '')
                .append($('<a>')
                    .attr('href', '#')
                    .attr('aria-label', 'Next')
                    .append($('<span>')
                        .attr('aria-hidden', 'true')
                        .html('&raquo;')
                )
            ).click(function () {
                    if (pageNum != numPages) {
                        _self.drawSpwTable(pageNum + 1);
                    }
                })
        );
        $('#spwT_loading').addClass("hidden");
        return false;
    },
    processSpwLineChart: function (response, params) {
        var _self = params.self;
        var data = JSON.parse(response);

        var xLabels = [];
        var spendPerWeekData = [];
        var movingAverageData = [];
        for (var i = 0; i < data.length; i++) {
            xLabels.push(_self.handleDates(data[i].beginning, $('#purchasePeriodSelector').val()));
            spendPerWeekData.push(parseFloat(data[i].cost));
            movingAverageData.push(parseFloat(data[i].movingAverage));
        }

        $('#perWeek_chart_div').highcharts({
            title: {
                text: ''
            },
            xAxis: {
                categories: xLabels
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Spend (£)'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                pointFormat: '{series.name}: <b>£{point.y:.2f}</b>'
            },
            legend: {
                enabled: false
            },
            series: [{
                name: 'Spend Per Week',
                data: spendPerWeekData
            }, {
                name: 'Moving Average',
                data: movingAverageData
            }]
        });

        $('#spw_loading').addClass("hidden");
    },
    processCbcPieChart:  function (response) {
        var data = JSON.parse(response);

        var categoriesAndValue = [];
        for (var i = 0; i < data.length; i++) {
            categoriesAndValue.push([
                data[i].category,
                parseFloat(data[i].totalSpend)
            ]);
        }

        $('#category_chart_div').highcharts({
            chart: {
                type: 'pie',
                options3d: {
                    enabled: true,
                    alpha: 45,
                    beta: 0
                }
            },
            title: {
                text: ''
            },
            tooltip: {
                pointFormat: '{series.name}: <b>£{point.y:.2f} ({point.percentage:.2f}%)</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    depth: 35,
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}'
                    }
                }
            },
            series: [{
                type: 'pie',
                name: 'Total Spend',
                data: categoriesAndValue
            }]
        });

        $('#costbycat_loading').addClass("hidden");
    },
    processBbcAreaChart: function (response) {
        var data = JSON.parse(response);

        var xLabels = [];
        var averageWeekData = [];
        var thisWeekData = [];
        for (var i = 0; i < data.length; i++) {
            xLabels.push(data[i].category);
            averageWeekData.push(parseFloat(data[i].averageSpend));
            thisWeekData.push(parseFloat(data[i].spendSoFar));
        }

        $('#category_per_time_chart_div').highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: xLabels,
                crosshair: true
            },
            yAxis: {
                title: {
                    text: 'Spend (£)'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>£{point.y:.1f}</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
            series: [{
                name: 'This Week',
                data: thisWeekData

            }, {
                name: 'Average Week',
                data: averageWeekData

            }]
        });


        $('#catBd_loading').addClass("hidden");
    },
    redirectToArchive:   function (startDate) {
        var period = $('#purchasePeriodSelector').val().toLowerCase();
        var start;
        var end;

        if (period === 'year') {
            window.location.href = "/purchase/archive/startdate/" + startDate + "-01-01/enddate/" + startDate + "-12-31";
            return;
        }

        var parts = startDate.split("-");
        if (period === 'month') {
            start = moment(parts[1] + "-" + parts[0] + "-" + '01', 'YYYY-MM-DD');
            end = moment(parts[1] + "-" + parts[0] + "-" + '01', 'YYYY-MM-DD').endOf('month');
        } else if (period === 'week') {
            start = moment(parts[0] + "-" + parts[1] + "-" + parts[2], 'YYYY-MM-DD');
            end = moment(parts[0] + "-" + parts[1] + "-" + parts[2], 'YYYY-MM-DD').endOf('week');
        }
        window.location.href = "/purchase/archive/startdate/" + start.format('YYYY-MM-DD') + "/enddate/" + end.format('YYYY-MM-DD');
    },
    handleDates:         function (date, period) {
        if (period === 'Week') {
            return moment(date).format('DD/MM/YYYY');
        } else if (period === 'Month') {
            var parts = date.split('-');
            return precedeWithZero(parts[0]) + '/' + parts[1];
        }
        return date;
    }
});