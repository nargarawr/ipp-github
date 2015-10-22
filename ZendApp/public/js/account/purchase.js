$(document).ready(function () {
    $('#updatePurchase').bind("click", function(){
        $.ajax({
            type: 'POST',
            url:  '/account/updatepurchasesettings',
            data: {
                salary:  $('#in_salary').val() === "" ? 0 : $('#in_salary').val(),
                postTax: $('#in_posttax').val() === "" ? 0 : $('#in_posttax').val(),
                bills:   $('#in_bills').val() === "" ? 0 : $('#in_bills').val(),
                yearLength: $('#in_yearl').val() === "" ? 53 : $('#in_yearl').val()
            }
        }).success(function () {
            location.reload();
        });
        return false;
    });

    $('.newCategoryForm').each(function() {
        var newCatForm = new addNewCategoryForm(this);
    });

    $('.customCategoryRow').each(function() {
        var customCatRow = new customCategoryRow(this);
    });
});

var customCategoryRow = Class.extend({
    init: function(row) {
        this.row = $(row);
        this.categoryId = this.row.attr('id').slice(4);
        this.deleteButton = this.row.find(".glyphicon-remove");
        this.editButton = this.row.find(".glyphicon-pencil");
        this.confirmEditButton = this.row.find(".glyphicon-ok");
        this.iconContainer = this.row.find(".iconContainer");
        this.nameContainer = this.row.find(".nameContainer");
        this.editNameContainer = this.row.find(".editNameContainer");
        this.editCatNameInput = this.row.find(".editCatNameInput");
        this.editCatSubmitContainer = this.row.find(".editCatSubmitContainer");

        this.setupListeners();
    },
    setupListeners: function() {
        var _self = this;
        this.deleteButton.click(function() {
            $.ajax({
                type: 'POST',
                url: '/account/setcustomcategoryactive/',
                data: {
                    categoryId: _self.categoryId,
                    active: 0
                }
            }).success(function () {
                location.reload();
            });
        });

        this.editButton.click(function() {
            _self.showEditForm(true);
        });

        this.confirmEditButton.click(function() {
            $.ajax({
                type: 'POST',
                url: '/account/updatecustomcategoryname/',
                data: {
                    categoryId: _self.categoryId,
                    name: _self.editCatNameInput.val()
                }
            });
            location.reload();
        });
    },
    showEditForm: function(show){
        if (show) {
            this.editNameContainer.removeClass("hidden");
            this.editCatSubmitContainer.removeClass("hidden");

            this.iconContainer.addClass("hidden");
            this.nameContainer.addClass("hidden");
        } else {
            this.editNameContainer.addClass("hidden");
            this.editCatSubmitContainer.addClass("hidden");

            this.iconContainer.removeClass("hidden");
            this.nameContainer.removeClass("hidden");
        }
    }
});

var addNewCategoryForm = Class.extend({
    init: function(div) {
        this.div = $(div);

        this.showFormControls = this.div.find(".showFormControls");
        this.formControls = this.div.find(".formControls");

        this.showFormButton = this.div.find(".showForm");
        this.hideFormButton = this.div.find(".hideForm");
        this.submitFormButton = this.div.find(".submitForm");

        this.setupListeners();
    },
    setupListeners: function() {
        var _self = this;
        this.showFormButton.click(function() {
            _self.showFormControls.addClass('hidden');
            _self.formControls.removeClass('hidden');
        });

        this.hideFormButton.click(function() {
            _self.formControls.addClass('hidden');
            _self.showFormControls.removeClass('hidden');
        });

        this.submitFormButton.click(function() {
            var text = _self.formControls.find("input[type=text]").val();
            if (!text) {
                return;
            }

            $.ajax({
                type: 'post',
                url: '/purchase/addcategory/',
                data: {
                    category: text
                }
            }).done(function () {
                window.location.href = '/account/purchase';
            });

        });
    }
});