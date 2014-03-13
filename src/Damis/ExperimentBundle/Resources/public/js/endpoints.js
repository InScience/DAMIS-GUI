;
(function() {
	window.endpoints = {
		// this is the paint style for the connecting lines..
		getConnectorPaintStyle: function() {
			return {
				lineWidth: 4,
				strokeStyle: "#cccccc",
				joinstyle: "round",
				outlineColor: "#eaedef",
				outlineWidth: 2
			}
		},

		// .. and this is the hover style. 
		getConnectorHoverStyle: function() {
			return {
				lineWidth: 4,
				strokeStyle: "#5C96BC",
				outlineWidth: 2,
				outlineColor: "white"
			}
		},

		getEndpointHoverStyle: function() {
			return {
				fillStyle: "#5C96BC"
			}
		},

		// the definition of source endpoints (the small blue ones)
		getSourceEndpoint: function() {
			return {
				endpoint: "Dot",
				paintStyle: {
					fillStyle: "#346789",
					radius: 7
				},
				isSource: true,
				connector: ["StateMachine", {
					stub: [40, 60],
					gap: 10,
					cornerRadius: 5,
					alwaysRespectStubs: true
				}],
				connectorStyle: this.getConnectorPaintStyle(),
				hoverPaintStyle: this.getEndpointHoverStyle(),
				connectorHoverStyle: this.getConnectorHoverStyle(),
				dragOptions: {}
			}
		},

		// a source endpoint that sits at BottomCenter
		// bottomSource : jsPlumb.extend( { anchor:"BottomCenter" }, sourceEndpoint),
		// the definition of target endpoints (will appear when the user drags a connection) 
		getTargetEndpoint: function() {
			return {
				endpoint: "Dot",
				paintStyle: {
					strokeStyle: "#346789",
					fillStyle: "transparent",
					radius: 5,
					lineWidth: 2
				},
				hoverPaintStyle: this.getEndpointHoverStyle(),
				dropOptions: {
					hoverClass: "hover",
					activeClass: "active-target"
				},
				isTarget: true
			}
		},

		addEndpoint: function(isTarget, box, anchor, parameters) {
			var endpoint;
			if (isTarget) {
				endpoint = jsPlumb.addEndpoint(box, this.getTargetEndpoint(), {
					anchor: anchor,
                    maxConnections: 1,
					parameters: parameters
				});
			} else {
				endpoint = jsPlumb.addEndpoint(box, this.getSourceEndpoint(), {
					anchor: anchor,
                    maxConnections: -1,
					parameters: parameters
				});
			}
			return endpoint;
		}
	}
})();

