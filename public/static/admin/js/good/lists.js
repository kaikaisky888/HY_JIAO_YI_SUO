/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 00:09:27
 * @LastEditTime: 2026-03-13
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'good.lists/index',
        add_url: 'good.lists/add',
        edit_url: 'good.lists/edit',
        delete_url: 'good.lists/delete',
        export_url: 'good.lists/export',
        modify_url: 'good.lists/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'productLists.title', width: 100, title: '对应币种'},
                    {field: 'title', title: '标题', minWidth: 150},
                    {field: 'logo', title: '图片', minWidth: 100, templet: ea.table.image, search: false},
                    {
                        field: 'can_buy',
                        title: '限购',
                        minWidth: 100,
                        search: false,
                        templet: function (d) {
                            if (d.can_buy == 0) {
                                return '不限';
                            } else {
                                return d.can_buy;
                            }
                        }
                    },
                    {field: 'play_time', title: '玩法周期', minWidth: 120},
                    {field: 'play_price', title: '最小限额', minWidth: 120, search: false},
                    {field: 'max_price', title: '最大限额', minWidth: 120, search: false},
                    {field: 'play_rate', title: '回报率', minWidth: 120, search: false},
                    {field: 'annual_rate', title: '年化', minWidth: 140, search: false},
                    {field: 'sort', title: '排序', edit: 'text', minWidth: 100, search: false},
                    {field: 'status', width: 100, title: '状态', tips: '启用|禁用', search: false, templet: ea.table.switch},
                    {field: 'create_time', title: '创建时间', minWidth: 180, search: false},
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