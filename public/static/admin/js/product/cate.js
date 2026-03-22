/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 23:20:04
 * @LastEditTime: 2021-06-13 23:39:18
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'product.cate/index',
        add_url: 'product.cate/add',
        edit_url: 'product.cate/edit',
        delete_url: 'product.cate/delete',
        export_url: 'product.cate/export',
        modify_url: 'product.cate/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['add'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'title', minWidth: 80, title: '分类名'},
                    {field: 'image', search: false, minWidth: 100, imageHeight: 25, title: '分类图片', templet: ea.table.image},
                    {field: 'sort', search: false, minWidth: 80, title: '排序', edit: 'text'},
                    {field: 'status', minWidth: 100, title: '状态', tips: '启用|禁用', templet: ea.table.switch, tips: '启用|禁用', search: 'select', selectList: {0: '禁用', 1: '启用'}},
                    {field: 'remark', minWidth: 180, title: '备注说明', templet: ea.table.text},
                    {field: 'create_time', search: false, minWidth: 180, title: '创建时间'},
                    {width: 150, title: '操作',fixed: "right", templet: ea.table.tool},
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