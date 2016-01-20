/* Flot plugin that adds some extra coordinate axes to flot chart.

 Copyright (c) 2013 http://zizhujy.com.
 Licensed under the MIT license.

 Usage:
 Inside the <head></head> area of your html page, add the following lines:

 <script type="text/javascript" src="http://zizhujy.com/Scripts/flot/jquery.flot.coordinate.js"></script>

 Now you are all set. This plugin will draw a default coordinate axes for you.

 Online examples:
 http://zizhujy.com/Plotter is using it, you can take a look to get the first impression about what this plugin can do.

 Dependencies:
 jquery.js
 jquery.flot.js

 Customizations:
 The coordinate type are accessed as strings separated by pipe line '|' through the coordinate option:

 options{
 coordinate: {
 type: 'rectangular|default'
 }
 }
 */

(function ($) {
    var classes = null;
    var surface = null;
    var theLayer = "";
    var theFont = "";

    function processCoordinates(plot, canvascontext) {
        var handlers = {
            "default": function (plot, ctx) {
                setAxesVisibility(plot, true);
                surface = new classes.Canvas("flot-base", plot.getPlaceholder());
                handleRatioXY(plot);
                drawAxiesAndArrows(plot, ctx);
                $(".flot-text").show();
            },

            rectangular: function (plot, ctx) {

                function measureTickLabels(axis) {

                    setAxesVisibility(plot, true);
                    var opts = axis.options, ticks = axis.ticks || [];
                    var axisw = opts.labelWidth || 0, axish = opts.labelHeight || 0;
                    var legacyStyles = axis.direction + "Axis " + axis.direction + axis.n + "Axis";
                    var layer = "flot-" + axis.direction + "-axis flot-" + axis.direction + axis.n + "-axis " + legacyStyles;
                    var font = opts.font || "flot-tick-label tickLabel";

                    for (var i = 0; i < ticks.length; ++i) {

                        var t = ticks[i];

//                        if (!t.label)
//                            continue;

                        var info = surface.getTextInfo(layer, t.label, font);

                        if (opts.labelWidth == null)
                            axisw = Math.max(axisw, info.width);
                        if (opts.labelHeight == null)
                            axish = Math.max(axish, info.height);
                    }

                    axis.labelWidth = Math.ceil(axisw);
                    axis.labelHeight = Math.ceil(axish);

                    theLayer = layer;
                    theFont = font;
                }

                // round to nearby lower multiple of base
                function floorInBase(n, base) {
                    return base * Math.floor(n / base);
                }

                function tickGenerator(axis) {
                    var opts = axis.options;

                    // estimate number of ticks
                    var noTicks;
                    if (typeof opts.ticks == "number" && opts.ticks > 0)
                        noTicks = opts.ticks;
                    else
                    // heuristic based on the model a*sqrt(x) fitted to
                    // some data points that seemed reasonable
                        noTicks = 0.3 * Math.sqrt(axis.direction == "x" ? surface.width : surface.height);

                    var delta = (axis.max - axis.min) / noTicks;
                    var dec = -Math.floor(Math.log(delta) / Math.LN10);
                    var maxDec = opts.tickDecimals;

                    if (maxDec != null && dec > maxDec) {
                        dec = maxDec;
                    }

                    var magn = Math.pow(10, -dec);
                    var norm = delta / magn; // norm is between 1.0 and 10.0
                    var size;

                    if (norm < 1.5) {
                        size = 1;
                    } else if (norm < 3) {
                        size = 2;
                        // special case for 2.5, requires an extra decimal
                        if (norm > 2.25 && (maxDec == null || dec + 1 <= maxDec)) {
                            size = 2.5;
                            ++dec;
                        }
                    } else if (norm < 7.5) {
                        size = 5;
                    } else {
                        size = 10;
                    }

                    size *= magn;

                    if (opts.minTickSize != null && size < opts.minTickSize) {
                        size = opts.minTickSize;
                    }

                    axis.delta = delta;
                    axis.tickDecimals = Math.max(0, maxDec != null ? maxDec : dec);
                    axis.tickSize = Math.min(opts.tickSize, size);

                    // Time mode was moved to a plug-in in 0.8, but since so many people use this
                    // we'll add an especially friendly make sure they remembered to include it.

                    if (opts.mode == "time" && !axis.tickGenerator) {
                        throw new Error("Time mode requires the flot.time plugin.");
                    }

                    // Flot supports base-10 axes; any other mode else is handled by a plug-in,
                    // like flot.time.js.

                    var ticks = [],
                        start = floorInBase(axis.min, axis.tickSize),
                        i = 0,
                        v = Number.NaN,
                        prev;

                    do {
                        prev = v;
                        v = start + i * axis.tickSize;
                        ticks.push({ v: v, label: v });
                        ++i;
                    } while (v < axis.max && v != prev);

                    measureTickLabels(axis);

                    return ticks;
                }

                function drawAxisTicksAndLabels(plot, ctx, axis) {
                    axis.ticks = tickGenerator(axis);

                    ctx.save();
                    var offset = plot.getPlotOffset();
                    ctx.translate(offset.left, offset.top);
                    // Avoid using pointOffset()! Use plot.p2c() instead.
                    //var origin = plot.pointOffset({ x: 0, y: 0 });
                    var origin = plot.p2c({ x: 0, y: 0 });
                    ctx.textAlign = "end";
                    ctx.textBaseline = "middle";

                    if (!!axis.ticks && !!axis.ticks.length) {
                        for (var i = 0; i < axis.ticks.length; i++) {
                            var tick = axis.ticks[i];
                            if (tick.v < axis.min || tick.v > axis.max) {
                                continue;
                            }

                            // Calculations for ticks
                            var aPos = axis.p2c(tick.v);
                            var xFrom = 0;
                            var xTo = 0;
                            var yFrom = 0;
                            var yTo = 0;

                            if (axis.direction == "x") {
                                xFrom = aPos;
                                xTo = xFrom;
                                yFrom = 0;
                                yTo = plot.height();
                            } else {
                                xFrom = 0;
                                xTo = plot.width();
                                yFrom = aPos;
                                yTo = yFrom;
                            }

                            // Draw tick
                            ctx.beginPath();
                            ctx.strokeStyle = axis.options.tickColor;
                            ctx.moveTo(xFrom, yFrom);
                            ctx.lineTo(xTo, yTo);
                            ctx.stroke();

                            // Draw label
                            if (typeof tick.label != "undefined") {
                                var labelWidth = axis.labelWidth;
                                var labelHeight = axis.labelHeight;
                                if (!!theLayer && !!theFont) {
                                    var info = surface.getTextInfo(theLayer, tick.label, theFont);
                                    labelWidth = info.width;
                                    labelHeight = info.height;
                                }

                                var x = 0;
                                var y = 0;
                                if (axis.direction == "x") {
                                        x = aPos + labelWidth / 2;
                                        y = origin.top + labelHeight / 2;

                                } else {

                                        x = origin.left - labelWidth / 2;
                                        y = aPos;

                                }
                                ctx.fillStyle = options.grid.borderColor; // axis.options.color;
                                ctx.strokeStyle = options.grid.borderColor; // || axis.options.tickColor || $.color.parse(axis.options.color).scale("a", 0.22).toString();
                                ctx.beginPath();
                                if(axis.direction == "x")
                                    y = 579;
                                if(axis.direction == "y")
                                    x = -2;								
                                ctx.fillText(tick.label, x, y);
                                //ctx.fillRect(x, y, -10, -10);
                                ctx.stroke();
                            }
                        }
                    }

                    ctx.restore();
                }

                setAxesVisibility(plot, true);

                var options = plot.getOptions();

                var axes = plot.getAxes();
                var xaxis = axes.xaxis;
                var yaxis = axes.yaxis;
                // Make the flot.setupGrid() update the min and max to the latest
                if (typeof options != "undefined") {
                    if (typeof xaxis.min != "undefined") options.xaxis.min = xaxis.min;
                    if (typeof xaxis.max != "undefined") options.xaxis.max = xaxis.max;

                    if (typeof yaxis.min != "undefined") options.yaxis.min = yaxis.min;
                    if (typeof yaxis.max != "undefined") options.yaxis.max = yaxis.max;
                }

                $.extend(true, options.xaxis, { min: xaxis.min, max: xaxis.max });
                $.extend(true, options.yaxis, { min: yaxis.min, max: yaxis.max });
                // Make the the options under options.xaxis set inside this plugin be available in the future. For example, options.xaxis.tickSize
                $.extend(true, xaxis, options.xaxis);
                $.extend(true, yaxis, options.yaxis);

                // To use plot.p2c() in the future, we must set axis.used = true:
                xaxis.used = true;
                yaxis.used = true;

                handleRatioXY(plot);

                //surface = plot.getSurface();
                surface = new classes.Canvas("flot-coordinate", plot.getPlaceholder());
                $(".flot-coordinate").remove().insertBefore($(".flot-overlay"));

                // Draw coordinates

                ctx.save();
                ctx.strokeStyle = options.grid.borderColor;
                ctx.lineWidth = 1;

                var plotOffset = plot.getPlotOffset();
                ctx.translate(plotOffset.left, plotOffset.top);

                ctx.beginPath();

                ctx.moveTo(xaxis.p2c(xaxis.min), yaxis.p2c(0));
                ctx.lineTo(xaxis.p2c(xaxis.max), yaxis.p2c(0));
                ctx.stroke();

                ctx.moveTo(xaxis.p2c(0), yaxis.p2c(yaxis.min));
                ctx.lineTo(xaxis.p2c(0), yaxis.p2c(yaxis.max));
                ctx.stroke();

                ctx.restore();

                // Draw ticks and labels
                drawAxisTicksAndLabels(plot, ctx, xaxis);
                drawAxisTicksAndLabels(plot, ctx, yaxis);
            },

            auto: function (plot, ctx) {
                var axes = plot.getAxes();
                var xaxis = axes.xaxis;
                var yaxis = axes.yaxis;

                if (xaxis.min <= 0 && xaxis.max >= 0 &&
                    yaxis.min <= 0 && yaxis.max >= 0) {

                    this["rectangular"] && this.rectangular(plot, ctx);
                } else {
                    this["default"] && this["default"](plot, ctx);
                }
            }
        };

        var options = plot.getOptions();

        var coords = options.coordinate.type.split("|");

        var $flotText = $(".flot-text");
        $flotText.hide();

        //$(".flot-text").filter(function(){return $(this).html() !== "";}).remove();

        for (var i = 0; i < coords.length; i++) {
            var s = coords[i];

            if (handlers[s]) {
                // if($(".flot-text").length <= 0) {
                //     $flotText.insertAfter(".flot-base");
                // }
                handlers[s](plot, canvascontext, $flotText);
            }
        }
    }

    function setAxesVisibility(plot, show) {
        var options = plot.getOptions();
        $.extend(true, options, { xaxis: { show: true, reserveSpace: true }, yaxis: { show: true, reserveSpace: true } });

        var xaxes = plot.getXAxes();
        var yaxes = plot.getYAxes();

        $.each(xaxes, function (index, xaxis) {
            xaxis.show = show;
            xaxis.reserveSpace = show;

            xaxis.options.show = show;
            xaxis.options.reserveSpace = show;
        });

        $.each(yaxes, function (index, yaxis) {
            yaxis.show = show;
            yaxis.reserveSpace = show;

            yaxis.options.show = show;
            yaxis.options.reserveSpace = show;
        });
    }

    function handleRatioXY(plot) {
        // Handle axes ratio
        var options = plot.getOptions();
        var axes = plot.getAxes();
        var xaxis = axes.xaxis;
        var yaxis = axes.yaxis;

        if (options.coordinate.ratioXY) {
            $.extend(true, xaxis.options, { min: xaxis.min, max: xaxis.max });
            $.extend(true, yaxis.options, { min: yaxis.min, max: yaxis.max });

            var ratio = parseFloat(options.coordinate.ratioXY);
            // deltaX / deltaY = ratioXY * width / height
            xaxis.options = xaxis.options || {};
            if (!xaxis.options.max) xaxis.options.max = 10;
            if (!xaxis.options.min) xaxis.options.min = -10;
            var deltaX = xaxis.options.max - xaxis.options.min;
            var deltaY = deltaX / (ratio * plot.width() / plot.height());

            yaxis.options = yaxis.options || {};
            if (!yaxis.options.max) yaxis.options.max = 10;
            if (!yaxis.options.min) yaxis.options.min = -10;
            var r = (yaxis.options.max - yaxis.options.min) / deltaY;

            // Adjust y-axis based on the ratio and x-axis
            yaxis.options.max = yaxis.options.max / r;
            yaxis.options.min = yaxis.options.min / r;

            $.extend(true, options, { xaxis: { options: { min: xaxis.options.min, max: xaxis.options.max } }, yaxis: { options: { min: yaxis.options.min, max: yaxis.options.max } } });
            plot.setupGrid();
            //alert("coor " + xaxis.options.min);
        }
    }

    function drawAxiesAndArrows(plot, args) {
        var options = plot.getOptions();

        var axes = plot.getAxes();
        var xaxis = axes.xaxis;
        var yaxis = axes.yaxis;

        var ctx = plot.getCanvas().getContext("2d");

        // Draw coordinates
        ctx.save();
        ctx.strokeStyle = options.grid.borderColor;
        ctx.lineWidth = 1;

        var plotOffset = plot.getPlotOffset();
        ctx.translate(plotOffset.left, plotOffset.top);

        ctx.beginPath();
        ctx.moveTo(xaxis.p2c(xaxis.min) - 1, yaxis.p2c(yaxis.min));
        ctx.lineTo(xaxis.p2c(xaxis.max), yaxis.p2c(yaxis.min));
        ctx.closePath();
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(xaxis.p2c(xaxis.min), yaxis.p2c(yaxis.min) - 1);
        ctx.lineTo(xaxis.p2c(xaxis.min), yaxis.p2c(yaxis.max));
        ctx.closePath();
        ctx.stroke();

        // Draw arrow
        //var o = plot.pointOffset({ x: xaxis.max, y: 0 });
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(xaxis.p2c(xaxis.max) + 1, yaxis.p2c(yaxis.min));
        ctx.lineTo(xaxis.p2c(xaxis.max) + 1 - 15, yaxis.p2c(yaxis.min) - 7);
        ctx.lineTo(xaxis.p2c(xaxis.max) + 1 - 10, yaxis.p2c(yaxis.min));
        ctx.lineTo(xaxis.p2c(xaxis.max) + 1 - 15, yaxis.p2c(yaxis.min) + 7);
        ctx.lineTo(xaxis.p2c(xaxis.max) + 1, yaxis.p2c(yaxis.min));
        ctx.closePath();
        ctx.fillStyle = options.grid.borderColor;
        ctx.fill();

        ctx.beginPath();
        ctx.moveTo(xaxis.p2c(xaxis.min), yaxis.p2c(yaxis.max) - 1);
        ctx.lineTo(xaxis.p2c(xaxis.min) - 7, yaxis.p2c(yaxis.max) - 1 + 15);
        ctx.lineTo(xaxis.p2c(xaxis.min), yaxis.p2c(yaxis.max) - 1 + 10);
        ctx.lineTo(xaxis.p2c(xaxis.min) + 7, yaxis.p2c(yaxis.max) - 1 + 15);
        ctx.lineTo(xaxis.p2c(xaxis.min), yaxis.p2c(yaxis.max) - 1);
        ctx.closePath();
        ctx.fillStyle = options.grid.borderColor;
        ctx.fill();
        ctx.restore();
    }

    function init(plot, theClasses) {
        classes = theClasses;
        plot.hooks.drawBackground.push(processCoordinates);
        plot.hooks.bindEvents.push(function () {
            var options = plot.getOptions();
            if (!!options.xaxis.highlightTick && !!options.xaxis.highlightColor) {
                $(".flot-x-axis ." + options.xaxis.highlightTick).css({ "color": options.xaxis.highlightColor });
            }
            if (!!options.yaxis.highlightTick && !!options.yaxis.highlightColor) {
                $(".flot-y-axis ." + options.yaxis.highlightTick).css({ "color": options.yaxis.highlightColor });
            }

            $(".flot-text").filter(function () { return $(this).text() === ""; }).remove();
        });
    }

    var options = {
        coordinate: { type: 'default' },
        xaxis: {
            tickFormatter: function (v, axis) {
                return v;
            }
        },
        yaxis: {
            tickFormatter: function (v, axis) {
                return v;
            }
        }
    };

    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'coordinate',
        version: '1.6'
    });
})(jQuery);