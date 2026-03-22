define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.card/advancedIndex',
        edit_url: 'member.card/advancedEdit',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'ID', width: 80},
                    {
                        field: 'memberUser.username',
                        title: '用户',
                        minWidth: 160,
                        templet: function (d) {
                            var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>';
                            return html;
                        }
                    },
                    {field: 'name', title: '姓名', minWidth: 100},
                    {field: 'card', title: '证件号', minWidth: 220},
                    {
                        field: 'advanced_status',
                        title: '高级认证状态',
                        minWidth: 120,
                        search: 'select',
                        selectList: {1: '审核中', 2: '已通过', 3: '已拒绝'},
                        templet: function (d) {
                            if (d.advanced_status == 1) {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">审核中</span>';
                            } else if (d.advanced_status == 2) {
                                return '<span class="layui-btn layui-btn-normal layui-btn-xs">已通过</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-warm layui-btn-xs">已拒绝</span>';
                            }
                        }
                    },
                    {field: 'advanced_time', title: '申请时间', minWidth: 180, search: false},
                    {field: 'advanced_do_time', title: '审核时间', minWidth: 180, search: false},
                    {
                        width: 120,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[
                            {
                                text: '查看审核',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs'
                            }
                        ]]
                    }
                ]],
            });

            ea.listen();
        },

        edit: function () {
            ea.listen();
        }
    };

    return Controller;
});