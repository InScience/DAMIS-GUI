(function() {
	window.files = {
        taskBoxId : null,
        filePath : null,

		init: function(componentType, formWindow) {
			if (componentType == 'NewFile') {
				this.update(formWindow);
			}
		},

		// send request to the server to obtain file upload form
		update: function(dialog) {
			var url = window.componentFormUrls['UPLOAD FILE'];
			var fileForm = dialog.find(".dynamic-container");
			if (fileForm.length == 0) {
				var fileForm = $("<div class=\"dynamic-container\"></div>");
				dialog.append(fileForm);
				var outParam = dialog.find("input[value=OUTPUT_CONNECTION]").parent().find("input[name$=value]");
				var data = {}
				if (outParam.val()) {
					data['dataset_url'] = outParam.val();
				}
				window.utils.showProgress();
				dialog.closest(".ui-dialog").find("button").attr("disabled", "disabled");
				$.ajax({
					url: url,
					data: data,
					context: fileForm
				}).done(function(resp) {
					$(this).html(resp);
					window.utils.customizeFileForm($(this));
					var buttons;

                    // TODO: fix buttons
					if (data['dataset_url']) {
						buttons = window.files.uploadedButtons();
					} else {
						buttons = window.files.allButtons();
					}
					dialog.dialog("option", "buttons", buttons);
					dialog.dialog("option", "min-width", 0);
					dialog.dialog("option", "width", "auto");
					window.utils.hideProgress();
				});
			}
		},

		// upload form in the iframe
		doUpload: function(dialog) {
            this.taskBoxId = /\d+/g.exec(dialog.attr("id"))[0];
			window.utils.showProgress();
			dialog.closest(".ui-dialog").find("button").attr("disabled", "disabled");

			fileForm = dialog.find(".dynamic-container");
			// TODO: clone does not preserve textarea and input values 
			// so we need to construct a placeholder differently 
			var fileFormPlaceholder = fileForm.clone(true);

			// append a field with currently selected file url
			var outParam = dialog.find("input[value=OUTPUT_CONNECTION]").parent().find("input[name$=value]");
			var currDatasetInput = $("<input type=\"hidden\" name=\"dataset_url\" />");
			currDatasetInput.val(outParam.val());
			fileForm.append(currDatasetInput);

			// move the fields to the hidden form
			// replace them with a placeholder
			var fileUploadForm = $("#file-upload-form");
			fileForm.after(fileFormPlaceholder).appendTo(fileUploadForm);
            if($('form#createDataset').length > 0){
                fileUploadForm.find('form').children().each(function(c){
                    fileUploadForm.append(fileUploadForm.find('form').children()[c]);
                });
                fileUploadForm.find('form').children().each(function(c){
                    fileUploadForm.append(fileUploadForm.find('form').children()[c]);
                });
            }
			$("#file-upload-iframe").off("load");
			$("#file-upload-iframe").on("load", function(resp) {
				window.files.handleUploadResponse(fileFormPlaceholder);
			});

			$("#file-upload-form").submit();
		},

		// process file upload response
		handleUploadResponse: function(fileFormPlaceholder) {
			var fileUploadIframe = $("#file-upload-iframe");
			var textContent = fileUploadIframe.contents().find("body").text();

			// if we no text in the iframe, it means that this was not a POST
			// response, so we should exit
			if (!textContent || textContent.length == 0) {
				return;
			}
			var responseText = $("<div class=\"dynamic-container\">" + fileUploadIframe.contents().find("body").html() + "</div>");

			// clear the iframe response in order to prevent unexpected processing
			fileUploadIframe.contents().find("body").html("");
			$("#file-upload-form").html("");

			var formWindow = fileFormPlaceholder.parent();

			var connectionInput = formWindow.find(".parameter-values input[value=OUTPUT_CONNECTION]");
			var valueInput = connectionInput.parent().find("input[name$=value]");
			var idInput = connectionInput.parent().find("input[name$=id]");
			if (this.checkSuccess(responseText)) {
				// set OUTPUT_CONNECTION parameter of this task to the uploaded 
				// file url
				var fileUrl = responseText.find("input[name=file_path]").val();
                this.filePath = fileUrl;
                window.params.addParam(this.taskBoxId, idInput.val(), fileUrl);
				valueInput.val(fileUrl);

				// display only another set of buttons 
				formWindow.dialog("option", "buttons", window.files.uploadedButtons());
			} else {
				if (valueInput.val()) {
					formWindow.dialog("option", "buttons", window.files.uploadedButtons());
				} else {
					formWindow.dialog("option", "buttons", window.files.allButtons());
				}
			}

			fileFormPlaceholder.remove();
			formWindow.append(responseText);
			window.utils.customizeFileForm(formWindow);
			window.utils.hideProgress();
		},

		// check if the upload was successful
		checkSuccess: function(resp) {
			return resp.find(".form-row li").length == 0;
		},

		uploadedButtons: function() {
			var buttons = [{
				"text": Translator.trans('OK', {}, 'ExperimentBundle'),
				"class": "btn btn-primary",
				"click": function(ev) {
					var fileForm = $(this).find(".dynamic-container");
					var submit = false;
					$.each(fileForm.find("input#datasets_newtype_datasetTitle,input#datasets_newtype_file,textarea#datasets_newtype_datasetDescription"), function(idx, el) {
						if ($(el).val()) {
                            if(!$('.toggle-btn').is(':visible'))
							    submit = true;
							return false;
						}
					});
					// submit form if any of the visible fields are filled in
					// otherwise, just close the window 
					if (submit) {
						window.files.doUpload($(this));
					} else {
						$(this).dialog("close");
					}
				}
			},
			{
				"text": Translator.trans('Cancel', {}, 'ExperimentBundle'),
				"class": "btn",
				"click": function(ev) {
                    $(this).find(".toggle-section").hide();
                    $(this).find(".toggle-btn").show();
					$(this).dialog("close");
				}

			}];
			return buttons;
		},

		// all buttons of this component dialog
		allButtons: function() {
			var buttons = [{
				"text": Translator.trans('OK', {}, 'ExperimentBundle'),
				"class": "btn btn-primary",
				"click": function(ev) {
					window.files.doUpload($(this));
				}
			},
			{
				"text": Translator.trans('Cancel', {}, 'ExperimentBundle'),
				"class": "btn",
				"click": function(ev) {
					$(this).dialog("close");
				}
			}];
			return buttons;
		}
	}
})();

