var g_modalDeleteId = null;
var g_modalDeleteType = null;
var g_modalModuleId = null;

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

    $('#newModuleSubmitButton').click(function () {
        $.ajax({
            url:  "/grades/addnewmodule",
            type: 'get',
            data: {
                code:     $('#inputModuleCode').val(),
                name:     $('#inputModuleName').val(),
                year:     $('#inputModuleYear').val(),
                credits:  $('#inputModuleCredits').val(),
                semester: $('#inputModuleSemester').val()
            }
        }).success(function () {
            location.reload();
        });
    });

    $('#newAssessmentSubmitButton').click(function() {
        $.ajax({
            url:  '/grades/addassessment',
            type: 'POST',
            data: {
                name:     $('#inputAssessmentName').val(),
                weight:   $('#inputAssessmentWeight').val(),
                moduleId: g_modalModuleId,
                mark:     $('#inputAssessmentMark').val()
            }
        }).success(function () {
        });
        $('#inputAssessmentName').val('');
        $('#inputAssessmentWeight').val('');
        $('#inputAssessmentMark').val('');
        getModuleTableFromModuleId(g_modalModuleId).find('.refreshReminder').removeClass('hidden');
        $('#addAssessmentModal').magnificPopup('close');
    });

    $('.assAddBtn').each(function(i, obj){
        $(obj).click(function() {
            launchAddAssessmentModal($(this).attr('data-mid'))
        });
    });

    $('.moduleContainer').each(function () {
        var mc = new moduleContainer($(this));
    });

    if ($('#deletionModal').length > 0) {
        var dmm = new deleteContentModal($('#deletionModal'));
    }

    if ($('#moduleResultsHistogramContainer').length > 0) {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('id') == 'summaryTabNav') {
                drawModuleResultsHistogram();
            }
        });
    }

    $(window).resize(function () {
        if ($('#moduleResultsHistogramContainer').length > 0) {
            drawModuleResultsHistogram();
        }
    });

});

function drawModuleResultsHistogram () {
    cacheAndCallAjax(
        "/grades/getmodulegrades",
        function (response) {
            var data = JSON.parse(response);
            var buckets = [
                {min: 0, max: 9, count: 0},
                {min: 10, max: 19, count: 0},
                {min: 20, max: 29, count: 0},
                {min: 30, max: 39, count: 0},
                {min: 40, max: 49, count: 0},
                {min: 50, max: 59, count: 0},
                {min: 60, max: 69, count: 0},
                {min: 70, max: 79, count: 0},
                {min: 80, max: 89, count: 0},
                {min: 90, max: 100, count: 0}
            ];

            for (var i = 0; i < data.length; i++) {
                for (var j = 0; j < buckets.length; j++) {
                    if (data[i].grade >= buckets[j].min && (data[i].grade <= buckets[j].max)) {
                        buckets[j].count += 1;
                    }
                }
            }

            var values = [];
            var categories = [];

            for (var k = 0; k < buckets.length; k++) {
                values.push(buckets[k].count);
                categories.push(buckets[k].min + "-" + buckets[k].max);
            }

            $('#moduleResultsHistogramContainer').highcharts({
                chart: {
                    type: 'column'
                },
                title: '',
                xAxis: {
                    categories: categories
                },
                yAxis: {
                    title: {
                        text: ''
                    }
                },
                plotOptions: {
                    column: {
                        groupPadding: 0,
                        pointPadding: 0,
                        borderWidth:  0
                    }
                },
                series: [{
                    name: 'Module Count',
                    data: values
                }]
            });

            $('#mrh_loading').addClass('hidden');
            return;
        },
        null
    );
}

function launchDeletionModal(id, type) {
    g_modalDeleteId = id;
    g_modalDeleteType = type;

    var el = $('#deletionModal');
    $.magnificPopup.open({
        items:           {
            src: el
        },
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
}

function launchAddAssessmentModal(id) {
    g_modalModuleId = id;

    var el = $('#addAssessmentModal');
    $.magnificPopup.open({
        items:           {
            src: el
        },
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
}

function getModuleTableFromAssessmentId(id) {
    var table;
    $('.tab-pane.active .moduleTable').each(function() {
        var localTable = $(this);
        $(this).find('.assessmentRow').each(function(){
            if ($(this).find('.assessmentId').val() == id) {
                table = localTable;
            }
        });
    });
    return table;
}

function getModuleTableFromModuleId(id) {
    var returnVal = null;
    $('.moduleContainer').each(function(){
        if (parseInt(id) == parseInt($(this).find('.moduleId').val())){
            returnVal = $(this);
        }
    });
    return returnVal;
}

var moduleContainer = Class.extend({
    init:                function (panel) {
        var _self = this;
        this.panel = panel;
        this.moduleId = this.panel.find('.moduleId').val();
        this.panelTitle = this.panel.find('.panel-heading');
        this.moduleModificationButtons = this.panel.find('.moduleModificationButtons');
        this.moduleShowDetailsButtons = this.panel.find('.moduleShowDetails');

        this.moduleDeleteButton = this.moduleModificationButtons.find('.glyphicon-remove');
        this.moduleEditButton = this.moduleModificationButtons.find('.glyphicon-pencil');
        this.moduleSubmitButton = this.panelTitle.find('.moduleSubmitButton');
        this.showModuleDetails = this.moduleShowDetailsButtons.find('.glyphicon-collapse-down');
        this.hideModuleDetails = this.moduleShowDetailsButtons.find('.glyphicon-collapse-up');

        this.isShowingEditForm = false;
        this.isShowingModuleDetails = false;

        this.assessments = [];
        this.tableRows = this.panel.find('.table tbody').find('.assessmentRow');
        [].forEach.call(this.tableRows, function (htmlObject, i, jqueryObject) {
            _self.assessments.push(new assessmentContainer($(htmlObject)));
        });
        this.setupListeners();
    },
    setupListeners:      function () {
        var _self = this;
        this.panelTitle.mouseover(function () {
            if (_self.isShowingEditForm) {
                return;
            }
            _self.moduleModificationButtons.removeClass('hidden');
            _self.moduleShowDetailsButtons.removeClass('hidden');
        });

        this.panelTitle.mouseout(function () {
            if (_self.isShowingEditForm) {
                return;
            }
            _self.moduleModificationButtons.addClass('hidden');

            if (_self.isShowingModuleDetails) {
                return;
            }
            _self.moduleShowDetailsButtons.addClass('hidden');
        });

        this.moduleDeleteButton.click(function () {
            launchDeletionModal(_self.moduleId, 'm');
        });

        this.moduleEditButton.click(function () {
            _self.showHideMoreDetails(true);
            _self.showHideEditForm();
        });

        this.moduleSubmitButton.click(function () {
            _self.submitEditForm();
        });

        this.showModuleDetails.click(function () {
            _self.showHideMoreDetails(true);
        });

        this.hideModuleDetails.click(function () {
            _self.showHideMoreDetails(false);
        });
    },
    submitEditForm:      function () {
        this.assessments.forEach(function(a){
            a.submitModifications();
        });

        // Collect all new values
        var newModuleCode = this.panel.find(".moduleCode input[type=text]").val();
        var newModuleName = this.panel.find(".moduleName input[type=text]").val();
        var newModuleCredits = this.panel.find(".moduleCredits input[type=number]").val();
        var newModuleYear = this.panel.find(".moduleYear select").val();
        var newModuleSemesterId = this.panel.find(".moduleSemester select").val();

        // Check if year or semester change; if so, we'll need to reload the page later
        var newModuleSemesterName = this.panel.find(".moduleSemester select option:selected").text().trim();
        var semesterHasChanged = newModuleSemesterName != this.panel.find(".moduleSemester .text .displayTag").text().trim();

        var yearHasChanged = newModuleYear != this.panel.find(".moduleYear .text .displayTag").text().trim().slice(0, -2);
        var shouldRefresh = semesterHasChanged || yearHasChanged;

        // Set all display boxes to reflect new value (if we're not refreshing)
        if (!shouldRefresh) {
            this.panel.find(".moduleCode .displayTag").html(newModuleCode);
            this.panel.find(".moduleName .displayTag").html(newModuleName);
            this.panel.find(".moduleCredits .displayTag").html(newModuleCredits);
        }

        // Actually update the values in the database
        $.ajax({
            type: 'POST',
            url:  '/grades/updatemodule',
            data: {
                moduleId: this.moduleId,
                code:     newModuleCode,
                name:     newModuleName,
                year:     newModuleYear,
                credits:  newModuleCredits,
                semester: newModuleSemesterId
            }
        }).success(function (response) {
            if (shouldRefresh) {
                location.reload()
            }
        });
        if (!shouldRefresh) {
            this.showHideEditForm();
        }
    },
    showHideMoreDetails: function (shouldShow) {
        if (shouldShow) {
            this.panel.find('.moduleDetails').slideDown();
            this.showModuleDetails.addClass('hidden');
            this.hideModuleDetails.removeClass('hidden');
        } else {
            this.panel.find('.moduleDetails').slideUp();
            this.showModuleDetails.removeClass('hidden');
            this.hideModuleDetails.addClass('hidden');
        }
        this.isShowingModuleDetails = shouldShow;
    },
    showHideEditForm:    function () {
        var addToDisplayTags = this.isShowingEditForm ? '' : 'hidden';
        var addToEditTags = this.isShowingEditForm ? 'hidden' : '';

        this.panel.find('.displayTag').each(function () {
            $(this).addClass(addToDisplayTags);
            $(this).removeClass(addToEditTags);
        });
        this.panel.find('.editTag').each(function () {
            $(this).addClass(addToEditTags);
            $(this).removeClass(addToDisplayTags);
        });

        this.isShowingEditForm = !(this.isShowingEditForm);
    }
});

var assessmentContainer = Class.extend({
    init:                function (row) {
        this.row = row;
        this.assessmentId = this.row.find('.assessmentId').val();
        this.deleteButton = this.row.find('.glyphicon-remove');

        this.setupListeners();
    },
    setupListeners:      function () {
        var _self = this;

        this.deleteButton.click(function () {
            launchDeletionModal(_self.assessmentId, 'a');
        });
    },
    submitModifications: function () {
        // Get new values
        var newName = this.row.find('.assessmentName').val();
        var newWeight = this.row.find('.assessmentWeight').val();
        var newGrade = this.row.find('.assessmentGrade').val();

        // Get original values
        var originalName = this.row.find('.assessmentNameValue').html().trim();
        var originalWeight = this.row.find('.assessmentWeightValue').html().trim();
        var originalGrade = this.row.find('.assessmentGradeValue').html().trim();

        // Check any changes have been made, otherwise stop processing
        var changes = (newName != originalName)
            || (newWeight != originalWeight)
            || (newGrade != originalGrade);
        if (!changes) {
            return;
        }

        // Update original values
        this.row.find('.assessmentNameValue').html(newName);
        this.row.find('.assessmentWeightValue').html(newWeight);
        this.row.find('.assessmentGradeValue').html(newGrade);

        // Make ajax call to update database
        $.ajax({
            type: 'POST',
            url:  '/grades/editassessment',
            data: {
                id:     this.assessmentId,
                name:   newName,
                weight: newWeight,
                grade:  newGrade
            }
        }).success(function () {
        });

        getModuleTableFromAssessmentId(this.assessmentId).find('.refreshReminder').removeClass('hidden');
    }
});

var deleteContentModal = Class.extend({
    init:           function (modal) {
        this.modal = modal;
        this.cancelButton = this.modal.find('.btn-primary');
        this.deleteButton = this.modal.find('.btn-danger');

        this.setupListeners();
    },
    setupListeners: function () {
        this.deleteButton.click(function () {
            var url = '';

            if (g_modalDeleteType == 'm') {
                url = '/grades/setmoduleactive'
            } else if (g_modalDeleteType == 'a') {
                url = '/grades/setassessmentactive'
            }

            $.ajax({
                type: 'POST',
                url:  url,
                data: {
                    id: g_modalDeleteId,
                    isActive: 0
                }
            }).success(function () {
                if (g_modalDeleteType == 'm') {
                    location.reload();
                } else if (g_modalDeleteType == 'a') {
                    var elementToRemove = null;
                    $('.tab-pane.active .assessmentRow').each(function() {
                        if ($(this).find('.assessmentId').val() == g_modalDeleteId) {
                            elementToRemove = $(this);
                        }
                    });
                    getModuleTableFromAssessmentId(g_modalDeleteId).find('.refreshReminder').removeClass('hidden');
                    elementToRemove.remove();
                    $('#deletionModal').magnificPopup('close');
                }
            });
        });

        this.cancelButton.click(function () {
            $('#deletionModal').magnificPopup('close');
        });
    }
});
