$(function() {
    var timer;
    var tooltips = $(".component-tooltip");
    var tooltip;

    $.each(tooltips, function(idx, el) {
        $(el).popover({
            html:true,
            container: "body",
            // no inline style/onclick: Bootstrap >= 3.4 sanitizes popover HTML and strips them
            title: $(el).text() + "<i class=\"glyphicon glyphicon-remove component-popover-close\"></i>"
        });
    });
    $(document).on("click", ".component-popover-close", function() {
        $(".component-tooltip").popover("hide");
    });
    tooltips.on("click", function(ev) {

        $.each($(".component-tooltip"), function(idx, tooltip) {
            if (tooltip != ev.currentTarget) {
                $(tooltip).popover("hide");
            }
        });
    });

    $(document).on({

        mouseenter: function () {
            clearTimeout(timer);
        },

        mouseleave: function (ev) {
            timer = setTimeout(function(){
                $.each($(".component-tooltip"), function(idx, tooltip) {
                     $(tooltip).popover("hide");
                });
            }, 2000);
        }
    }, '.popover-content, .popover-title');

    tooltips.on('mouseout',  function(ev){
        timer = setTimeout(function(){
            $(ev.currentTarget).popover('hide');
        }, 2000);
    });


    // Tabs init
    $("#cluster-tabs li").click(function(){
        $("#cluster-tabs .active").removeClass("active");
        $("#toolbox div[id$=panel]").hide();

        $(this).addClass("active");
        var clusterId = /\d+/g.exec($(this).attr("id"));
        var activePanel = $("#cluster-"+ clusterId +"-panel");
        activePanel.show();
    });

    $("#toolbox > div").accordion({
       heightStyle: "content"
   });

});