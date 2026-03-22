/*
 * @Author: Fox Blue
 * @Date: 2021-07-03 12:23:12
 * @LastEditTime: 2021-08-25 16:26:18
 * @Description: Forward, no stop
 */
var width =120,
    height =40,
    margin = {left:0,top:5,right:0,bottom:5},
    g_width = width - margin.left -margin.right,
    g_height = height -margin.top - margin.bottom;
    function gobarchart(el,data,chage,i){
        var g = d3.select(el)
        // var data = [34110, 34130, 34079, 34079, 34071, 34078, 34097, 34115, 34152, 34137];
        var priceMin = d3.min(data);
        var priceMax = d3.max(data);

        var scale_x = d3.scaleLinear().domain([0,data.length-1]).range([0,g_width])
        // var scale_y = d3.scaleLinear().domain([0,d3.max(data)]).range([g_height,0])
        var scale_y = d3.scaleLinear().domain([priceMin, priceMax]).range([g_height,0])

        if(chage > 0){
            var color = "lightseagreen";
            var lclass = "svg-green-a";
        }else{
            var color = "indianred";
            var lclass = "svg-red-a";
        }
        var area = false;
        if(area){
            var pw = 0.5;
        }else{
            var pw = 2;
        }

        var line_generator = d3.line()
        .x(function(d,i){return scale_x(i);})//0,1,2,3...
        .y(function(d){return scale_y(d);})//1,3,5
        .curve(d3.curveCardinal)

        g.select('path').attr('d',line_generator(data))
        .attr("stroke", color)
        .attr("stroke-width", pw)

        if(area){
            const linearGradient = g
                .append("linearGradient")
                .attr("id", "linearColor"+i)
                //颜色渐变方向
                .attr("x1", "0%")
                .attr("y1", "100%")
                .attr("x2", "0%")
                .attr("y2", "50%");
            if(chage > 0){
                // //设置矩形条开始颜色
                linearGradient.append("stop")
                    .attr("offset", "0%")
                    .attr("stop-color", "#ffffffff");
                // //设置结束颜色
                linearGradient.append("stop")
                    .attr("offset", "100%")
                    .attr("stop-color", color);
            }else{
                // //设置矩形条开始颜色
                linearGradient.append("stop")
                    .attr("offset", "0%")
                    .attr("stop-color", "#ffffffff");
                // //设置结束颜色
                linearGradient.append("stop")
                    .attr("offset", "100%")
                    .attr("stop-color", color);
            }
            var area_generator = d3.area()
                .x(function(d,i){return scale_x(i);})//0,1,2,3...
                .y0(g_height)
                .y1(function(d){return scale_y(d);})//1,3,5
                .curve(d3.curveCardinal)
        
                g.select('path').attr('d',area_generator(data))
                .style("fill","url(#" + linearGradient.attr("id") + ")")
                .style("opacity", 0.6)
        }

    }
    $('.svg').empty();
    
    d3.selectAll('.svg').append('g').attr('transform','translate('+margin.left+','+margin.top+')')
    d3.selectAll('.svg').append('path')

    // d3.selectAll('.svg').select('path').append("animate").attr("attributeName","fill")//因为是填充色，所以用fill属性；如果渐变的是线条的颜色，就改成stroke属性
    //     .attr('attributeType','XML')
    //     .attr('from','rgb(60, 86, 94)')
    //     .attr('to','rgb(81, 81, 82)')
    //     .attr('dur','0.5s')
    //     .attr('fill','freeze')

    d3.selectAll('.svg').on("click", function(){
        $(this).fadeOut(50).fadeIn(500);
    }) 

    layui.use(['layer', 'form','element','carousel'], function(){
		var layer = layui.layer
		,form = layui.form
		,element = layui.element
		,carousel = layui.carousel;
		element.on('nav(demo)', function(elem){
			//console.log(elem)
			layer.msg(elem.text());
		});

        carousel.render({
			elem: '#banner'
			,width: '100%' 
			,arrow: 'hover' 
		});

        form.verify({ 
            username: function (value, item) { 
                if(value.length <= 3){
                    var placeholder= $(item).attr("placeholder");
                    layer.msg(placeholder, {shade: 0.1,time: 1000});
                    return false;
                }
            },
        })

        form.on('submit(checkRegister)', function(data){
            var reg = data.field.reg;
            var url = data.field.register_url+'?reg='+reg;
            if(reg.length>3){
                window.location.href = url;
            }
            return false;
        });
        
    })
