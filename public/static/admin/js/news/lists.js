/*
 * @Author: Fox Blue
 * @Date: 2021-06-19 22:59:23
 * @LastEditTime: 2021-06-26 20:46:22
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'news.lists/index',
        add_url: 'news.lists/add',
        edit_url: 'news.lists/edit',
        delete_url: 'news.lists/delete',
        export_url: 'news.lists/export',
        modify_url: 'news.lists/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id', search: false},
                    {field: 'newsCate.title', width: 90, title: '分类'},
                    {field: 'title', minWidth: 200, title: '标题'},
                    {field: 'logo', title: '图片', minWidth: 100, templet: ea.table.image, search: false},
                    {field: 'status', title: '状态', width: 85, selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', width: 180, title: '创建时间', search: 'range'},
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