/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-08-23 04:26:49
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'order.deal/index',
    };

    var Controller = {
        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150,
                        templet: function (d) {
                            var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>';
                            return html;
                        }
                    },
                    {field: 'title', title: '交易对', minWidth: 120},
                    {field: 'type', title: '类型', minWidth: 100, search: 'select', selectList: {1: '限价', 2: '市价'}, templet: function(d){
                        if (d.type == 2) {
                            return '<span class="layui-btn layui-btn-xs">市价</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">限价</span>';
                        }
                    }},
                    {field: 'direction', title: '方向', minWidth: 100, search: 'select', selectList: {1: '买入', 2: '卖出'}, templet: function(d){
                        if (d.direction == 2) {
                            return '<span class="layui-btn layui-btn-xs">卖出</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">买入</span>';
                        }
                    }},
                    {field: 'account', title: '委托量', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>委托量：</label>'+d.account+'</div>';
                        dt = dt + '<div class="div-cos"><label>USDT：</label>'+d.price_usdt+'</div>';
                        return dt;
                    }},
                    {field: 'account_product', title: '实际成交', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>成交量：</label>'+d.account_product+'</div>';
                        dt = dt + '<div class="div-cos"><label>手续费：</label>'+d.account_sxf+' '+d.account_sxf_tit+'</div>';
                        return dt;
                    }},
                    {field: 'status', title: '状态', minWidth: 140, search: 'select', selectList: {1: '进行中', 2: '已完成', 3: '已撤消'}, templet: function(d){
                        var dt = '<a class="layui-btn layui-btn-xs layui-btn-warm" data-request="order.deal/edeal?id='+d.id+'" data-title="完成">完成</a>';
                        if (d.status == 3) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">已撤消</span>';
                        } else if (d.status == 2) {
                            return '<span class="layui-btn layui-btn-xs">已完成</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">进行中</span>'+dt;
                        }
                    }},
                    {field: 'create_time', width: 180, title: '时间', search: 'range', templet: function(d){
                        return d.create_time+'<br>'+d.update_time
                    }},
                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});