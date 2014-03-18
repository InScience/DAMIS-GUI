(function() {
    window.files = {
        taskBoxId : null,
        filePath : null,

        init: function(componentType, formWindow) {
            if (!(componentType in ['NewFile'])) {
                this.update(formWindow);
            }
        },

        update: function(formWindow) {

        }

    }
})();