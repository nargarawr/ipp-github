$(document).ready(function () {
    var periodManager = new PeriodManager();
    var timerManager = new TimerManager();
    var examManager = new ExamManager(periodManager);
    var dataManager = new DataManager(periodManager);

    $('[data-toggle="tooltip"]').tooltip();

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

var PeriodManager = Class.extend({
    init:                      function () {
        this.revisionPeriodSelect = $('#revisionPeriodSelect');
        this.currentPeriodId = $('#currentPeriod').val();

        // Sets the correct value of the select box
        this.revisionPeriodSelect.find("option[id='pid_" + this.currentPeriodId + "']").attr('selected', 'selected');

        this.setupListeners();
    },
    setupListeners:            function () {
        var _self = this;

        this.revisionPeriodSelect.on('change', function () {
            location.href = '/revision/index/period/' + _self.getSelectedRevisionPeriod();
        });
    },
    getSelectedRevisionPeriod: function () {
        return this.revisionPeriodSelect.find('option:selected').attr('id').substr(4);
    }
});

var TimerManager = Class.extend({
    init:           function () {
        this.stopWatchInterval = 0;
        this.startButton = $('#startBtn');
        this.stopButton = $('#stopBtn');
        this.resetButton = $('#resetBtn');

        this.setupListeners();
    },
    setupListeners: function () {
        var _self = this;
        this.startButton.click(function () {
            _self.startTimer();
        });
        this.stopButton.click(function () {
            _self.stopTimer();
        });
        this.resetButton.click(function () {
            _self.resetTimer();
        });
    },
    startTimer:     function () {
        $('#startBtn').attr("disabled", "disabled");
        $('#stopBtn').removeAttr("disabled");
        this.stopWatchInterval = setInterval(this.timerTick, 1000);
    },
    stopTimer:      function () {
        $('#stopBtn').attr("disabled", "disabled");
        $('#startBtn').removeAttr("disabled");
        clearInterval(this.stopWatchInterval);
    },
    resetTimer:     function () {
        this.stopTimer();
        $('#d_hr').text('00');
        $('#d_min').text('00');
        $('#d_sec').text('00');
    },
    timerTick:      function () {
        var seconds = $('#d_sec');
        var minutes = $('#d_min');
        var hours = $('#d_hr');
        var secs = parseInt(seconds.text()) + 1;
        if (secs == 60) {
            secs = 0;
            var mins = parseInt(minutes.text()) + 1;
            if (mins == 60) {
                mins = 0;
                hours.text(precedeWithZero(parseInt(hours.text()) + 1));
            }
            minutes.text(precedeWithZero(mins));
        }
        seconds.text(precedeWithZero(secs));
    }
});

var ExamManager = Class.extend({
    init:              function (periodManager) {
        this.periodManager = periodManager;

        this.addButton = $('#addExam');
        this.closeInputButton = $('#closeExamBox');
        this.updateButton = $('#updateExam');
        this.deleteButton = $('#deleteExam');

        this.editingExamId = '';

        this.setupListeners();
        this.drawExamTable();
    },
    setupListeners:    function () {
        var _self = this;

        $('#timeInput').timepicker();

        $("#dateInput").datepicker({
            dateFormat: 'dd/mm/yy',
            onSelect:   function (dateText) {
            }
        });

        this.addButton.click(function () {
            _self.addExam();
        });
        this.closeInputButton.click(function () {
            _self.closeExamInputBox();
        });
        this.updateButton.click(function () {
            _self.updateExam();
        });
        this.deleteButton.click(function () {
            _self.deleteExam();
        });
    },
    drawExamTable:     function () {
        var _self = this;

        $.ajax({
            url: '/revision/getexams/period/' + this.periodManager.getSelectedRevisionPeriod()
        }).success(function (response) {
            $('#examsTbody').empty();
            var parsedJSON = JSON.parse(response);
            var headerNames = [];
            for (var i = 0; i < parsedJSON.length; i++) {
                var e = parsedJSON[i];
                headerNames.push({name: e.module, id: e.id});

                var editBtn = $('<span>')
                    .attr('class', 'cPointer r_margin_15 glyphicon glyphicon-pencil glyphicon-20 editBtn')
                    .attr('id', 'editBtn' + e.id);

                var row = $('<tr>').append(
                    $('<td>').attr('id', 'module_' + e.id).text(e.module),
                    $('<td>').attr('id', 'worth_' + e.id).text(e.worth),
                    $('<td>').attr('id', 'location_' + e.id).text(e.location),
                    $('<td>').attr('id', 'date_' + e.id).text(moment(e.date).format('DD/MM/YYYY')),
                    $('<td>').attr('id', 'time_' + e.id).text(e.time),
                    $('<td>').attr('id', 'length_' + e.id).text(e.length),
                    $('<td>').attr('id', 'seat_' + e.id).text(e.seat),
                    $('<td>').append(editBtn)
                );

                $('#examsTbody').append(row);
            }

            // Add edit button listeners
            $('.editBtn').each(function (i, ele) {
                $(ele).click(function () {
                    _self.editExam(ele.id.slice(7))
                });
            });
        });
    },
    addExam:           function () {
        var _self = this;

        var examInput = $('#addExamInput');
        if (examInput.hasClass('hidden')) {
            examInput.removeClass('hidden');
            $('#closeExamBox').removeClass('hidden');
        } else {
            var missing_count = 0;
            var formControls = examInput.find('.form-control');
            formControls.each(function (i, obj) {
                if ($('#' + obj.id).val() === ''
                    || (obj.id === 'moduleInput' && ($('#moduleInput').val()).length > 3)) {
                    missing_count++;
                    $('#' + obj.id + 'Wrapper').addClass('has-error');
                }
            });

            if (missing_count > 0) {
                return;
            } else {
                formControls.each(function (i, obj) {
                    var wrapper = $('#' + obj.id + 'Wrapper');
                    if (wrapper.hasClass('has-error')) {
                        wrapper.removeClass('has-error')
                    }
                });
            }

            var url = '/revision/addexam';
            url += '/module/' + $('#moduleInput').val();
            url += '/worth/' + $('#worthInput').val();
            url += '/location/' + $('#locationInput').val();
            url += '/date/' + moment($('#dateInput').val(), 'DD/MM/YYYY').format('YYYY-MM-DD');
            url += '/time/' + $('#timeInput').val();
            url += '/length/' + $('#lengthInput').val();
            url += '/seat/' + $('#seatInput').val();
            url += '/period/' + this.periodManager.getSelectedRevisionPeriod();

            $.ajax({
                url: url
            }).success(function () {
                _self.drawExamTable();
                _self.closeExamInputBox();
            });
        }
    },
    editExam:          function (examId) {
        $('#addExamInput').removeClass('hidden');
        $('#deleteExam').removeClass('hidden');

        $('#moduleInput').val($('#module_' + examId).text());
        $('#worthInput').val($('#worth_' + examId).text());
        $('#locationInput').val($('#location_' + examId).text());
        $('#dateInput').val($('#date_' + examId).text());
        $('#timeInput').val($('#time_' + examId).text());
        $('#lengthInput').val($('#length_' + examId).text());
        $('#seatInput').val($('#seat_' + examId).text());

        $('#addExam').addClass('hidden');
        $('#updateExam').removeClass('hidden');
        $('#closeExamBox').removeClass('hidden');

        this.editingExamId = examId;
    },
    updateExam:        function () {
        var _self = this;

        var url = '/revision/editexam';
        url += '/examid/' + this.editingExamId;
        url += '/module/' + $('#moduleInput').val();
        url += '/worth/' + $('#worthInput').val();
        url += '/location/' + $('#locationInput').val();
        url += '/date/' + moment($('#dateInput').val(), 'DD/MM/YYYY').format('YYYY-MM-DD');
        url += '/time/' + $('#timeInput').val();
        url += '/length/' + $('#lengthInput').val();
        url += '/seat/' + $('#seatInput').val();

        $.ajax({
            url: url
        }).success(function () {
            _self.drawExamTable();
            _self.closeExamInputBox();
        });
    },
    deleteExam:        function () {
        var _self = this;
        $.ajax({
            type: 'GET',
            url:  '/revision/removeexam',
            data: {
                examid: _self.editingExamId
            }
        }).success(function () {
            _self.drawExamTable();
            _self.closeExamInputBox();
        });
    },
    closeExamInputBox: function () {
        var addExamInput = $('#addExamInput');
        addExamInput.addClass('hidden');
        $('#closeExamBox').addClass('hidden');
        $('#updateExam').addClass('hidden');
        $('#addExam').removeClass('hidden');
        $('#deleteExam').addClass('hidden');
        addExamInput.find('.form-control').each(function (i, obj) {
            $(obj).val('');
        });
    }
});

var DataManager = Class.extend({
    init:              function (periodManager) {
        this.periodManager = periodManager;
        this.progressTable = $('#revision_progress_table');

        this.drawCharts();
    },
    drawCharts:        function () {
        var _self = this;
        var periodId = this.periodManager.getSelectedRevisionPeriod();

        // Start by getting the latest headers
        $.ajax({
            url: '/revision/getexams/period/' + periodId
        }).success(function (response) {
            var parsedJSON = JSON.parse(response);
            var headers = [];
            for (var i = 0; i < parsedJSON.length; i++) {
                headers.push({
                    id:   parsedJSON[i].id,
                    name: parsedJSON[i].module
                });
            }

            // Then get the data for the charts and draw them
            $.ajax({
                url: '/revision/getrevisionentries/periodId/' + periodId
            }).success(function (response) {
                _self.drawRevisionTable(response, _self, headers);
                _self.drawRevisionChart(response, _self, headers);
            });
        });
    },
    drawRevisionTable: function (response, self, headers) {
        var parsedJSON = JSON.parse(response);
        var _self = self;
        _self.progressTable.empty();

        // Add header row first
        var tableHeader = $('<thead>');
        var tableHeaderRow = $('<tr>');
        tableHeaderRow.append($('<th>').text('Exam'));
        for (var l = 0; l < headers.length; l++) {
            var thColumn = $('<th>').text(headers[l].name);
            tableHeaderRow.append(thColumn);
        }
        tableHeader.append(tableHeaderRow);
        _self.progressTable.append(tableHeader);

        var tableBody = $('<tbody>');

        var periodStartDate = moment($('#cpSd').val());
        var periodEndDate = moment($('#cpEd').val()).add(1, 'days');

        for (var tempDate = periodStartDate; tempDate.isBefore(periodEndDate); tempDate.add(1, 'days')) {
            var row = $('<tr>').attr('class','cPointer');
            row.append(
                $('<td>').text(moment(tempDate, 'YYYY-MM-DD').format('DD/MM/YYYY'))
            );

            for (var j = 0; j < headers.length; j++) {
                var column = $('<td>');
                var text = {
                    planned: 0,
                    actual:  0
                };

                var tempDateFormatted = tempDate.format('YYYY-MM-DD');
                if (parsedJSON.hasOwnProperty(tempDateFormatted)) {
                    var objects = parsedJSON[tempDateFormatted];

                    for (var k = 0; k < objects.length; k++) {
                        if (objects[k].examId == headers[j].id) {
                            text.planned = objects[k].planned;
                            text.actual = objects[k].actual;
                        }
                    }
                }

                column.text(text.actual + "/" + text.planned);
                row.append(column);
            }

            // Hidden row for editing later
            var hiddenRow = $('<tr>').attr('class', 'hidden');
            var cols = $('<td>').attr('colspan', headers.length + 1);

            var tableRowEditor = new TableRowEditor();
            cols.append(tableRowEditor.getDOMElements(headers));

            hiddenRow.append(cols);

            var tableRow = new TableRow(
                row,
                hiddenRow,
                tempDate.format('YYYY-MM-DD'),
                _self,
                tableRowEditor
            );

            tableBody.append(row);
            tableBody.append(hiddenRow);
        }

        _self.progressTable.append(tableBody);
    },
    drawRevisionChart: function (response, self, headers) {
        var parsedJSON = JSON.parse(response);
        var _self = self;

        var series = [];
        for (var j = 0; j < headers.length; j++) {
            series.push({
                name: headers[j].name,
                data: []
            });
        }

        var xLabels = [];

        var periodStartDate = moment($('#cpSd').val());
        var periodEndDate = moment($('#cpEd').val()).add(1, 'days');
        for (var tempDate = periodStartDate; tempDate.isBefore(periodEndDate); tempDate.add(1, 'days')) {
            xLabels.push(tempDate.format('DD/MM/YYYY'));

            for (var k = 0; k < headers.length; k++) {
                var tempDateFormatted = tempDate.format('YYYY-MM-DD');
                if (parsedJSON.hasOwnProperty(tempDateFormatted)) {
                    var objects = parsedJSON[tempDateFormatted];
                    for (var h = 0; h < objects.length; h++) {
                        if (objects[h].examId == headers[k].id) {
                            series[k].data.push(parseInt(objects[h].actual));
                        }
                    }
                } else {
                    series[k].data.push(0);
                }
            }
        }

        $('#revision_progress_chart_div').highcharts({
            chart:       {
                type: 'column'
            },
            title:       {
                text: ''
            },
            xAxis:       {
                categories: xLabels
            },
            yAxis:       {
                min:   0,
                title: {
                    text: 'Revision Completed (Hours)'
                }
            },
            legend:      {
                align:           'right',
                x:               -30,
                verticalAlign:   'top',
                y:               25,
                floating:        true,
                backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
                borderColor:     '#CCC',
                borderWidth:     1,
                shadow:          false
            },
            tooltip:     {
                formatter: function () {
                    return '<b>' + this.x + '</b><br/>' +
                        this.series.name + ': ' + this.y + '<br/>' +
                        'Total: ' + this.point.stackTotal;
                }
            },
            plotOptions: {
                column: {
                    stacking:   'normal',
                    dataLabels: {
                        enabled:   true,
                        color:     (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                        style:     {
                            textShadow: '0 0 3px black'
                        },
                        formatter: function () {
                            return (this.y <= 0) ? '' : this.y;
                        }
                    }

                }
            },
            series:      series
        });

        $('#revChart_loading').addClass("hidden");
    }
});

var TableRowEditor = Class.extend({
    init: function(){
        this.container = null;
    },
    getDOMElements: function(exams){
        var container = dom.div({
            class: 'hiddenRowContainer'
        });

        this.container = container;

        var titleRow = dom.row();
        titleRow.append([
            dom.col('xs', 2).text('Exam'),
            dom.col('xs', 5).text('Planned'),
            dom.col('xs', 5).text('Actual')
        ]);
        container.append([titleRow]);

        for (var i = 0; i < exams.length; i++) {
            var row = dom.row();
            var examTitle = dom.col('xs', 2).text(exams[i].name);

            var plannedInputCol = dom.col('xs', 5);
            var plannedInput = $('<input>')
                .attr('type', 'number')
                .attr('class', 'form-control planned revisionInput')
                .attr('value', 0)
                .attr('data-examId', exams[i].id);

            var actualInputCol = dom.col('xs', 5);
            var actualInput = $('<input>')
                .attr('type', 'number')
                .attr('class', 'form-control actual revisionInput')
                .attr('value', 0)
                .attr('data-examId', exams[i].id);

            row.append([
                examTitle,
                plannedInputCol.append(plannedInput),
                actualInputCol.append(actualInput)
            ]);

            container.append(row);
        }

        return container;
    },
    getValues: function() {
        if (this.container == null) {
            return;
        }

        var values = [];
        this.container.find('input[type=number]').each(function (j, obj) {
            var examId = $(obj).attr('data-examId');
            var isPlanning = $(obj).hasClass('planned');
            var value = parseInt($(obj).val());

            if (value == '') {
                value = 0;
            }

            var exists = false;
            for (var i = 0; i < values.length; i++) {
                if (values[i].hasOwnProperty('examId') && values[i].examId == examId) {
                    exists = i;
                }
            }

            if (exists !== false) {
                if (isPlanning) {
                    values[exists].planned = value;
                } else {
                    values[exists].actual = value;
                }
            } else {
                values.push({
                    examId:  examId,
                    actual:  isPlanning ? 0 : value,
                    planned: isPlanning ? value : 0
                });
            }
        });

        return values;
    }
});

var TableRow = Class.extend({
    init: function (row, hiddenRow, rowName, dataManager, tableRowEditor) {
        this.row = row;
        this.hiddenRow = hiddenRow;
        this.rowName = rowName;
        this.dataManager = dataManager;
        this.tableRowEditor = tableRowEditor;

        this.setupListeners();
    },
    setupListeners: function() {
        var _self = this;
        this.row.click(function(){
            if (_self.hiddenRow.hasClass('hidden')) {
                _self.hiddenRow.removeClass('hidden');
            } else {
                _self.hiddenRow.addClass('hidden');
                _self.saveExamEntry();
            }
        })
    },
    saveExamEntry: function() {
        var _self = this;
        var values = this.tableRowEditor.getValues();

        $.ajax({
            type: 'POST',
            url:  '/revision/updateexamentries/',
            data: {
                date: _self.rowName,
                values: values
            }
        }).success(function (response) {
            _self.dataManager.drawCharts();
        });
    }
});
/*


// data manager
function drawBanner(examsSoon) {
    $('#soonBanner').empty();

    if (examsSoon.length == 0) {
        return;
    }

    var div = $('<div>')
        .attr('class', 'alert alert-info')
        .attr('role', 'alert');
    var ol = $('<ul>');
    for (var i = 0; i < examsSoon.length; i++) {
        var e = examsSoon[i];
        ol.append($('<li>')
                .text(e.name + ' is only ' + (e.timeleft).toFixed(0) + ' hours away!')
        );
    }
    div.append(ol);
    $('#soonBanner').append(div);
}



// data manager
function populateProgressTable() {
    $.get("/revision/getrevisionprogress/period/" + getCurrentPeriod(), function (progress_response) {
        $.get("/revision/getrevisionplanning/period/" + getCurrentPeriod(), function (plan_response) {
            $('#revision_progress_tbody').empty();
            var parsedProgress = JSON.parse(progress_response);
            var parsedPlan = JSON.parse(plan_response);

            var progressArray = Array();
            var planArray = Array();

            var moduleCount = 1;
            $('.module_name').each(function (i, obj) {
                moduleCount++;
                progressArray[(obj.id).substr(5)] = Array();
                planArray[(obj.id).substr(5)] = Array();
            });

            for (var i = 0; i < parsedProgress.length; i++) {
                var e = parsedProgress[i];
                progressArray[e.id][e.date] = parseFloat(e.progress);
            }

            for (var i = 0; i < parsedPlan.length; i++) {
                var e = parsedPlan[i];
                planArray[e.id][e.date] = parseFloat(e.plan);
            }

            var start = new Date($('#cpSd').val());
            var end = new Date($('#cpEd').val());

            while (start <= end) {
                var engDate = convertFromDateObj(start, 'en_gb');
                var usDate = convertFromDateObj(start, 'en_us');
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                var isToday = start.getTime() === today.getTime();

                var tr = $('<tr id="row_' + usDate + '" class="cPointer' + (isToday ? ' pbarTop' : '' ) + '">')
                    .append($('<td>')
                        .html('<b>' + engDate + '</b><span id="dateTitle_' + usDate + '"></span>')
                );

                var cDone = 0;
                var cOf = 0;
                var progressTrack = new Array();
                $('.module_name').each(function (i, obj) {
                    var id = (obj.id).substr(5);
                    var done = (progressArray[id][usDate] === undefined
                        ? 0
                        : progressArray[id][usDate]);
                    cDone += done;
                    var of = (planArray[id][usDate] === undefined
                        ? 0
                        : planArray[id][usDate]);
                    cOf += of;
                    tr.append($('<td>')
                            .append($('<span>')
                                .text(done + ' / ')
                        )
                            .append($('<span>')
                                .attr('id', id + "_" + usDate)
                                .text(of)
                        )
                    );

                    // Only count up to max for that day
                    if (done > of) {
                        done = of;
                    }
                    progressTrack[id] = {
                        done: done,
                        of:   of
                    };
                });

                tr.append($('<td class="b_padding_0">')
                        .append($('<span id="editRow_' + usDate + '" class="hidden glyphicon glyphicon-pencil glyphicon-20">'))
                );

                var percentComplete = 0;
                var done = 0;
                var of = 0;

                progressTrack.forEach(function (element, index, array) {
                    done += element.done;
                    of += element.of;
                });
                percentComplete = (100 * (done / of)).toFixed(0);

                if (percentComplete == 100) {
                    tr.addClass('success');
                } else if (percentComplete >= 50) {
                    tr.addClass('warning');
                } else {
                    if (start.getTime() < today.getTime()) { // day has passed
                        tr.addClass('danger');
                    }
                }

                $('#revision_progress_tbody').append(tr);

                if (isToday) {
                    // Add progress bar to next row
                    if (isNaN(percentComplete) || percentComplete === undefined) {
                        percentComplete = 100;
                    }
                    var progressBar = $('<tr class="pbar">')
                        .append($('<td colspan="' + (moduleCount + 1) + '">')
                            .html('<div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="'
                            + percentComplete
                            + '" aria-valuemin="0" aria-valuemax="100" style="width:'
                            + percentComplete + '%;">' + percentComplete + '%</div></div>')
                    );
                    $('#revision_progress_tbody').append(progressBar);
                }

                var newDate = start.setDate(start.getDate() + 1);
                start = new Date(newDate);
            }


        });
    }).success(function (response) {
    });
}*/
