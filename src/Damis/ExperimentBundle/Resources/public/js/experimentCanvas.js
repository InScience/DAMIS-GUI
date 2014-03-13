;
(function() {
	window.experimentCanvas = {

		// initialize 
		init: function(spec) {

			jsPlumb.importDefaults({
				Container: spec.id,
				// default drag options
				DragOptions: {
					cursor: 'pointer',
					zIndex: 2000
				},
				// default to blue at one end and green at the other
				EndpointStyles: [{
					fillStyle: '#225588'
				},
				{
					fillStyle: '#558822'
				}],
				// blue endpoints 7 px; green endpoints 11.
				Endpoints: [["Dot", {
					radius: 7
				}], ["Dot", {
					radius: 11
				}]],
				// the overlays to decorate each connection with.  note that the label overlay uses a function to generate the label text; in this
				// case it returns the 'labelText' member that we set on each connection in the 'init' method below.
				ConnectionOverlays: [["Arrow", {
					location: 1
				}]]
			});

			// register native components as event observers
			window.eventObservers.populateEventObservers();

			jsPlumb.bind("connectionDetached", function(info, originalEvent) {
				// Clear the input parameter value and display it as input field
				var params = info.targetEndpoint.getParameters();
				var param = window.experimentForm.getParameter(params.iParamNo, params.iTaskBoxId);
				var srcRefField = window.experimentForm.getParameterSourceRef(param);
				srcRefField.val("");

				var connectionParams = info.connection.getParameters();
				var srcComponentType = window.componentSettings.getComponentDetails({
					boxId: connectionParams.oTaskBoxId
				})['type'];
				var targetComponentType = window.componentSettings.getComponentDetails({
					boxId: connectionParams.iTaskBoxId
				})['type'];
				$.each(window.eventObservers.eventObservers, function(idx, o) {
					if (o.connectionDeleted) {
						o.connectionDeleted(srcComponentType, targetComponentType, connectionParams);
					}
				});
			});

			// maps task box to its output endpoint connection
			// stores output parameter address into input parameter
			jsPlumb.bind("connection", function(info, originalEvent) {
				var conn = info.connection;
				var params = conn.getParameters();

				if ($(conn.source).hasClass("task-box")) {
					//display disabled field to the user
					var param = window.experimentForm.getParameter(params.iParamNo, params.iTaskBoxId);
					var srcRefField = window.experimentForm.getParameterSourceRef(param);
					srcRefField.val(params.oParamNo + "," + params.oTaskBoxId);

					//clear literal value field and hide it
					var valField = window.experimentForm.getParameterValue(param);
					valField.val("");
				}

				var srcComponentType = window.componentSettings.getComponentDetails({
					boxId: params.oTaskBoxId
				})['type'];
				var targetComponentType = window.componentSettings.getComponentDetails({
					boxId: params.iTaskBoxId
				})['type'];

				$.each(window.eventObservers.eventObservers, function(idx, o) {
					if (o.connectionEstablished) {
						o.connectionEstablished(srcComponentType, targetComponentType, params);
					}
				});
			});

			// listen for clicks on connections, and offer to delete connections on click.
			jsPlumb.bind("click", function(conn, originalEvent) {
				jsPlumb.detach(conn);
			});
		}
	};
})();

