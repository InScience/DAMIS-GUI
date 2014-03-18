(function() {
	window.eventObservers = {
		// native components that are interested in events
		eventObservers: [],

		// collect native components as event observers
		populateEventObservers: function() {
			this.eventObservers.push(window.files);
			this.eventObservers.push(window.chart);
			this.eventObservers.push(window.technicalDetails);
			this.eventObservers.push(window.matrixView);
			this.eventObservers.push(window.existingFile);
            this.eventObservers.push(window.componentForm);
		}
	}
})();

