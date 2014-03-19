(function() {
    window.componentNoForm = {
        taskBoxId : null,
        filePath : null,
        url : null,
        valid : false,
        id : false,

        init: function(componentType, formWindow) {
            if (componentType == 'NoForm') {
                this.update(formWindow);
            }
        },

        update: function(dialog) {
            var form = dialog.find(".dynamic-container");
            if (form.length == 0)
                var form = $("<div class=\"dynamic-container\"></div>");
            var componentInput = dialog.find(".component-selection select");

            dialog.append(form);
            dialog.closest(".ui-dialog").find("button").attr("disabled", "disabled");
            this.id = componentInput.val();

            var buttons = window.componentNoForm.allButtons();
            dialog.dialog("option", "buttons", buttons);
            dialog.dialog("option", "min-width", 0);
            dialog.dialog("option", "width", "auto");
        },

        allButtons: function() {
            var buttons = [{
                "text": Translator.trans('OK', {}, 'ExperimentBundle'),
                "class": "btn btn-primary",
                "click": function(ev) {
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
})();