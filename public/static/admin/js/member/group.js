/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:23:18
 * @LastEditTime: 2021-06-21 13:08:39
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.group/index',
        add_url: 'member.group/add',
        edit_url: 'member.group/edit',
        delete_url: 'member.group/delete',
        export_url: 'member.group/export',
        modify_url: 'member.group/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', width: 80, title: 'id'},
                    {field: 'name', width: 120, title: '权限名称'},
                    {field: 'number', width: 120, title: '权限对应数字'},
                    {field: 'sort', width: 80, title: '排序', edit: 'text'},
                    {field: 'status', width: 80, title: '状态', tips: '启用|禁用', width: 100, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', width: 180, title: '创建时间'},
                    {width: 150,fixed: "right", title: '操作', templet: ea.table.tool},
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