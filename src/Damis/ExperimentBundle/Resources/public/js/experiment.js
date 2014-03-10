$(function() {
    $("#cluster-tabs li").click(function(){
        $("#cluster-tabs .active").removeClass("active");
        $("#toolbox div[id$=panel]").hide();

        $(this).addClass("active");
        var clusterId = /\d+/g.exec($(this).attr("id"));
        var activePanel = $("#cluster-"+ clusterId +"-panel");
        activePanel.show();
    });
});