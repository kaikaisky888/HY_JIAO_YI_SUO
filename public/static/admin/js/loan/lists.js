/*
 * @Author: OpenAI
 * @Description: 贷款产品管理
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'loan.lists/index',
        add_url: 'loan.lists/add',
        edit_url: 'loan.lists/edit',
        delete_url: 'loan.lists/delete',
        export_url: 'loan.lists/export',
        modify_url: 'loan.lists/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'name', title: '产品名称', minWidth: 160},
                    {field: 'logo', title: '图片', minWidth: 100, templet: ea.table.image, search: false},
                    {field: 'min_amount', title: '最小金额', minWidth: 120, search: false},
                    {field: 'max_amount', title: '最大金额', minWidth: 120, search: false},
                    {field: 'interest_rate', title: '年化利率(%)', minWidth: 120, search: false},
                    {field: 'repayment_period', title: '还款周期(天)', minWidth: 120, search: false},
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