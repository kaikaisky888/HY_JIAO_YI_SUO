/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-08-05 02:39:02
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'order.ieorg/index',
        release_url: 'order.ieorg/release',
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
                    {field: 'ieoLists.title', title: '项目', minWidth: 120},
                    {field: 'buy_account', title: '购买数量', minWidth: 180, search: false},
                    {field: 'money', title: '金额', minWidth: 180, search: false},
                    {field: 'productLists.title', title: '支付', minWidth: 80, search: false},
                    {field: 'type', title: '状态', minWidth: 110, search: 'select', selectList: {1: '认购冻结', 2: '认购释放'}, templet: function(d){
                        if (d.type == 2) {
                            return '<span class="layui-btn layui-btn-xs">认购释放</span>';
                        } else if (d.type == 1) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">认购冻结</span>';
                        }
                    }},
                    {field: 'create_time', width: 180, title: '时间', search: 'range'},
                    {
                        width: 100,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '释放',
                                url: init.release_url,
                                method: 'request',
                                auth: 'release',
                                title: '确定要释放么？',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                                msgbefore: true,
                            }],
                        ]
                    }
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