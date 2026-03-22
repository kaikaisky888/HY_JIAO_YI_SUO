/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-09-12 19:06:57
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.wallet/index',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    // {field: 'id', title: 'id', minWidth: 120},
                    {field: 'memberUser.username', title: '用户', minWidth: 150,
                        templet: function (d) {
                            var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>';
                            return html;
                        }
                    },
                    {field: 'productLists.title', title: '币种', width: 100},
                    {field: 'ex_money', title: '币币帐户', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>数量：</label>'+d.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>锁定：</label>'+d.lock_ex_money+'</div>';
                        return dt;
                    }},
                    {field: 'le_money', title: '合约帐户', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>数量：</label>'+d.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>锁定：</label>'+d.lock_le_money+'</div>';
                        return dt;
                    }},
                    {field: 'op_money', title: '期权帐户', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>数量：</label>'+d.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>锁定：</label>'+d.lock_op_money+'</div>';
                        return dt;
                    }},
                    {field: 'up_money', title: '理财帐户', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>数量：</label>'+d.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>锁定：</label>'+d.lock_up_money+'</div>';
                        return dt;
                    }},
                    {field: 'uid', title: '操作', width: 80, search: false, templet: function(d){
                        var dt = '<a class="layui-btn layui-btn-xs" data-open="member.wallet/dowallet?id='+d.id+'" data-title="调整">调整</a>';
                        return dt;
                    }},
                    {field: 'create_time', title: '创建时间', minWidth: 180, search: false},
                    {field: 'status', title: '状态', minWidth: 100, tips: '启用|禁用', search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    // {width: 250, title: '操作', templet: ea.table.tool},
                ]],
            });

            ea.listen();
        },
        dowallet: function () {
            ea.listen();
        },
    };
    return Controller;
});