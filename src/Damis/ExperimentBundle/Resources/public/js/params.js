(function() {
    window.params = {
        container : [],

        addParam : function(taskBoxId, parameterId, parameterValue) {
            if(this.container[taskBoxId] == undefined)
                this.container[taskBoxId] = [];

            this.container[taskBoxId][parameterId] =
                {id : parameterId,
                value : parameterValue};
        },

        getParams : function(taskBoxId) {
            taskBoxId = /\d+/g.exec(taskBoxId)[0];
            return (this.container[taskBoxId] == undefined) ? [] : this.container[taskBoxId];
        }
    }
})();
