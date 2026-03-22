/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 21:09:01
 * @LastEditTime: 2021-10-13 09:51:49
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'miner.lists/index',
        add_url: 'miner.lists/add',
        edit_url: 'miner.lists/edit',
        delete_url: 'miner.lists/delete',
        export_url: 'miner.lists/export',
        modify_url: 'miner.lists/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'productLists.title', width: 100, title: '对应币种'},
                    {field: 'title', width: 200, title: '矿机名称'},
                    {field: 'can_buy', title: '限购', minWidth: 100, search: false,templet:function(d){
                        if(d.can_buy==0){
                            return '不限';
                        }else{
                            return d.can_buy;
                        }
                    }},
                    {field: 'play_time', width: 200, title: '玩法周期', search: false},
                    {field: 'min_rate', width: 200, title: '最小收益率', search: false},
                    {field: 'max_rate', width: 200, title: '最大收益率', search: false},
                    {field: 'sort', width: 100, title: '排序', edit: 'text', search: false},
                    {field: 'status', width: 100, title: '状态', tips: '启用|禁用', search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', width: 180, title: '创建时间', search: false},
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