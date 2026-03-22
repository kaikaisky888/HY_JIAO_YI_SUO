/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-07-11 20:52:22
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.card/index',
        add_url: 'member.card/add',
        edit_url: 'member.card/edit',
        delete_url: 'member.card/delete',
        modify_url: 'member.card/modify',
        export_url: 'member.card/export',
        show_url: 'member.card/show',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id'},
                    {field: 'memberUser.username', title: '用户', minWidth: 150,
                        templet: function (d) {
                            var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>';
                            return html;
                        }
                    },
                    {field: 'name', title: '姓名', minWidth: 100},
                    {field: 'card', title: '证件号', minWidth: 220},
                    {field: 'status', title: '状态', minWidth: 100, search: 'select', selectList: {0: '审核中', 1: '已审核', 2: '已拒绝'},
                        templet: function (d) {
                            if(d.status == 0){
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">审核中</span>';
                            }else if(d.status == 1){
                                return '<span class="layui-btn layui-btn-xs">已审核</span>';
                            }else{
                                return '<span class="layui-btn layui-btn-warm layui-btn-xs">已拒绝</span>';
                            }
                        }
                    },
                    {field: 'create_time', title: '创建时间', minWidth: 180, search: false},
                    {field: 'update_time', title: '更新时间', minWidth: 180, search: false},
                    {width: 100, title: '操作', templet: ea.table.tool,
                        operat: [
                            [{
                                text: '查看审核',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs',
                            }]
                        ]
                    },
                ]],
            });

            ea.listen();
        },
        show: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});