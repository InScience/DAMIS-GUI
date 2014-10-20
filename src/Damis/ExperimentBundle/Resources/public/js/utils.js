(function() {
	window.utils = {
		showProgress: function() {
			var spinner = $("#spinner");
			if (spinner.length == 0) {
				spinner = $('<div id="spinner" style="display: none; background-color: rgba(0, 0, 0, 0.6); width:100%; height:100%; position:fixed; top:0; left:0; z-index:9999"/>');
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
							"click": function() {
								$(this).dialog("close");
							}
						},
						{
							"text": gettext("OK"),
							"class": "btn",
							"click": function() {
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
            fileInput.on("change", function () {
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
		titleChanged: function() {
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
			newFileBtn.on("click", function() {
				var newFileForm = container.find(".toggle-section");
				newFileForm.show();
				newFileBtn.hide();
			})
		},

		formatStr: function(str, args) {
			var newStr = str;
			for (var key in args) {
                if(args.hasOwnProperty(key))
				    newStr = newStr.replace(new RegExp("{" + key + "}", "g"), args[key]);
			}
			return newStr;
		},

        // MLP functions
        countChange: function(context){
            //$(context).on('change', 'input#mlp_type_qty', window.utils.percentagesCount);
        },
                
        percentagesCount : function(ev) {
            //var used = 100 - parseFloat($('input#mlp_type_qty').val());
            //$('input#mlp_type_fakedT').val(used);
        }
	}
})();

