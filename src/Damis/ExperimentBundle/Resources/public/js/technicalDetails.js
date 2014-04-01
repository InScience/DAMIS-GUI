(function() {
	window.technicalDetails = {
		init: function(componentType, formWindow) {
			if (componentType == 'TechnicalInfo') {
				this.toUnconnectedState(formWindow);
			}
		},

		// prepare dialog, when component is unconnected 
		toUnconnectedState: function(formWindow) {
			formWindow.find(".technical-details-container").remove();
			var container = $("<div class=\"technical-details-container\">" +
                Translator.trans("This component should be connected to an executed task in order to view results.", {}, 'ExperimentBundle') +
                "</div>");
			formWindow.append(container);
			formWindow.dialog("option", "buttons", window.technicalDetails.reducedButtons());
			formWindow.dialog("option", "minWidth", 0);
			formWindow.dialog("option", "width", 300);
		},

        reducedButtons: function() {
             var buttons = [{
                 "text":Translator.trans('Cancel', {}, 'ExperimentBundle'),
                 "class": "btn",
                 "click": function(ev) {
                     $(this).dialog("close");
                 }
             }];
             return buttons;
        },

		// all buttons for this component
        allButtons: function() {
            var buttons = [{
                "text": Translator.trans('Download', {}, 'ExperimentBundle'),
                "class": "btn btn-primary",
                "click": function(ev) {
                    var formWindow = $(this);
                    var downloadOptions = $(this).find(".download-options").clone(true);
                    downloadOptions.dialog({
                        "title": Translator.trans("Select file type and destination", {}, 'ExperimentBundle'),
                        "modal": true,
                        "minWidth": 450,
                        "open": function() {
                            var dialog = $(this).closest(".ui-dialog");
                            dialog.find(".ui-dialog-titlebar > button").remove();
                        },
                        "buttons": [{
                            "text": Translator.trans("OK", {}, 'ExperimentBundle'),
                            "class": "btn btn-primary",
                            "click": function(ev) {
                                var data = window.matrixView.getOutputParamDetails(formWindow);
                                var format = $(this).find("input[name=file-type]:checked").val();
                                var dst = $(this).find("input[name=file-destination]:checked").val();
                                if (dst == 'midas') {
                                    $(this).find(".not-implemented").show();
                                } else {
                                    //image = image.replace("image/png", "image/octet-stream");
                                    var url = Routing.generate('technical_information',{id : data["dataset_url"]});

                                    // POST to server to obtain a downloadable result
                                    var formatInput = $("<input name=\"format\" value=\"" + format + "\"/>");
                                    var myForm = $("<form method=\"post\" action=\"" + url + "\"></form>");
                                    myForm.append(formatInput);
                                    $("body").append(myForm);
                                    myForm.submit();
                                    myForm.remove();
                                    $(this).dialog("destroy");
                                }
                            }
                        },
                            {
                                "text": Translator.trans("Cancel", {}, 'ExperimentBundle'),
                                "class": "btn",
                                "click": function(ev) {
                                    $(".not-implemented").hide();
                                    $(this).dialog("destroy");
                                }
                            }]
                    });
                }
            }];
            var reducedButtons = window.matrixView.reducedButtons();
            return buttons.concat(reducedButtons);
        },

		// update dialog content with new data
		update: function(formWindow) {
            var boxId = window.taskBoxes.getBoxId(formWindow);
            var ancestor = window.taskBoxes.getAncestorTaskBoxId(boxId);
            var datasetId = window.taskBoxes.getConnectedTaskBoxDatasetId(ancestor);
			formWindow.find(".technical-details-container").remove();
			var container = $("<div class=\"technical-details-container\"><img width=\"250px\" src=\"/bundles/damisexperiment/images/loading.gif\"/></div>");
			formWindow.append(container);
			var data = window.technicalDetails.getOutputParamDetails(formWindow);
            if(!datasetId)
                datasetId = 'undefined';
            var url = Routing.generate('technical_information',
                {id : datasetId, experimentId : $('#id_experiment').val(), taskBox: ancestor});
			$.ajax({
				url: url,
				data: data,
				context: container
			}).done(function(resp) {
				$(this).html(resp);
				if (!/<[a-z][\s\S]*>/i.test(resp)) {
					// non-html (failure) response
				} else {
					formWindow.dialog("option", "buttons", window.technicalDetails.allButtons());
					formWindow.dialog("option", "minWidth", 0);
					formWindow.dialog("option", "maxHeight", 400);
					formWindow.dialog("option", "width", "auto");
				}
			});
		},

		// get details of a parameter, that is connected to the current component input connection
		getOutputParamDetails: function(dialog) {
            // TODO get data of dataset
			var inParam = dialog.find("input[value=INPUT_CONNECTION]");
			var srcRefField = inParam.closest("div").find("input[id$=source_ref]");
			var oParamField = window.experimentForm.getOutputParam(srcRefField);
			if (oParamField) {
				return {
					pv_name: oParamField.attr("name"),
					dataset_url: oParamField.val()
				}
			}
			return {}
		},

		// called when connection is established
		connectionEstablished: function(srcComponentType, targetComponentType, connectionParams) {
			if (targetComponentType == 'TechnicalInfo') {
				this.update($("#" + window.taskBoxes.getFormWindowId(connectionParams.iTaskBoxId)));
			}
		},

		// called when connection is deleted
		connectionDeleted: function(srcComponentType, targetComponentType, connectionParams) {
			if (srcComponentType == 'TechnicalInfo' || targetComponentType == 'TechnicalInfo') {
				var formWindow = $("#" + window.taskBoxes.getFormWindowId(connectionParams.iTaskBoxId));
				this.toUnconnectedState(formWindow);
			}
		},

        doubleClick: function(componentType, formWindow) {
            if (componentType == 'TechnicalInfo') {
                var boxId = window.taskBoxes.getBoxId(formWindow);
                var datasetId = window.taskBoxes.getConnectedTaskBoxDatasetId(boxId);
                if (datasetId == ""){
                    this.toUnconnectedState(formWindow);
                } else{
                    formWindow.dialog("option", "width", 'auto');
                    formWindow.dialog("open");
                    this.update(formWindow);

                }
            }
        }
	}
})();

