/*
 * @Author: Fox Blue
 * @Date: 2021-06-23 15:46:35
 * @LastEditTime: 2021-08-07 04:32:03
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.config/index',
        add_url: 'member.config/add',
        edit_url: 'member.config/edit',
        delete_url: 'member.config/delete',
        export_url: 'member.config/export',
        modify_url: 'member.config/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'group', minWidth: 120, title: '分组'},
                    {field: 'name', minWidth: 200, title: '设置名'},
                    {field: 'title', minWidth: 150, title: '标题说明'},
                    {field: 'value', minWidth: 250, title: '设置值', edit: 'text'},
                    {field: 'sort', width: 80, title: '排序', edit: 'text'},
                    {field: 'create_time', minWidth: 180, title: '创建时间'},
                    {width: 250, title: '操作', templet: ea.table.tool},
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