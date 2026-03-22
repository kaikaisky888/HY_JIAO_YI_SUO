/*
 * @Author: Fox Blue
 * @Date: 2021-06-19 23:03:11
 * @LastEditTime: 2021-06-20 10:29:48
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'news.cate/index',
        add_url: 'news.cate/add',
        edit_url: 'news.cate/edit',
        delete_url: 'news.cate/delete',
        export_url: 'news.cate/export',
        modify_url: 'news.cate/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'title', width: 150, title: '分类名'},
                    {field: 'image', imageHeight: 20, title: '分类图片', templet: ea.table.image},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'status', title: '状态', templet: ea.table.switch},
                    {field: 'create_time', width: 180, title: '创建时间'},
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