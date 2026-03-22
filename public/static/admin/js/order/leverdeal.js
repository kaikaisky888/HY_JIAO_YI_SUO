/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-08-09 16:08:29
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'order.leverdeal/index',
    };

    var Controller = {
        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150,
                        templet: function (d) {
                            var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>';
                            return html;
                        }
                    },
                    {field: 'title', title: '产品', minWidth: 120},
                    {field: 'buy_price', title: '价格', minWidth: 200, templet: function(d){
                        var dt = '<div class="div-cos"><label>买入价：</label>'+d.buy_price+'</div>';
                        if(d.status ==2){
                            dt = dt + '<div class="div-cos"><label>平仓价：</label>'+d.close_price+'</div>';
                        }else{
                            dt = dt + '<div class="div-cos"><label>平仓价：</label>------</div>';
                        }
                        dt = dt + '<div class="div-cos"><label>当前价：</label>'+d.now_price+'</div>';
                        return dt;
                    }},
                    {field: 'style', title: '方向', minWidth: 100, search: 'select', selectList: {1: '做多', 2: '做少'}, templet: function(d){
                        if (d.style == 2) {
                            return '<span class="layui-btn layui-btn-xs">做少</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">做多</span>';
                        }
                    }},
                    {field: 'account', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>数量：</label>'+d.account+'</div>';
                        dt = dt + '<div class="div-cos"><label>'+d.title+'：</label>'+d.price_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>手续费：</label>'+d.rate_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>杠杆：</label>'+d.play_time+'</div>';
                        return dt;
                    }},
                    {field: 'status', title: '状态', minWidth: 110, search: 'select', selectList: {1: '持仓中', 2: '已平仓'}, templet: function(d){
                        if (d.status == 2) {
                            return '<span class="layui-btn layui-btn-xs">已平仓</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">持仓中</span>';
                        }
                    }},
                    {field: 'is_lock', title: '平仓', minWidth: 100, search: 'select', selectList: {1: '手动', 2: '自动'}, templet: function(d){
                        if (d.is_lock == 2) {
                            return '<span class="layui-btn layui-btn-xs">自动</span>';
                        }else if (d.is_lock == 1) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">手动</span>';
                        } else{
                            return '---';
                        }
                    }},
                    {field: 'is_win', title: '盈亏', minWidth: 100, search: 'select', selectList: {1: '盈', 2: '亏'}, templet: function(d){
                        if (d.is_win == 2) {
                            return '<span class="layui-btn layui-btn-xs">亏</span>';
                        }else if (d.is_win == 1) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">盈</span>';
                        } else{
                            return '---';
                        }
                    }},
                    {field: 'create_time', width: 180, title: '时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});