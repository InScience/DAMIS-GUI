$(function () {

    // adds a flag that the input was changed by the user
    var titleChanged = function (ev) {
        if ($(this).val()) {
            $(this).addClass("changed");
        } else {
            $(this).removeClass("changed");
        }
    };


    var fileInput = $("input#datasets_newtype_file");
    var titleInput = $("input[name='datasets_newtype[datasetTitle]']");
    titleInput.on("change", titleChanged);
    fileInput.on("change", function (ev) {
        var fileName = $(this).val();
        // put the uploaded file name next to the upload button
        var stdFileName = fileName.replace(/\\/g, "/");
        var start = stdFileName.lastIndexOf("/") + 1;

        // prefill title input field with the uploaded file name
        // if the input had not been changed by the user
        if (!titleInput.hasClass("changed")) {
            var baseName = fileName.substring(start, stdFileName.lastIndexOf("."));
            titleInput.off("change");
            titleInput.val(baseName);
            titleInput.on("change", titleChanged);
        }
    });

});
