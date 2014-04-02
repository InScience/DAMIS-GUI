(function() {
	window.taskBoxes = {
        currentBoxId : null,

		assembleBoxHTML: function(boxName, icoUrl, clusterIcoUrl, componentId) {
			var closeIco = '<a class="black-remove" href="#"><span class="component-tooltip glyphicon glyphicon-remove"></span></a>';
			var clusterIco = '<span style="width: 20px; height: 20px; position: absolute; top:0; left:0; background: url(' + clusterIcoUrl + ')"></span>';
			return '<div class="task-box" data-componentId="' + componentId + '"><img src=\"' + icoUrl + '\" width=\"64px\" height=\"64px\" />' + closeIco + clusterIco + '<div class=\"desc\"><div>' + Translator.trans(boxName, {}, 'ExperimentBundle') + '</div></div></div>';
		},

		countBoxes: 0,

		// remove all endpoints of a task box
		removeEndpoints: function(taskBoxId) {
			var epoints = jsPlumb.getEndpoints(taskBoxId);
			var epoints2 = [];
			if (epoints) {
				$.each(epoints, function(idx, e) {
					epoints2.push(e);
				});
				var len = epoints2.length;
				while (len--) {
					jsPlumb.deleteEndpoint(epoints2[len]);
				}
			}
		},

		removeTaskBox: function(taskBox) {
			var formWindow = $("#" + window.taskBoxes.getFormWindowId(taskBox));
			formWindow.find(".delete-row").click(); // remove task form
			formWindow.remove(); // remove the window
			// all connections are automatically detached
			// so this box outgoing connections input parameters are
			// reset by "connectionDetached" event handler
			window.taskBoxes.removeEndpoints(taskBox.attr("id"));
			taskBox.remove(); // remove task box
		},

		// modify box name according to component selection
		setBoxName: function(taskBoxId, title) {
			var nameContainer = $("#" + taskBoxId).find(".desc div");
			nameContainer.html(Translator.trans(title, {}, 'ExperimentBundle'));
		},

		// delete existing endpoints and create new ones to reflect the
		// selected component
		recreateEndpoints: function(taskBoxId, formWindow) {
			// Remove old endpoints
			window.taskBoxes.removeEndpoints(taskBoxId);
			// Add new endpoints for input/output parameters
			var parameters = formWindow.find('.parameter-values');

			var outAnchors = ["Right", "BottomRight", "TopCenter"];
			var oIdx = 0;
			var inAnchors = ["Left", "BottomLeft", "BottomCenter"];
			var iIdx = 0;

			var taskBox = $("#" + taskBoxId);

			// inspect each parameter form
			// each form has one "value" field and
			// an indicator field: "connection_type"
			$.each(parameters.find('div'), function(idx) {
				var connectionType = $(this).find("input[id$='connection_type']").val();
				var paramName = "<span>" + $(this).find("span").text() + "</span>";

				if (connectionType === "INPUT_CONNECTION") {
					//add input endpoint
					window.endpoints.addEndpoint(true, taskBox, inAnchors[iIdx], {
						iParamNo: idx,
						iTaskBoxId: taskBoxId
					});
					iIdx++;
				} else if (connectionType === "OUTPUT_CONNECTION") {
					//add output endpoint
					window.endpoints.addEndpoint(false, taskBox, outAnchors[oIdx], {
						oParamNo: idx,
						oTaskBoxId: taskBoxId
					});
					oIdx++;
				}
			});
		},

		// Loads parameters for the selected component
		loadComponentParameters: function(componentInput) {
			$.ajax({
				url: Routing.generate('component', {id : componentInput.val()}),
				context: $(this),
                async:   false
			}).done(function(resp) {
				// replace old parameter formset with a new one
				var formWindow = componentInput.closest('.task').parent();
				formWindow.find(".parameter-values").html(resp);

				var taskBoxId = window.taskBoxes.getBoxId(formWindow);
				window.taskBoxes.recreateEndpoints(taskBoxId, formWindow);
			});
		},

		// create modal window
		createTaskFormDialog: function(taskForm, existingParameters, formWindowId, title, componentId) {
			var taskFormContainer = $("<div></div>");
			taskFormContainer.attr("id", formWindowId);
			taskFormContainer.addClass("task-window");
			taskFormContainer.append(taskForm);
			if (existingParameters) {
				taskFormContainer.append(existingParameters);
			} else {
				taskFormContainer.append("<div class=\"parameter-values\"></div>");
			}
			taskFormContainer.dialog({
				title: Translator.trans(title, {}, 'ExperimentBundle'),
				autoOpen: false,
				appendTo: "#task-forms",
				modal: true,
				// Cancel button should return the box to a previous state, but
				// that is too complicated for now, so no Cancel button
				buttons: window.taskBoxes.defaultDialogButtons(),
				open: function() {
					var dialog = $(this).closest(".ui-dialog");
					dialog.find(".ui-dialog-titlebar > button").remove();
				}
			});

			var componentType = window.componentSettings.getComponentDetails({
				componentId: componentId
			})['type'];
			$.each(window.eventObservers.eventObservers, function(idx, o) {
				if (o.init) {
					o.init(componentType, taskFormContainer);
				}
			});
		},

		defaultDialogButtons: function() {
			return [{
				"text": Translator.trans('OK', {}, 'ExperimentBundle'),
				"class": "btn btn-primary",
				"click": function(ev) {
					// TODO: send to server for processing
					$(this).dialog("close");
				}
			},
			{
				"text": Translator.trans('Cancel', {}, 'ExperimentBundle'),
				"class": "btn",
				"click": function(ev) {
					// TODO: discard any changes
					$(this).dialog("close");
				}

			}]
		},

		// create a new task box and modal window, assign event handlers 
		createTaskBox: function(ev, ui, taskContainer) {
			// create a task form for this box
			var addTaskBtn = $("a.add-row")
			addTaskBtn.click();
			var taskForm = addTaskBtn.prev();

			//set component ID into the task form
			var componentId = $(ui.draggable).find("input").val();
			var componentInput = taskForm.find(".component-id input");
			componentInput.val(componentId);

			// drop the task where it was dragged
			var componentDetails = window.componentSettings.details[componentId];
			var componentLabel = componentDetails['label'];
			var clusterIcoUrl = componentDetails['cluster_ico'];
			var icoUrl = componentDetails['ico'];

			var taskBox = $(window.taskBoxes.assembleBoxHTML(componentLabel, icoUrl, clusterIcoUrl, componentId));
			taskBox.appendTo(taskContainer);
			taskBox.css("left", ui.position.left + "px");
			taskBox.css("top", ui.position.top + "px");

			//assign id
			count = window.taskBoxes.countBoxes;
			window.taskBoxes.countBoxes++;
			taskBox.attr("id", "task-box-" + count);

			// create modal window for the form
			window.taskBoxes.createTaskFormDialog(taskForm, null, window.taskBoxes.getFormWindowId(taskBox), componentLabel, componentId);

			this.addTaskBoxEventHandlers(taskBox);

			// asynchronous Ajax-loading of parameters, so don't add code below
			window.taskBoxes.loadComponentParameters(componentInput);
		},

		//adds task box event handlers: delete task box, dbclick, and makes it
		//draggable
		addTaskBoxEventHandlers: function(taskBox) {

			// delete task box on right-click
			var closeIco = taskBox.find(".glyphicon-remove");
			closeIco.off("click");
			closeIco.on("click", function(ev) {
				var taskBox = $(ev.target).closest(".task-box");
				window.taskBoxes.removeTaskBox(taskBox);
			});

			// open dialog on dbclick
			taskBox.off("dbclick");
			taskBox.on("dblclick", function(ev) {
				var boxId = $(ev.currentTarget).attr("id");
                window.taskBoxes.currentBoxId = boxId;
				var formWindowId = window.taskBoxes.getFormWindowId(boxId);
				var formWindow = $("#" + formWindowId);
				var componentType = window.componentSettings.getComponentDetails({
					componentId : $(ev.currentTarget).attr('data-componentid')
				})['type'];

				$.each(window.eventObservers.eventObservers, function(idx, o) {
					if (o.doubleClick) {
						o.doubleClick(componentType, formWindow);
					}
				});

                //Checking if its connected to file
                if(componentType === 'Filter' || componentType === 'Select') {
                    var datasetId = window.taskBoxes.getConnectedTaskBoxDatasetId(boxId);
                    if(datasetId === false || datasetId === '')
                        window.taskBoxes.toUnconnectedState(formWindow);
                    else {
                        window.taskBoxes.showConnectedForm(formWindow);
                        window.componentForm.update(formWindow, datasetId);
                    }
                }

				formWindow.dialog('open');
			});

			//make the box draggable
			jsPlumb.draggable(taskBox, {
				grid: [20, 20],
				containment: "parent"
			});
		},

		//generates task box id from the provided task form 
		getBoxId: function(formWindow) {
			var windowId = formWindow instanceof $ ? formWindow.attr("id") : formWindow;
			return windowId.replace("-form", "");
		},

		// generates task form id from the provided task box
		getFormWindowId: function(taskBox) {
			return taskBox instanceof $ ? taskBox.attr("id") + "-form": taskBox + "-form";
		},

        getConnectedTaskBoxDatasetId: function(taskBox) {
            var ancestor = this.getAncestorTaskBoxId(taskBox);

            if(ancestor == false)
                return false;

            var inputValue = $('div#' + ancestor + '-form')
                .find('.parameter-values input[value=OUTPUT_CONNECTION]')
                .parent()
                .find('input[name$=value]')
                .val();

            var datasetValue = window.datasets[ancestor];

            if(datasetValue == undefined && inputValue == undefined) {
                var ancestorComponent = window.componentSettings.getComponentDetails({boxId : ancestor});
                if(ancestorComponent != undefined)
                    if(ancestorComponent['type'] == 'NewFile' || ancestorComponent['type'] == 'UploadedFile')
                        if(window.params.getParams(ancestor)[0] != undefined)
                            return window.params.getParams(ancestor)[0].value;
            }

            return (inputValue == undefined) ? datasetValue : inputValue;
        },

        getAncestorTaskBoxId : function (taskBox) {
            var connections = jsPlumb.getConnections({
                target: taskBox
            });

            if(connections.length == 0)
                return false;
            else
                return connections[0].sourceId;
        },

        toUnconnectedState: function(formWindow) {
            formWindow.find(".plot-container").remove();
            formWindow.find(".dynamic-container").hide();
            var container = $("<div class=\"plot-container\">" + Translator.trans("This component should be connected to a selected file", {}, 'ExperimentBundle') + "</div>");
            formWindow.append(container);
            formWindow.dialog("option", "buttons", window.chart.notConnectedButtons());
            formWindow.dialog("option", "width", "auto");
        },

        showConnectedForm : function(formWindow) {
            formWindow.find(".plot-container").remove();
            formWindow.find(".dynamic-container").show();
            formWindow.dialog("option", "buttons", window.componentForm.allButtons());
        }
	}

})();

