(function() {
	window.utils = {
		showProgress: function() {
			var spinner = $("#spinner");
			if (spinner.length == 0) {
				var spinner = $('<div id="spinner" style="display: none; background-color: rgba(0, 0, 0, 0.6); width:100%; height:100%; position:fixed; top:0px; left:0px; z-index:9999"/>');
				$("body").append(spinner);
				setTimeout(function() {
					if (spinner.length > 0) {
						spinner.show();
						spinner.spin("modal");
					}
				},
				2000);
			}
		},
		hideProgress: function() {
			var spinner = $("#spinner");
			spinner.spin(false);
			spinner.remove();
		},
		initDeleteConfirm: function(formSelector) {
			$(".delete-btn").click(function() {
				window.utils.openDeleteConfirm(formSelector);
			});
		},
		openDeleteConfirm: function(formSelector) {
			if ($(formSelector + " input[type=checkbox]").is(":checked")) {
				var dialog = $(".delete-confirm");
				if (!dialog.hasClass("ui-dialog-content")) {
					dialog.dialog({
						"modal": true,
						"autoopen": false,
						"appendTo": "body",
						"buttons": [{
							"text": gettext("Cancel"),
							"class": "btn btn-primary",
							"click": function(ev) {
								$(this).dialog("close");
							}
						},
						{
							"text": gettext("OK"),
							"class": "btn",
							"click": function(ev) {
								$(this).dialog("close");
								$(formSelector).submit();
							}
						}],
						"open": function() {
							var dialog = $(this).closest(".ui-dialog");
							dialog.find(".ui-dialog-titlebar > button").remove();
						}
					});
				} else {
					dialog.dialog("open");
				}
			}
		},

		// initilializes elements of file input form
		customizeFileForm: function(container) {
            var fileInput = $("input#datasets_newtype_file");
            var titleInput = $("input[name='datasets_newtype[datasetTitle]']");
            titleInput.on("change", window.utils.titleChanged);
            fileInput.on("change", function (ev) {
                var fileName = $(this).val();
                // put the uploaded file name next to the upload button
                var stdFileName = fileName.replace(/\\/g, "/");
                var start = stdFileName.lastIndexOf("/") + 1;

                // prefill title input field with the uploaded file name
                // if the input had not been changed by the user
                if (!titleInput.hasClass("changed")) {
                    var baseName = fileName.substring(start, stdFileName.lastIndexOf("."));
                    titleInput.off("change");
                    titleInput.val(baseName);
                    titleInput.on("change", window.utils.titleChanged);
                }
            });

			// toggle file form visibility on click of a button
			window.utils.initToggleSectionBtn(container);
		},

		// adds a flag that the input was changed by the user
		titleChanged: function(ev) {
			if ($(this).val()) {
				$(this).addClass("changed");
			} else {
				$(this).removeClass("changed");
			}
		},

		// initializes a button (.toggle-btn) that toggles a section (.toggle-section) 
		// visibility inside a container
		initToggleSectionBtn: function(container) {
			// toggle file form visibility on click of a button
			var newFileBtn = container.find(".toggle-btn");
			newFileBtn.on("click", function(ev) {
				var newFileForm = container.find(".toggle-section");
				newFileForm.show();
				newFileBtn.hide();
			})
		},

		formatStr: function(str, args) {
			var newStr = str;
			for (var key in args) {
				newStr = newStr.replace(new RegExp("{" + key + "}", "g"), args[key]);
			}
			return newStr;
		},

        countChange: function(context){
            $(context).on('change', 'input#mlp_type_trainingData', window.utils.percentagesCount);
            $(context).on('change', 'input#mlp_type_testData', window.utils.percentagesCount);
        },

        percentagesCount : function(ev) {
            var used = 100 - parseFloat($('input#mlp_type_trainingData').val())
                - parseFloat($('input#mlp_type_testData').val());
            $('input#mlp_type_validationData').val(used);
        }
	}
})();

