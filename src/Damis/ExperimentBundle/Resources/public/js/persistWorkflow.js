;
(function() {
	window.persistWorkflow = {

		// persists jsPlumb entities to a string
		persistJsPlumbEntities: function() {

			// persist boxes together with their endpoints
			var boxes = {};
			$.each($(".task-box"), function(idx, box) {
				var $box = $(box);
				var boxId = $box.attr("id");

				var endpoints = [];
				$.each(jsPlumb.getEndpoints(boxId), function(eIdx, e) {
                    var type;
                    if(e.anchor.type == undefined)
                        type = [e.anchor.x, e.anchor.y, 0, 0, 0, 0, e.anchor.cssClass];
                    else
                        type = e.anchor.type;

					endpoints.push({
						parameters: e.getParameters(),
						anchor: type,
						isTarget: e.isTarget
					});
				});

				boxes[boxId] = {
					boxId: boxId,
					x: parseInt($box.css("left"), 10),
					y: parseInt($box.css("top"), 10),
					endpoints: endpoints,
                    componentId: $box.attr('data-componentid'),
                    form_parameters: window.params.getParams(boxId)
				};
			});

			// persist connections
			var connections = [];
			$.each(jsPlumb.getConnections(), function(idx, connection) {
				var sourceId = connection.sourceId;
				var targetId = connection.targetId;
				var sourceAnchor, targetAnchor;
				if (connection.endpoints[0].elementId == sourceId) {
					sourceAnchor = connection.endpoints[0].anchor;
					targetAnchor = connection.endpoints[1].anchor;
				} else {
					targetAnchor = connection.endpoints[0].anchor;
					sourceAnchor = connection.endpoints[1].anchor;
				}
				connections.push({
					connectionId: connection.id,
					sourceBoxId: sourceId,
					targetBoxId: targetId,
					params: connection.getParameters(),
					sourceAnchor: sourceAnchor,
					targetAnchor: targetAnchor
				});
			});
			var boxesStr = JSON.stringify(boxes);
			var connectionsStr = JSON.stringify(connections);
			var persistedStr = boxesStr + "***" + connectionsStr + "***" + window.taskBoxes.countBoxes;
			return persistedStr;
		},

		restoreBoxes: function(persistedStr) {
			var parts = persistedStr.split("***");
			var boxes = JSON.parse(parts[0]);

			$.each(boxes, function(idx, box) {
                var componentSettings = window.componentSettings.getComponentDetails({
                    componentId: box['componentId']
                });
				var taskBox = $(window.taskBoxes.assembleBoxHTML(componentSettings['label'], componentSettings['ico'], componentSettings['cluster_ico'], box['componentId']));
				taskBox.attr("id", box['boxId']);
				taskBox.appendTo($("#flowchart-container"));
				taskBox.css("left", box['x'] + "px");
				taskBox.css("top", box['y'] + "px");
                window.params.setParams(box['boxId'], box.form_parameters);

				$.each(box['endpoints'], function(i, e) {
					var endpoint = window.endpoints.addEndpoint(e.isTarget, box['boxId'], e.anchor, e.parameters);
				});
			});
		},

		restoreConnections: function(persistedStr) {
			var parts = persistedStr.split("***");
			var connections = JSON.parse(parts[1]);

			$.each(connections, function(idx, conn) {
				var sourceParams = {
					oParamNo: conn.params['oParamNo'],
					oTaskBoxId: conn.params['oTaskBoxId']
				};
				var targetParams = {
					iParamNo: conn.params['iParamNo'],
					iTaskBoxId: conn.params['iTaskBoxId']
				};

				var sourceEndpoint;
				$.each(jsPlumb.getEndpoints(conn.sourceBoxId), function(eIdx, e) {
					match = ! e.isTarget && e.getParameters()["oParamNo"] == conn.params["oParamNo"];
					if (match) {
						sourceEndpoint = e;
						return false;
					}
				});

				var targetEndpoint;
				$.each(jsPlumb.getEndpoints(conn.targetBoxId), function(eIdx, e) {
					match = e.isTarget && e.getParameters()["iParamNo"] == conn.params["iParamNo"];
					if (match) {
						targetEndpoint = e;
						return false;
					}
				});

				jsPlumb.connect({
					source: sourceEndpoint,
					target: targetEndpoint
				});
			});
		},

		restoreCountBoxes: function(persistedStr) {
			var parts = persistedStr.split("***");
			var boxes = JSON.parse(parts[0]);
			window.taskBoxes.countBoxes = parseInt(parts[2]);
		}
	}
})();

