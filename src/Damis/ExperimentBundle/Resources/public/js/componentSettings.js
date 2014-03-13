;
(function() {
	window.componentSettings = {
		getComponentDetails: function(params) {
			var formWindow;
			var componentId;
			if (params['componentId']) {
				componentId = params['componentId'];
			} else {
				if (params['boxId']) {
					formWindow = $("#" + window.taskBoxes.getFormWindowId(params['boxId']));
				} else if (params['formWindowId']) {
					formWindow = $("#" + params['formWindowId']);
				} else if (params['formWindow']) {
					formWindow = params['formWindow'];
				}
				var componentInput = $(".component-selection select");
				componentId = componentInput.val();
			}
			return this.details[componentId];
		}
	}
})();

