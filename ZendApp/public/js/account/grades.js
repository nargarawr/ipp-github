$(document).ready(function () {
    $("body").tooltip({selector: '[data-toggle=tooltip]'});

    $('#updateGrades').bind("click", updateGradesDetails);

    $('.gradesFTU').each(function () {
        var gtf = new gradesFirstTimeFlow($(this));
    });
});

var gradesFirstTimeFlow = Class.extend({
    init:           function (row) {
        this.row = row;
        this.openPartTwo = $('#goToPart2');
        this.openPartThree = $('#goToPart3');
        this.submitFormButton = $('#submitForm');

        this.partTwo = this.row.find('.part2');
        this.partThree = this.row.find('.part3');

        this.courseLength = 0;

        this.setupListeners();
    },
    setupListeners: function () {
        var _self = this;

        this.openPartTwo.click(function () {
            var keepGoing = _self.checkInputs(2);
            if (!keepGoing) {
                return;
            }

            $('#ftuCourseLength').attr('disabled', true);

            var defaultTabSelect = $('#ftuDefaultTab');
            var options = [];
            for (var i = 1; i <= _self.courseLength; i++) {
                options.push({
                    val:  i,
                    text: 'Year ' + i
                });
            }

            $(options).each(function () {
                defaultTabSelect.append(
                    $("<option>")
                        .attr('value', this.val)
                        .text(this.text)
                );
            });

            _self.openPartThree.removeClass('hidden');
            _self.partTwo.removeClass('hidden');
            $(this).addClass('hidden');
        });
        this.openPartThree.click(function () {
            var keepGoing = _self.checkInputs(2);
            if (!keepGoing) {
                return;
            }

            var div = $('#yearWorthEntry');
            for (var i = 1; i <= _self.courseLength; i++) {
                var span = $("<span>")
                    .attr('class', 'input-group-addon')
                    .attr('id', 'yr_prefix_' + i)
                    .text("Year " + i);

                var input = $("<input>")
                    .attr('class', 'form-control yearWeight')
                    .attr('type', 'number')
                    .attr('area-describedby', 'yr_prefix_' + i)
                    .attr('data-year', i)
                    .attr('value', '0');

                var inputGroupDiv = $("<div>")
                    .attr('class', 'input-group')
                    .append(span, input);

                var colDiv = $("<div>")
                    .attr('class', 'col-md-6')
                    .append(inputGroupDiv);

                div.append(colDiv);
            }

            _self.submitFormButton.removeClass('hidden');
            _self.partThree.removeClass('hidden');
            $(this).addClass('hidden');
        });
        this.submitFormButton.click(function () {
            var keepGoing = _self.checkInputs(3);
            if (!keepGoing) {
                return;
            }

            var courseLength = _self.courseLength;
            var defaultTab = $('#ftuDefaultTab').val();
            var yearWeights = [];

            $('.yearWeight').each(function () {
                yearWeights.push({
                    year:   $(this).attr('data-year'),
                    weight: $(this).val()
                });
            });

            $.ajax({
                url:  '/account/setupgradesapp',
                type: 'POST',
                data: {
                    courseLength: courseLength,
                    defaultTab:   defaultTab,
                    yearWeights:  yearWeights
                }
            }).success(function () {
                window.location = "/account/grades";
            });
        });
    },
    checkInputs:    function (stage) {
        if (stage == 3) {
            var allGood = true;
            $('.yearWeight').each(function () {
                if ($(this).val() == '' || $(this).val() < 0 || $(this).val() > 100) {
                    allGood = false;
                    $(this).addClass('redBorder');
                } else {
                    $(this).removeClass('redBorder');
                }

            });
            if (!allGood) {
                return false;
            }
        }

        var courseLength = $('#ftuCourseLength').val();
        if (courseLength < 0 || courseLength == '') {
            $('#ftuCourseLength').addClass('redBorder');
            return false;
        } else {
            this.courseLength = courseLength;
            $('#ftuCourseLength').removeClass('redBorder');
        }

        return true;
    }
});