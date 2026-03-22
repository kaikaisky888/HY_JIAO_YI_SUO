/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 22:18:49
 * @LastEditTime: 2021-06-25 23:18:59
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'cpm.banner/index',
        add_url: 'cpm.banner/add',
        edit_url: 'cpm.banner/edit',
        delete_url: 'cpm.banner/delete',
        export_url: 'cpm.banner/export',
        modify_url: 'cpm.banner/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'type', title: '类型', search: 'select', selectList: {1: '轮播', 2: '弹窗'}, templet: ea.table.switch, templet: function(d){
                        if(d.type==1){
                            return '<span class="layui-btn layui-btn-xs">轮播</span>';
                        }else if(d.type==2){
                            return '<span class="layui-btn layui-btn-normal layui-btn-xs">弹窗</span>';
                        }
                    }},
                    {field: 'title', title: '标题'},
                    {field: 'name', title: '位置标识'},
                    {field: 'logo', title: '图片', search: false, templet: ea.table.image},
                    {field: 'lang', title: '语言'},
                    {field: 'status', title: '状态', tips: '启用|禁用', search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', minWidth: 180, search: false, title: '创建时间'},
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