/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-08-08 12:23:56
 * @Description: Forward, no stop
 */
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-06-22 17:56:13
 * @Description: Forward, no stop
 */

define(["jquery", "easy-admin"], function ($, ea) {
    var flow = layui.flow;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.ship/index',
    };

    window.findUser = function(_this,id){
        $("body").find('.me-first').removeClass("fa-minus-square").addClass("fa-plus-square");
        $(_this).find('i').removeClass("fa-plus-square").addClass("fa-minus-square")
        $.post("find", {id:id}, function (res) {
            if (res.count > 0) {
                if($("#list_"+id).length <= 0){
                    $(_this).after('<table id="list_'+id+'" class="layui-table fox-table" lay-skin="nob"></table>')
                }
                $("#list_"+id).empty();
                flow.load({
                    elem: "#list_"+id //指定列表容器
                    ,isAuto: false
                    ,end:''
                    ,done: function(page, next){ //到达临界点（默认滚动触发），触发下一页
                    var lis = [];
                        layui.each(res.data, function(index, item){
                            var html = '<tr>';
                            html = html + '<td><a href="javascript:void(0);" onclick ="findUser(this,'+item.id+')"><i class="me-first fa fa-plus-square" aria-hidden="true"></i> '+item.username+'<span class="span-margin">('+item.id+'-'+item.ucount+')</span></a></td>';	
                            html = html + '</tr>';
                            lis.push(html);
                        }); 
                        next(lis.join(''), page < 1);    
                    }
                });
            }else{
                $(_this).find('i').removeClass("fa-minus-square").addClass("fa-plus-square");
            }
        });
    }

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                skin: 'nob',
                cols: [[
                    {field: 'username', width: '100%', title: '用户', templet: function(d){
                        return '<a href="javascript:void(0);" onclick ="findUser(this,'+d.id+')"><i class="me-first fa fa-plus-square" aria-hidden="true"></i> '+d.username+'<span class="span-margin">('+d.id+'-'+d.ucount+')</span></a>';
                    }},
                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});