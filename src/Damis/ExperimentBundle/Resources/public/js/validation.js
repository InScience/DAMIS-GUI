(function() {
	window.validation = {
		validate: function() {
            $('#fileMoreThan1').remove();
            $('#fileNotExistMsg').remove();
            $('#fileChooseMsg').remove();
            $('#connectionsMsg').remove();
            $('#formErrorsMsg').remove();
            $('#loopsMsg').remove();
            var persisted = window.persistWorkflow.persistJsPlumbEntities();
            var parts = persisted.split("***");
            var boxes = JSON.parse(parts[0]);
            var error = false;
            var fileNumbers = 0;
            for(var box in boxes){
                $('#' + box).removeClass('error');
                var type = window.componentSettings.getComponentDetails({componentId : boxes[box].componentId})['type'];
                if(type === "UploadedFile" || type === "NewFile"){
                    fileNumbers++;
                    if(!error)
                        error = this.validateFileChosen(box);
                    else
                        this.validateFileChosen(box);
                }
            }

            if(fileNumbers > 1){
                $('div.container').find('.tabbable .tab-content')
                    .prepend('<div id="fileMoreThan1" class="alert alert-danger">'+Translator.trans('Too many file components', {}, 'ExperimentBundle')+'</div>');
                return false;
            }

            if(fileNumbers == 0){
                $('div.container').find('.tabbable .tab-content')
                    .prepend('<div id="fileNotExistMsg" class="alert alert-danger">'+Translator.trans('File component is required', {}, 'ExperimentBundle')+'</div>');
                return false;
            }

            if (error){
                $('div.container').find('.tabbable .tab-content')
                    .prepend('<div id="fileChooseMsg" class="alert alert-danger">'+Translator.trans('Please select file', {}, 'ExperimentBundle')+'</div>');
                return false;
            }

            for(var box in boxes){
                var type = window.componentSettings.getComponentDetails({componentId : boxes[box].componentId})['type'];
                if(type !== "UploadedFile" && type !== "NewFile"){
                    if(!error)
                        error = this.validateConnections(box);
                    else
                        this.validateConnections(box);
                }
            }

            if (error){
                $('div.container').find('.tabbable .tab-content')
                    .prepend('<div id="connectionsMsg" class="alert alert-danger">'+Translator.trans('Not all components are connected', {}, 'ExperimentBundle')+'</div>');
                return false;
            }

            error = this.validateFormErrors();

            if (error){
                $('div.container').find('.tabbable .tab-content')
                    .prepend('<div id="formErrorsMsg" class="alert alert-danger">'+Translator.trans('There are errors in forms', {}, 'ExperimentBundle')+'</div>');
                return false;
             }

            for(var box in boxes){
                var type = window.componentSettings.getComponentDetails({componentId : boxes[box].componentId})['type'];
                if(type !== "UploadedFile" && type !== "NewFile"){
                    if(!error)
                        error = this.validateLoops(box);
                    else
                        this.validateLoops(box);
                }
            }

            if (error){
                $('div.container').find('.tabbable .tab-content')
                    .prepend('<div id="loopsMsg" class="alert alert-danger">'+Translator.trans('There are loops', {}, 'ExperimentBundle')+'</div>');
                return false;
            }

            return true;
		},

		validateFileChosen: function(box) {
            var parameters = JSON.stringify(window.params.getParams(box));
            if(JSON.parse(parameters)[0]){
                if (!JSON.parse(parameters)[0].value){
                    $('#' + box).addClass('error');
                    return true;
                }
                else{
                    $('#' + box).removeClass('error');
                    return false;
                }
            } else {
                $('#' + box).addClass('error');
                return true;
            }
		},

        validateConnections: function(box) {
            var trg = jsPlumb.getConnections({target:[box]});
            if(trg == false){
                $('#' + box).addClass('error');
                return true;
            }
            else{
                $('#' + box).removeClass('error');
                return false;
            }
		},

        validateLoops: function(box) {
            var src = jsPlumb.getConnections({source:[box]});
            if(src != false){
                if(src[0].sourceId == src[0].targetId){
                    $('#' + box).addClass('error');
                    return true;
                }
                else {
                    $('#' + box).removeClass('error');
                    return false;
                }
            }
		},

		validateFormErrors: function() {
            var error = false;
            $.each($(".task-box"), function(key, taskBox) {
                var tb = $('#' + $(taskBox).attr('id') + '-form');
                var type = window.componentSettings.details[$(taskBox).attr('data-componentid')]['type'];
                if(type !== "UploadedFile" && type !== "NewFile"){
                    if(tb.find('.dynamic-container').find('ul li').length > 0){
                        $(taskBox).addClass("error");
                        error = true;
                    } else {
                            $(taskBox).removeClass("error");
                    }
                }

            });
            return error;
		}
	}
})();

