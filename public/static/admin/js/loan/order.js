/*
 * @Author: OpenAI
 * @Description: 贷款申请管理
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'loan.order/index',
        detail_url: 'loan.order/detail',
        review_url: 'loan.order/review',
        delete_url: 'loan.order/delete',
        export_url: 'loan.order/export',
        modify_url: 'loan.order/modify',
        repay_url: 'loan.order/repay',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'uid', title: '用户ID', minWidth: 90},
                    {field: 'username', title: '用户名', minWidth: 120},
                    {field: 'loan_name', title: '贷款产品', minWidth: 150},
                    {field: 'amount', title: '申请金额', minWidth: 120, search: false},
                    {field: 'interest_rate', title: '年化利率(%)', minWidth: 120, search: false},
                    {field: 'repayment_period', title: '周期(天)', minWidth: 100, search: false},
                    {field: 'real_name', title: '真实姓名', minWidth: 120},
                    {field: 'phone', title: '手机号', minWidth: 130},
                    {
                        field: 'status_text',
                        title: '状态',
                        minWidth: 100,
                        search: false,
                        templet: function (d) {
                            var color = '#666';
                            if (parseInt(d.status) === 0) color = '#FFB800';
                            if (parseInt(d.status) === 1) color = '#16b777';
                            if (parseInt(d.status) === 2) color = '#FF5722';
                            if (parseInt(d.status) === 4) color = '#1E9FFF';
                            if (parseInt(d.status) === 5) color = '#f56c6c';
                            if (parseInt(d.status) === -1) color = '#999999';
                            return '<span style="color:' + color + ';">' + d.status_text + '</span>';
                        }
                    },
                    {field: 'review_admin_name', title: '审核人', minWidth: 100, search: false},
                    {field: 'create_time', title: '申请时间', minWidth: 180, search: false},
                    {field: 'review_time', title: '审核时间', minWidth: 180, search: false},
                    {
                        width: 280,
                        title: '操作',
                        templet: function (d) {
                            var html = '';

                            html += '<a class="layui-btn layui-btn-xs layui-btn-normal" data-open="' + init.detail_url + '?id=' + d.id + '" data-title="查看详情">详情</a>';

                            if (parseInt(d.status) === 0) {
                                html += '<a class="layui-btn layui-btn-xs" data-open="' + init.review_url + '?id=' + d.id + '" data-title="审核申请">审核</a>';
                            }

                            if (parseInt(d.status) === 1 || parseInt(d.status) === 5) {
                                html += '<a class="layui-btn layui-btn-xs layui-btn-warm" data-request="' + init.repay_url + '?id=' + d.id + '" data-table="'
                                    + init.table_render_id + '" data-confirm="确认该笔贷款已完成还款吗？">还款</a>';
                            }

                            html += '<a class="layui-btn layui-btn-xs layui-btn-danger" data-request="' + init.delete_url + '?id=' + d.id + '" data-table="'
                                + init.table_render_id + '" data-confirm="确定删除该记录吗？">删除</a>';

                            return html;
                        }
                    },
                ]],
            });

            ea.listen();
        },

        detail: function () {
            ea.listen();
        },

        review: function () {
            ea.listen();
        },
    };

    return Controller;
});