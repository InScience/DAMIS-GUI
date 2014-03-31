(function() {
    window.params = {
        container : [],

        addParam : function(taskBoxId, parameterId, parameterValue) {
            taskBoxId = this.filterName(taskBoxId);
            if(this.container[taskBoxId] == undefined)
                this.container[taskBoxId] = [];

            var updated = false;
            this.container[taskBoxId].forEach(function(row, index){
                if(row.id == parameterId) {
                    window.params.container[taskBoxId][index] =
                    {id : parameterId,
                     value : parameterValue};
                    updated = true;
                }
            });

            if(!updated) {
                this.container[taskBoxId].push(
                    {id : parameterId,
                    value : parameterValue});
            }
        },

        getParams : function(taskBoxId) {
            taskBoxId = this.filterName(taskBoxId);
            return (this.container[taskBoxId] == undefined) ? [] : this.container[taskBoxId];
        },

        filterName : function(taskBoxId) {
            return /\d+/g.exec(taskBoxId)[0];
        },

        setParams : function(taskBoxId, parameters) {
            taskBoxId = this.filterName(taskBoxId);
            if(this.container[taskBoxId] == undefined)
                this.container[taskBoxId] = [];

            this.container[taskBoxId] = parameters;
        }
    }
})();
