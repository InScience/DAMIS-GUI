(function() {
	window.midasFile = {
		init: function(componentType, formWindow) {
			if (componentType == 'MidasFile') {
				window.midasFile.update(formWindow);
			}
		},

		// send request to the server to obtain file upload form
		update: function(dialog, url, id, path) {
            var componentInput = dialog.find(".component-id input");
            if(typeof path === 'undefined')
                path = '';
			if (!url) {
                url = Routing.generate('existing_midas_file', {'id' : id, 'path' : path});
			}
			var container = dialog.find(".dynamic-container");
			var fileList;
			if (container.length == 0) {
				container = $("<div class=\"dynamic-container\"></div>");
				dialog.append(container);
			} else {
				fileList = container.find(".file-list");
			}

            var data = {}
            if (window.params.getParams(window.taskBoxes.getBoxId(dialog))) {
                data['data'] = JSON.stringify(window.params.getParams(window.taskBoxes.getBoxId(dialog)));
            }
			dialog.closest(".ui-dialog").find("button").attr("disabled", "disabled");
			window.utils.showProgress();
			$.ajax({
				url: url,
				data: data,
				context: container
			}).done(function(resp) {
				var container = $(this);
				container.html(resp);

				// bind paging handler
				container.find("a").on("click", function(ev) {
                    if(!ev.hasClass('fileView')) {
                        ev.preventDefault();
                        var page_url = $(this).attr("href");
                        if (!page_url.match(/.*#.*/g)) {
                            window.midasFile.update(dialog, page_url);
                        }
                    }
				});

				window.utils.initToggleSectionBtn(container);

				dialog.dialog("option", "buttons", window.midasFile.allButtons());
				dialog.dialog("option", "minWidth", 0);
				dialog.dialog("option", "width", "auto");
				window.utils.hideProgress();
			});
		},

		// all buttons of this component dialog
		allButtons: function() {
			var buttons = [{
				"text": Translator.trans('OK', {}, 'ExperimentBundle'),
				"class": "btn btn-primary",
				"click": function(ev) {
					var container = $(this).find(".dynamic-container");
					var datasetInput = container.find("input[name=dataset_pk]:checked");
					var path = container.find("input[name=folderPath]").val();
					if (datasetInput.val()) {
						var  datasetId = $(datasetInput).val();

						// set OUTPUT_CONNECTION value for this component
						var connectionInput = $(this).find(".parameter-values input[value=OUTPUT_CONNECTION]");
						var valueInput = connectionInput.parent().find("input[name$=value]");
                        var idInput = connectionInput.parent().find("input[name$=id]");
                        var id = null;
                        var params = window.params.getParams(window.taskBoxes.getBoxId($(this)));
                        if(params.length > 0)
                            id = params[0].id;
                        else
                            id = idInput.val();

                        window.params.addParam(window.taskBoxes.getBoxId($(this)), id, datasetId);
                        window.datasets[window.taskBoxes.getBoxId($(this))] = datasetId;
                        data = {};
						valueInput.val(datasetId);
                        if (window.params.getParams(window.taskBoxes.getBoxId($(this)))) {
                            data['data'] = JSON.stringify(window.params.getParams(window.taskBoxes.getBoxId($(this))));
                        }
                        url = Routing.generate('existing_midas_file', {'id' : id, 'path' : path});
                        $.ajax({
                            url: url,
                            data: data,
                            context: container,
                            method: "POST"
                        }).done(function(resp){
                            var container = $(this);
                            container.html(resp);
                            var currDatasetInput = $('input[name="dataset_url"]').val();
                            window.params.addParam(window.taskBoxes.getBoxId($(this).parent()), id, currDatasetInput);
                            window.datasets[window.taskBoxes.getBoxId($(this).parent())] = currDatasetInput;
                        });
					}
                    if($('.toggle-btn').is(':visible'))
                        $(this).dialog("close");
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
    $(document).on('click', ".toggle-btn", function(e){
        window.midasFile.update($(this).parent().parent().parent().find('.task-window'), Routing.generate('existing_midas_file', {'id' : 'undefined', 'path' : '', 'edit' : 1}));
    });
})();

