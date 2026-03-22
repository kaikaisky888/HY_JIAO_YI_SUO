/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-09-13 15:48:08
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        // recharge_url: 'member.wallet/recharge',
    };

    var Controller = {
        index: function () {
            init.index_url= 'member.wallet_data/index';
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
                    {field: 'productLists.title', title: '币种', minWidth: 120},
                    {field: 'type', title: '类型', minWidth: 90, search: 'select', selectList: {1: '加款', 2: '减款'}, templet: function(d){
                        if(d.type == 1) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">加款</span>';
                        }else if (d.type == 2) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">减款</span>';
                        }
                    }},
                    {field: 'data_type', title: '对应钱包', minWidth: 120, search: false, templet: function(d){
                        if(d.data_type == 'ex_money') {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">币币余额</span>';
                        }else if(d.data_type == 'lock_ex_money') {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">币币锁定余额</span>';
                        }else if(d.data_type == 'le_money') {
                            return '<span class="layui-btn layui-btn-xs">合约余额</span>';
                        }else if(d.data_type == 'lock_le_money') {
                            return '<span class="layui-btn layui-btn-xs">合约锁定余额</span>';
                        }else if(d.data_type == 'op_money') {
                            return '<span class="layui-btn layui-btn-xs layui-btn-primary">期权余额</span>';
                        }else if(d.data_type == 'lock_op_money') {
                            return '<span class="layui-btn layui-btn-xs layui-btn-primary">期权锁定余额</span>';
                        }else if(d.data_type == 'up_money') {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">理财余额</span>';
                        }else if(d.data_type == 'lock_up_money') {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">理财锁定余额</span>';
                        }
                    }},
                    {field: 'num', title: '数量', minWidth: 120, search: false},
                    {field: 'adminUser.username', title: '操作人', minWidth: 150},
                    {field: 'remark', title: '备注', minWidth: 120, search: false},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});