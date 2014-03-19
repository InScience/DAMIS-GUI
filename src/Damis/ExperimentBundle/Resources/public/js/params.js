(function() {
    window.params = {
        container : [],

        addParam : function(taskBoxId, parameterId, parameterValue) {
            taskBoxId = this.filterName(taskBoxId);
            if(this.container[taskBoxId] == undefined)
                this.container[taskBoxId] = [];

            this.container[taskBoxId][parameterId] =
                {id : parameterId,
                value : parameterValue};
        },

        getParams : function(taskBoxId) {
            taskBoxId = this.filterName(taskBoxId);
            return (this.container[taskBoxId] == undefined) ? [] : this.container[taskBoxId];
        },

        filterName : function(taskBoxId) {
            return /\d+/g.exec(taskBoxId)[0];
        }
    }
})();
