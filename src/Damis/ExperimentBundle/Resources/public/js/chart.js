(function() {
	window.chart = {

		// prepare dialog, when component is unconnected 
		toUnconnectedState: function(formWindow) {
			formWindow.find(".plot-container").remove();
			var container = $("<div class=\"plot-container\">" + Translator.trans("This component should be connected to a selected file or an executed task in order to view results.", {}, 'ExperimentBundle') + "</div>");
			formWindow.append(container);
			formWindow.dialog("option", "buttons", window.chart.notConnectedButtons());
			formWindow.dialog("option", "width", "auto");
            formWindow.find('.attribute-choices').remove();
		},

		errorButtons: function() {
			return window.chart.notConnectedButtons();
		},

		notConnectedButtons: function() {
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
					window.chart.downloadChart($(this));
				}
			}];
			var errorButtons = window.chart.notConnectedButtons();
			var result = buttons.concat(errorButtons);
			return result;
		},

		// custom color palette, rotates through a range of hue values
		generateColorPalette: function(data) {
			var len = data.length;
			return $.map(data, function(o, i) {
				return jQuery.Color({
					hue: (i * 300 / len),
					saturation: 0.95,
					lightness: 0.35,
					alpha: 1
				}).toHexString();
			});
		},

		// symbol palette, rotates through a set of symbols
		generateSymbolPalette: function() {
			return [["circle", Translator.trans('Circle', {}, 'ExperimentBundle')], ["square", Translator.trans('Square', {}, 'ExperimentBundle')],["diamond", Translator.trans('Diamond', {}, 'ExperimentBundle')],["triangle", Translator.trans("Triangle", {}, 'ExperimentBundle')],["cross", Translator.trans("Cross", {}, 'ExperimentBundle')]];
		},

		// renders the chart in place of plotPlaceholder
		renderChart: function(plotContainer, plotPlaceholder, dataContent, colors, symbols) {
			var data = [];
			$.each(dataContent.data, function(idx, rec) {
				data.push({
					label: rec['group'],
					points: {
						symbol: symbols[idx]
					},
					data: rec['data'],
					color: colors[idx]
				});
			});
			var options = {
				canvas: true,
				series: {
					points: {
						show: true,
						radius: 3
					}
				},
				legend: {
					show: false
				},
				grid: {
					backgroundColor: "#ffffff",
					clickable: true,
					hoverable: true
				},
				xaxis: {
					min: dataContent["minX"],
					max: dataContent["maxX"],
					tickSize: 5
				},
				yaxis: {
					min: dataContent["minY"],
					max: dataContent["maxY"],
					tickSize: 5
				}
			};

			var plot = $.plot(plotPlaceholder, data, options);

			$(plotPlaceholder).bind("plotclick", function(event, pos, item) {
				if (item) {
					$("<div id='point-tooltip'></div>").css({
						position: "absolute",
						display: "none",
						border: "1px solid #fdd",
						padding: "2px",
						"background-color": "#fee",
						opacity: 0.80
					}).appendTo(plotContainer);

					var x = item.datapoint[0].toFixed(2),
					y = item.datapoint[1].toFixed(2);

					var containerOffset = $(plotContainer).find(".results-container").offset();
					$("#point-tooltip").html(
                            x + ", " + y + " (" + Translator.trans("index") + ": " + (item.dataIndex + 1) + ", "
                                + Translator.trans("class", {}, 'ExperimentBundle') + ": " + item.series.label + ")")
                    .css({
						top: item.pageY - containerOffset['top'],
						left: item.pageX - containerOffset['left'] + 10
					}).fadeIn(200);
				} else {
					$("#point-tooltip").hide();
				}
			});
		},

		// updates the chart colors and symbols
		updateChartColorsSymbols: function(resp, formWindow, params) {
			var data = resp.content.data;
			var colorPalette = window.chart.generateColorPalette(data);
			var symbolPalette = window.chart.generateSymbolPalette();
			var colors = {};
			var symbols = {};
			$.each(params['renderChoices'], function(idx, choice) {
				var color = $(choice).find("input").val();
				colors[idx] = color ? color: colorPalette[idx % colorPalette.length];

				var symbol = $(choice).find("select").val();
				symbols[idx] = symbol ? symbol: symbolPalette[0][0];
			});
			window.chart.renderChartAndForm(resp, formWindow, {
				"selectedColors": colors,
				"selectedSymbols": symbols
			});
		},

		// displays a image format selection dialog 
		downloadChart: function(formWindow) {
			var downloadOptions = formWindow.find(".download-options").clone(true);
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
						// TODO: show progress indicator
						var canvas = formWindow.find(".plot-container").find("canvas")[0];
						var image;
						var format = $(this).find("input[name=file-type]:checked").val();
						var dst = $(this).find("input[name=file-destination]:checked").val();
						if (dst == "midas") {
							$(this).find(".not-implemented").show();
						} else {
							image = canvas.toDataURL();
							//image = image.replace("image/png", "image/octet-stream");
							var url = Routing.generate('dataset_chart');

							// POST to server to obtain a downloadable result
							var imageInput = $("<input name=\"image\" value=\"" + image + "\"/>");
							var formatInput = $("<input name=\"format\" value=\"" + format + "\"/>");
							var myForm = $("<form method=\"post\" action=\"" + url + "\"></form>");
							myForm.append(imageInput);
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
						$(this).dialog("destroy");
					}
				}
				]
			});
		},

		// renders the chart and the form with inputs for colors and symbols
		// for the first time
		renderChartAndForm: function(resp, formWindow, params) {
			var selectedColors = params != null ? params['selectedColors'] : null;
			var selectedSymbols = params != null ? params['selectedSymbols'] : null;

			var dataContent = resp.content;
			var colorPalette = window.chart.generateColorPalette(dataContent.data);
			var symbolPalette = window.chart.generateSymbolPalette();
			var symbolValues = [];
			var colorValues = [];

			var plotContainer = formWindow.find(".plot-container");
			var renderChoicesBody = plotContainer.find(".render-choices tbody");

			// a float attribute was used to determine class
			var isFloatCls = plotContainer.find(".float-cls-choices").length > 0;

			// fill the form with current color and symbol values
			$.each(dataContent.data, function(idx, series) {
				var colorCode = selectedColors ? selectedColors[idx] : colorPalette[idx].toLowerCase();
				colorValues.push(colorCode);

				var shapeSelect = $("<select></select>");
				$.each(symbolPalette, function(j, shape) {
					var selected;
					if (selectedSymbols) {
						selected = selectedSymbols[idx] == shape[0];
						if (selected) {
							symbolValues.push(shape[0]);
						}
					} else {
						selected = j == 0;
						if (selected) {
							symbolValues.push(symbolPalette[0][0]);
						}
					}
					var optionPattern = "<option value=\"{value}\" {selected}>{label}</option>";
					var args = {
						"value": shape[0],
						"selected": (selected ? "selected=\"selected\"": ""),
						"label": shape[1]
					}
					shapeSelect.append(window.utils.formatStr(optionPattern, args));
				});

				if (!isFloatCls) {
					var rowPattern = "<tr><td>{cls}</td>";
					rowPattern += "<td><div class=\"color-selector\" style=\"background-color: {colorCode};\"></div></td>";
					rowPattern += "<td class=\"hide\"><input type=\"hidden\" value=\"{colorCode}\"/></td>";
					var seriesRow = $(window.utils.formatStr(rowPattern, {
						"cls": series.group,
						"colorCode": colorCode
					}));

					var shapeCell = $("<td></td>");
					shapeCell.append(shapeSelect);
					seriesRow.append(shapeCell);

					// add color picker
					seriesRow.find('.color-selector').colpick({
						layout: 'rgbhex',
						color: colorCode,
						submitText: Translator.trans('OK', {}, 'ExperimentBundle'),
						onSubmit: function(hsb, hex, rgb, el) {
							var colorCode = '#' + hex;
							$(el).css('background-color', colorCode);
							$(el).colpickHide();
							$(el).closest("td").next().find("input").val(colorCode).trigger("change");
						}
					}).css('background-color', colorCode);

					renderChoicesBody.append(seriesRow);
				}
			});

			plotContainer.css("min-height", 400);
			plotContainer.css("position", "relative");
			// append to body temporarily in order for axes labels to be drawn correctly
			$("body").append(plotContainer);
			window.chart.renderChart(plotContainer, "body > .plot-container .results-container", dataContent, colorValues, symbolValues);
			//append to form after rendering because otherwise axes are not rendered
			formWindow.append(plotContainer);

			if (!isFloatCls) {
				// update image automatically on each color/shape change
				plotContainer.find(".render-choices select, .render-choices input").on("change", function() {
					window.chart.update(formWindow, window.chart.updateChartColorsSymbols, {
						renderChoices: formWindow.find(".plot-container .render-choices tbody tr")
					});
				});
			} else {
				var shapeSelector = plotContainer.find(".float-cls-choices select");
				shapeSelector.on("change", function() {
					for (i = 0; i < symbolValues.length; i++) {
						symbolValues[i] = $(this).val();
					}
					window.chart.update(formWindow, window.chart.renderChartAndForm, {
						"selectedSymbols": symbolValues
					});
				});
				shapeSelector.val(symbolValues[0]);
			}

			// only one of these tables in the selector will be present 
			var renderChoicesDataTable = plotContainer.find(".render-choices, .float-cls-choices").dataTable({
				"sScrollY": 400,
				"bScrollCollapse": true,
				"bInfo": false,
				"bPaginate": false,
				"bFilter": false,
				"bDestroy": true
			});

			formWindow.dialog("option", "buttons", window.chart.allButtons());
			formWindow.dialog("option", "minWidth", 650);
			formWindow.dialog("option", "close", function() {
				$(this).find("#point-tooltip").remove();
			});
		},

		mergeAttributeChoices: function(data, formWindow) {
			var x = formWindow.find(".attribute-choices select.x-attr").val();
			if (x != "-") {
				data['x'] = x;
			}
			var y = formWindow.find(".attribute-choices select.y-attr").val();
			if (y != "-") {
				data['y'] = y;
			}
			var cls = formWindow.find(".attribute-choices select.cls-attr").val();
			if (cls != "-") {
				data['cls'] = cls;
			}
		},

		cleanupColorpick: function() {
			$("html").off("mousedown");
			$(".colpick").remove(); // remove previous color pickers from the DOM
		},

		// update data from the server and call callback with parameters
		update: function(formWindow, callback, params) {
			var data = window.matrixView.getOutputParamDetails(formWindow);
			if (!data["dataset_url"]) {
				this.toUnconnectedState(formWindow);
				return;
			}
			this.mergeAttributeChoices(data, formWindow);

			formWindow.find(".plot-container").remove();
			var container = $("<div class=\"plot-container\"><img style=\"display: block; width: 250px; margin:auto;\" width=\"250px\" src=\"/bundles/damisexperiment/images/loading.gif\"/></div>");
			formWindow.append(container);
			$.ajax({
				url: Routing.generate('dataset_chart', {id : data["dataset_url"]}),
				data: data,
				context: container
			}).done(function(resp) {
				$(this).html(resp.html);
				window.chart.cleanupColorpick();
				$(this).find(".attribute-choices select").on("change", function() {
					// update image when attributes are changed
					window.chart.update(formWindow, window.chart.renderChartAndForm);
				});
				window.utils.showProgress();
				if (resp.status == "SUCCESS") {
					if (params) {
						callback(resp, formWindow, params);
					} else {
						callback(resp, formWindow);
					}
					formWindow.dialog("option", "buttons", window.chart.allButtons());
				} else {
					formWindow.dialog("option", "minHeight", "0");
					formWindow.dialog("option", "height", "auto");
					formWindow.dialog("option", "buttons", window.chart.errorButtons());
				}
				window.utils.hideProgress();
			});
		},

		// called when connection is deleted
		connectionDeleted: function(srcComponentType, targetComponentType, connectionParams) {
			if (srcComponentType == 'Chart' || targetComponentType == 'Chart') {
				var formWindow = $("#" + window.taskBoxes.getFormWindowId(connectionParams.iTaskBoxId));
				this.toUnconnectedState(formWindow);
			}
		},

		doubleClick: function(componentType, formWindow) {
			if (componentType == 'Chart') {
				formWindow.dialog("option", "minWidth", 650);
				formWindow.dialog("open");
				this.update(formWindow, this.renderChartAndForm);
			}
		}
	}
})();

