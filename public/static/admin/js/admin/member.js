/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:00:41
 * @LastEditTime: 2021-06-20 17:34:20
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'admin.member/index',
        add_url: 'admin.member/add',
        edit_url: 'admin.member/edit',
        delete_url: 'admin.member/delete',
        export_url: 'admin.member/export',
        modify_url: 'admin.member/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'name', title: '权限名称'},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'status', title: '状态', templet: ea.table.switch},
                    {field: 'create_time', title: '创建时间'},
                    {width: 150, title: '操作', templet: ea.table.tool},
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