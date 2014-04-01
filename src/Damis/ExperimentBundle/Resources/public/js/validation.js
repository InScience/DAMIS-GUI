(function() {
	window.validation = {
		validate: function() {
            var persisted = window.persistWorkflow.persistJsPlumbEntities();
            var parts = persisted.split("***");
            var boxes = JSON.parse(parts[0]);
            var error = false;
            for(var box in boxes){
                var type = window.componentSettings.getComponentDetails({boxId : box})['type'];
                if(type === "UploadedFile" || type === "NewFile"){
                    if(!error)
                        error = this.validateFileChosen(box);
                    else
                        this.validateFileChosen(box);
                }
            }
            if (error)
                return false;
            for(var box in boxes){
                if(!error)
                    error = this.validateConnections(box);
                else
                    this.validateFileChosen(box);
            }
            if (error)
                return false;
            error = this.validateFormErrors();
            if (error)
                return false;
		},

		validateFileChosen: function(box) {
            var parameters = JSON.stringify(window.params.getParams(box));
            if(JSON.parse(parameters)[0]){
                if (!JSON.parse(parameters)[0].value){
                    $('.existing-file-error').show();
                    $('#' + box).addClass('error');
                    return true;
                }
                else{
                    $('#' + box).removeClass('error');
                    $('.existing-file-error').hide();
                    return false;
                }
            } else {
                $('.existing-file-error').show();
                $('#' + box).addClass('error');
                return true;
            }
		},

        validateConnections: function(box) {
            var src = jsPlumb.getConnections({source:[box]});
            var trg = jsPlumb.getConnections({target:[box]});
            if(src == false && trg == false){
                $('#' + box).addClass('error');
                return true;
            }
            else{
                $('#' + box).removeClass('error');
                return false;
            }
		},

		validateFormErrors: function() {
            $.each($(".task-box"), function(taskBoxId, taskBox) {
               var tb = $('#task-box-'+taskBoxId+'-form');
            //    tb.parent('.ui-dialog').find('.ui-dialog-buttonset').find('.submit').click();
                if(tb.find('.dynamic-container').find('ul li').length > 0){
                    $(taskBox).addClass("error");
                } else {
                    var type = window.componentSettings.getComponentDetails({boxId : 'task-box-'+taskBoxId})['type'];
                    if(type !== "UploadedFile" && type !== "NewFile"){
                        $(taskBox).removeClass("error");
                    }
                }

            });
		}
	}
})();

