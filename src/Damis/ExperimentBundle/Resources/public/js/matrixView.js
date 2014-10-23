(function() {
	window.matrixView = {

		// prepare dialog, when component is unconnected 
		toUnconnectedState: function(formWindow) {
			formWindow.find(".matrix-container").remove();
			var container = $("<div class=\"matrix-container\">" + Translator.trans("This component should be connected to a selected file or an executed task in order to view results.", {}, 'ExperimentBundle') + "</div>");
			formWindow.append(container);
			formWindow.dialog("option", "buttons", window.matrixView.reducedButtons());
			formWindow.dialog("option", "width", "auto");
		},

		reducedButtons: function() {
			var buttons = [{
				"text": Translator.trans('Cancel', {}, 'ExperimentBundle'),
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
                                var path = $(this).find('input[name="folderPath"]').val();
                                //image = image.replace("image/png", "image/octet-stream");
                                var url = Routing.generate('matrix_view',{id : data["dataset_url"], dst : dst, path : path});

                                // POST to server to obtain a downloadable result
                                var formatInput = $("<input name=\"format\" value=\"" + format + "\"/>");
                                var myForm = $("<form method=\"post\" action=\"" + url + "\"></form>");
                                myForm.append(formatInput);
                                $("body").append(myForm);
                                myForm.submit();
                                myForm.remove();
                                $(this).dialog("destroy");

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
			var data = window.matrixView.getOutputParamDetails(formWindow);
            if (!data["dataset_url"]) {
                this.toUnconnectedState(formWindow);
                return;
            }
			var url = Routing.generate('matrix_view',{id : data["dataset_url"]});
			formWindow.find(".matrix-container").remove();
			var container = $("<div class=\"matrix-container\"><img style=\"display: block; width: 250px; margin:auto;\" width=\"250px\" src=\"/bundles/damisexperiment/images/loading.gif\"/></div>");
            formWindow.dialog("option", "buttons", this.reducedButtons());
			formWindow.append(container);
			$.ajax({
				url: url,
				data: data,
				context: container
			}).done(function(resp) {
				$(this).html(resp);
				if (!/<[a-z][\s\S]*>/i.test(resp)) {
					// non-html (failure) response
				} else {
					formWindow.dialog("option", "buttons", window.matrixView.allButtons());
					formWindow.dialog("option", "width", "auto");
					var table = $(formWindow).find(".file-content-table");
					//var dataTable = window.matrixView.initTable(table);
				}
			});
		},

		initTable: function(table) {
			return table.dataTable({
				"sScrollY": 400,
				"sScrollX": "100%",
				"bInfo": false,
				"bPaginate": false,
				"bFilter": false,
                "bSort": false,
				"bDestroy": true,
                "bScrollCollapse": true
			});

		},

		// get details of a parameter, that is connected to the current component input connection
		getOutputParamDetails: function(dialog) {
            var dataset_id = window.taskBoxes.getConnectedTaskBoxDatasetId(
                window.taskBoxes.getBoxId($(dialog).attr('id'))
            );
			if (dataset_id) {
				return {
					dataset_url: dataset_id
				}
			}
			return {}
		},

		doubleClick: function(componentType, formWindow) {
			if (componentType == 'Matrix') {
				formWindow.dialog("option", "width", 614);
				formWindow.dialog("open");
				this.update(formWindow);
			}
		}
	}
})();

