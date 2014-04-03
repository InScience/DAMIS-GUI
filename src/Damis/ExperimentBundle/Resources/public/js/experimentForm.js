;
(function() {
	window.experimentForm = {
		// parameters for window.experimentForm initialization
		params: {},

		// translate parameter binding from client to server
		// representation
		bindingToServer: function() {
			$.each($(".task-window input[id$=connection_type]"), function() {
				if ($(this).val() === "INPUT_CONNECTION") { // inspect each input parameter
					var srcRefField = $(this).closest("div").find("input[id$=source_ref]");
					var oParamField = window.experimentForm.getOutputParam(srcRefField);
					if (oParamField) {
						srcRefField.val(oParamField.attr("name"));
					}
				}
			});
		},

		// translate parameter binding from server to client
		// representation
		// parameterFormset - target box parameters
		bindingToClient: function(parameterFormset) {
			$.each(parameterFormset.find("input[id$=connection_type]"), function() {
				if ($(this).val() === "INPUT_CONNECTION") { // inspect each input parameter
					var srcRefField = $(this).closest("div").find("input[id$=source_ref]");
					var oParamName = $(srcRefField).val();
					if (oParamName) {
						var oParam = $("input[name=" + oParamName + "]");
						var sourceForm = oParam.closest(".task-window");
						var sourceBoxId = window.taskBoxes.getBoxId(sourceForm);

						var oParent = oParam.closest("div");
						var paramNo = oParent.index();
						srcRefField.val(paramNo + "," + sourceBoxId);
					}
				}
			});
		},

		// refresh parameter formset prefixes before submition
		// call the callback after prefixes refresh
		updatePrefixes: function(parameterPrefixesUrl, callback, params) {
			// pass current task forms prefixes to get parameter
			// formsets prefixes
			var taskFormPrefixes = []
			var taskIds = []
			$.each($(".task-window .task-form"), function(taskBoxIdx, taskForm) {
				var name = $(taskForm).find("input,select,textarea,label").attr("name");
				var taskFormPrefix = /tasks-\d+/g;
				taskFormPrefixes.push(taskFormPrefix.exec(name)[0]);

				var taskId = $(taskForm).find("input[id$=id]").val();
				taskIds.push(taskId ? taskId: "-");
			});
			$.ajax({
				url: parameterPrefixesUrl,
				data: {
					prefixes: taskFormPrefixes,
					taskIds: taskIds
				},
				context: $(this)
			}).done(function(parameterFormsetPrefixes) {
				// when a box is deleted, other boxes have their ids
				// updated,  however, parameter formsets prefixes are not updated
				// we need to do it manually
				var paramPrefixes = parameterFormsetPrefixes.split(",");
				$.each($(".task-window .parameter-values"), function(taskBoxIdx, paramsFormset) {
					$.each($(paramsFormset).find("input,select,textarea,label"), function(inputIdx, input) {
						var origPrefix = paramPrefixes[taskBoxIdx];
						var name = $(input).attr("name");
						var id = $(input).attr("id");
						if (name) {
							$(input).attr("name", name.replace(/PV_\d+/, origPrefix));
						}
						if (id) {
							$(input).attr("id", id.replace(/PV_\d+/, origPrefix));
						}
					});
				});
				callback(params);
			});
		},

		// Create form modal windows, assign them to boxes
		reinitExperimentForm: function() {

			// recreate modal windows
			// iterate through existing task boxes
			// in the order of creation (asume, it is reflected
			// in DOM order)
			var updatedForms = $("#experiment-form .inline");
			$.each($(".task-box"), function(taskBoxId, taskBox) {
				taskForm = $(updatedForms[taskBoxId + 1]);
				parameterFormset = $(taskForm.next(".parameter-values"));
				// mark the task box as conataining errors
				if (taskForm.find(".errorlist").length > 0 || parameterFormset.find(".errorlist").length > 0) {
					$(taskBox).addClass("error");
				} else {
					$(taskBox).removeClass("error");
				}
                var componentId = $(taskBox).attr('data-componentId');
				var componentLabel = window.componentSettings.getComponentDetails({
					componentId: componentId
				})['label'];
                $(taskForm).find('span.component-id input').val(componentId);
				window.taskBoxes.createTaskFormDialog(taskForm, parameterFormset,
                    window.taskBoxes.getFormWindowId($(taskBox)), componentLabel, componentId);
				window.taskBoxes.setBoxName($(taskBox).attr("id"), componentLabel);
				window.taskBoxes.addTaskBoxEventHandlers($(taskBox));
			});
			$.each($(".task-box"), function(taskBoxId, taskBox) {
				//restore parameter bindings from server to client representation
				taskForm = $(updatedForms[taskBoxId + 1]);
				parameterFormset = $(taskForm.next(".parameter-values"));
				window.experimentForm.bindingToClient(parameterFormset);
			});
		},

		// Submits the experiment form and reinitializes it
		submit: function(params) {
			// translate parameter bindings from client to server
			// representation
			window.experimentForm.bindingToServer();

			var form = $("#experiment-form");
			if (params["skipValidation"]) {
				form.find("input[name=experiment-skip_validation]").val("True");
                var valid = true;
			} else
                var valid = window.validation.validate();
			var data = form.serialize();
            if(valid)
                $.post(form.attr("action"), data, function(resp) {
                    if (!/<[a-z][\s\S]*>/i.test(resp)) {
                        // non-html string is returned, which is a redirec url
                        window.location = resp;
                        return;
                    }
                    //replace the existing form with the validated one
                    $("#experiment-form").remove();
                    $("#workflow-editor-container").before(resp);

                    //run standard initialization
                    window.experimentForm.init();
                    window.experimentForm.reinitExperimentForm();
                });
		},

		// init formset plugin and form submit handlers
		init: function() {
			var params = window.experimentForm.params;

			parametersUrl = params['parametersUrl'];
			parameterPrefixesUrl = params['parameterPrefixesUrl'];
			taskFormPrefix = params['taskFormPrefix'];

			//initialize the jQuery formset plugin
			$('.inline').formset({
				prefix: taskFormPrefix,
				extraClasses: ['task-form']
			});

			//assign new experiment handler
			$('#new-experiment-btn').click(function(ev) {
				window.location = params['experimentNewUrl'];
			});

			// open save dialog
			$('#save-btn').click(function(ev) {
				// hide all experiment params except title
				window.experimentForm.executeDialog("save");
			});

			// open execute dialog
			$('#execute-btn').click(function(ev) {
				window.experimentForm.executeDialog("execute");
			});

		},

		executeDialog: function(action) {
			var dialog = $("#exec-dialog");
			dialog.dialog({
				"title": 'Experiment settings',
				"modal": true,
				"appendTo": "#experiment-form",
				"buttons": [{
                    "text":  Translator.trans('Cancel', {}, 'ExperimentBundle'),
					"class": "btn",
					"click": function(ev) {
						$(this).dialog("close");
					}
				},
				{
                    "text": Translator.trans('OK', {}, 'ExperimentBundle'),
					"class": "btn btn-primary",
					"click": function(ev) {
						$(this).dialog("close");
						if (action == "execute") {
                            var persistedStr = window.persistWorkflow.persistJsPlumbEntities();
                            $("#experiment-form input[name=experiment-workflow_state]").val(persistedStr);
                            $("#experiment-form input[name=experiment-execute]").val(1);
                            window.experimentForm.submit({});
						} else {
                            var persistedStr = window.persistWorkflow.persistJsPlumbEntities();
                            $("#experiment-form input[name=experiment-workflow_state]").val(persistedStr);
                            window.experimentForm.submit({
                                "skipValidation": true
                            });
						}
					}
				}],
				"open": function() {
					var dialog = $(this).closest(".ui-dialog");
					dialog.find(".ui-dialog-titlebar > button").remove();

					if (action == "execute") {
						$(this).find("#exec-params").show();
					} else {
						$(this).find("#exec-params").hide();
					}
				},
				"close": function() {
					$(this).dialog("destroy");
				}
			});
            $('a#change-title').on('click', function(e) {
                e.preventDefault();
                $('a#change-title').css('display', 'none');
                $('span#experiment-title').css('display', 'none');
                $('input#id_experiment-title').css('display', 'inline');
            });
		},

		// returns parameter form, given 
		// parameter number in the formset
		// and task box id
		getParameter: function(parameterNum, taskBoxId) {
			var taskFormWindow = $("#" + window.taskBoxes.getFormWindowId(taskBoxId));
			var paramForm = $(taskFormWindow.find(".parameter-values").find("div")[parameterNum]);
			return paramForm;
		},

		//returns parameter value field in parameter form
		getParameterValue: function(paramForm) {
			return paramForm.find("input[id$=value]");
		},

		//returns parameter source_ref field in parameter form
		getParameterSourceRef: function(paramForm) {
			return paramForm.find("input[id$=source_ref]");
		},

		// returns connected output parameter value or undefined
		getOutputParam: function(srcRefField) {
			var oParamAddr = $(srcRefField).val();
			if (oParamAddr) {
				var parts = oParamAddr.split(",");
				var oParam = window.experimentForm.getParameter(parts[0], parts[1]);
				var oParamField = window.experimentForm.getParameterValue(oParam);
				return oParamField;
			}
			return null;
		}
	}
})();

