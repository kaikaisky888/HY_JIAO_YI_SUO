/*
 * @Author: Fox Blue
 * @Date: 2021-06-23 16:49:39
 * @LastEditTime: 2021-07-08 17:03:04
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.code/index',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', width: 80, title: 'ID', search: false},
                    {field: 'phone', title: '接收方'},
                    {field: 'code', title: '验证码'},
                    {field: 'uid', title: '会员ID'},
                    {field: 'create_time', title: '创建时间'},
                    {field: 'useable', title: '使用',
                    templet: function (d) {
                        if (d.useable === 0) {
                            return '<span class="layui-btn layui-btn-primary layui-btn-xs">未用</span>';
                        } else {
                            return '<span class="layui-btn layui-btn-warm layui-btn-xs">已用</span>';
                        }
                    }
                    },
                    {field: 'ip', title: 'IP地址'},
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});