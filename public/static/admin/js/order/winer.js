/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-08-05 22:02:50
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'order.winer/index',
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
                    {field: 'winerLists.title', title: '矿机', minWidth: 120},
                    {field: 'buy_account', title: '支付数量', minWidth: 180, search: false},
                    {field: 'time', title: '周期', minWidth: 180, search: false},
                    {field: 'lock', title: '剩余', minWidth: 120},
                    {field: 'rate_account', title: '回报', minWidth: 180, search: false},
                    {field: 'type', title: '状态', minWidth: 110, search: 'select', selectList: {1: '挖矿冻结', 2: '挖矿释放'}, templet: function(d){
                        if (d.type == 2) {
                            return '<span class="layui-btn layui-btn-xs">挖矿释放</span>';
                        } else if (d.type == 1) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">挖矿冻结</span>';
                        }
                    }},
                    {field: 'status', title: '状态', minWidth: 110, search: 'select', selectList: {1: '进行中', 2: '已完成', 3: '已撤消'}, templet: function(d){
                        if (d.lock == 0) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">释放中</span>';
                        }else{
                            if (d.status == 2) {
                                return '<span class="layui-btn layui-btn-xs">已完结</span>';
                            } else{
                                return '<span class="layui-btn layui-btn-xs layui-btn-normal">进行中</span>';
                            }
                        }
                    }},
                    {field: 'create_time', width: 180, title: '时间', search: 'range'},
                    
                ]],
            });

            ea.listen();
        },
        release: function () {
            ea.listen();
        },
    };
    return Controller;
});