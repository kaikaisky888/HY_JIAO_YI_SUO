/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 22:41:00
 * @LastEditTime: 2021-07-01 03:05:44
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'ieo.lists/index',
        add_url: 'ieo.lists/add',
        edit_url: 'ieo.lists/edit',
        delete_url: 'ieo.lists/delete',
        export_url: 'ieo.lists/export',
        modify_url: 'ieo.lists/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'productLists.title', width: 120, title: '对应币种'},
                    {field: 'title', width: 150, title: '标题'},
                    {field: 'ieo_usdt_price', width: 150, title: '发行价格U', search: false},
                    {field: 'ieo_btc_price', width: 150, title: '发行价格B', search: false},
                    {field: 'ieo_eth_price', width: 150, title: '发行价格E', search: false},
                    {field: 'ieo_num', width: 150, title: '发行总量', search: false},
                    {field: 'start_time', width: 180, title: '开始时间', search: false, templet: function (d) {
                        var t = d.start_time*1000;
                        return layui.util.toDateString(t, 'yyyy-MM-dd HH:mm:ss');
                    }},
                    {field: 'end_time', width: 180, title: '结束时间', search: false, templet: function (d) {
                        var t = d.end_time*1000;
                        return layui.util.toDateString(t, 'yyyy-MM-dd HH:mm:ss');
                    }},
                    {field: 'ieo_site', width: 150, title: '官网'},
                    {field: 'ieo_link', width: 150, title: '白皮书链接', search: false},
                    {field: 'sort', width: 100, title: '排序', edit: 'text', search: false},
                    {field: 'status', width: 100, title: '状态', tips: '启用|禁用', search: false, templet: ea.table.switch},
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