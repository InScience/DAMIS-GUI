$(function() {
    var timer;
    var tooltips = $(".component-tooltip");

    $.each(tooltips, function(idx, el) {
        $(el).popover({
            html:true,
            container: "body",
            title: $(el).text() + "<i style=\"float: right; cursor: pointer;\" class=\"icon-remove\" onclick=\"$(&quot;.component-tooltip&quot;).popover(&quot;hide&quot;);\"></i>"
        });
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
                $(this).data('isShowing',"false");
                $(ev.currentTarget).parent().css('display','none');
            }, 2000);
        }
    }, '.popover-content, .popover-title');

    tooltips.on('mouseout',  function(ev){
        timer = setTimeout(function(){
            $(this).data('isShowing',"false");
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