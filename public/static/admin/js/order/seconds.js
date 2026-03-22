/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-08-02 22:56:09
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'order.seconds/index',
        kong_urla: 'order.seconds/konga',
        kong_urlb: 'order.seconds/kongb',
    };

    var Controller = {
        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh',
                [{
                    text: '一键控赢',
                    url: init.kong_urla,
                    method: 'request',
                    auth: 'konga',
                    class: 'layui-btn layui-btn-sm',
                    icon: 'fa fa-level-up',
                    extend: 'data-full="false"',
                    checkbox:true,
                    msgbefore:false,
                }],
                [{
                    text: '一键控亏',
                    url: init.kong_urlb,
                    method: 'request',
                    auth: 'kongb',
                    class: 'layui-btn layui-btn-danger layui-btn-sm',
                    icon: 'fa fa-level-down',
                    extend: 'data-full="false"',
                    checkbox:true,
                    msgbefore:false,
                }]
                ],
                cols: [[
                    {templet: "#checkOid",
                        title: "<input type='checkbox' name='layTableCheckbox' lay-skin='primary' lay-filter='layTableAllChoose'> ",
                        width: 50,
                    },
                    {field: 'op_status', title: '状态', minWidth: 100, search: 'select', selectList: {0: '交易中', 1: '已平仓'}, templet: function(d){
                        if (d.op_status == 1) {
                            return '<span class="layui-btn layui-btn-xs">已平仓</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-primary">交易中</span>';
                        }
                    }},
                    {field: 'kong_type', title: '控', width: 100, search: 'select', selectList: {0: '中', 1: '赢',2: '亏'}, templet: function(d){
                        if (d.op_status === 0 && d.kong_type === 0 && d.end_price === '0.00000000'){ 
                            if(d.kong_type == 0){
                                var selecteda = 'selected';
                                var selectedb = '';
                                var selectedc = '';
                            }
                            var dt = '<select name="'+d.kong_type+'" lay-filter="selectKongType" lay-id="'+d.id+'">';
                            dt = dt + '<option value="0" '+selecteda+'>--</option>';
                            dt = dt + '<option value="1" '+selectedb+'>赢</option>';
                            dt = dt + '<option value="2" '+selectedc+'>亏</option>';
                            dt = dt + '</select>';
                        }else{
                            if(d.kong_type == 1){
                                var dt = '<span class="layui-btn layui-btn-xs">控赢</span></span>';
                            }else if(d.kong_type == 2){
                                var dt = '<span class="layui-btn layui-btn-xs layui-btn-danger">控亏</span>';
                            }else{
                                var dt = '<span class="layui-btn layui-btn-xs layui-btn-primary">正常</span>';
                            }
                        }
                        return dt;
                    }},
                    // {field: 'id', title: 'id', search: false},
                    {field: 'op_number', title: '数量额', minWidth: 150, search: false},
                    {field: 'op_style', title: '类型', minWidth: 100, search: 'select', selectList: {1: '买涨', 2: '买跌'}, templet: function(d){
                        if (d.op_style == 2) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-danger">买跌</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs">买涨</span>';
                        }
                    }},
                    {field: 'is_win', title: '赢亏', minWidth: 100, search: 'select', selectList: {0: '等待', 1: '赢利',2: '亏损', 3: '无效'}, templet: function(d){
                        if (d.is_win == 1) {
                            return '<span class="layui-btn layui-btn-xs">赢利</span>';
                        } else if (d.is_win == 2) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">亏损</span>';
                        } else if (d.is_win == 3) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-danger">无效</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-primary">等待</span>';
                        }
                    }},
                    {field: 'true_fee', title: '明细',  minWidth: 200, search: false, templet: function(d){
                        if (d.is_win == 1) {
                            var dt = '<div class="div-cos"><label>总赢返：</label>'+d.all_fee+'</div>';
                            dt = dt + '<div class="div-cos"><label>手续费：</label>'+d.sx_fee+'</div>';
                        } else if (d.is_win > 1) {
                            var dt = '<div class="div-cos"><label>单亏损：</label>'+d.op_number+'</div>';
                        }else {
                            var dt = '<div class="div-cos"><label>------</div>';
                        }
                        return dt;
                    }},
                    {field: 'statr_price', title: '价格', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>入仓：</label>'+d.start_price+'</div>';
                        if (d.op_status == 1) {
                            dt = dt + '<div class="div-cos"><label>平仓：</label>'+d.end_price+'</div>';
                        }else{
                            dt = dt + '<div class="div-cos"><label>平仓：</label>--------</div>';
                        }
                        return dt;
                    }},
                    {field: 'create_time', width: 220, title: '时间', search: 'range', templet: function(d){
                        var dt = '<div class="div-cos"><label>入仓：</label>'+d.create_time+'</div>';
                        if (d.op_status == 1) {
                            dt = dt + '<div class="div-cos"><label>平仓：</label>'+d.update_time+'</div>';
                        }else{
                            dt = dt + '<div class="div-cos"><label>平仓：</label>--------</div>';
                        }
                        return dt;
                    }},
                    {field: 'memberUser.username', title: '用户', minWidth: 150,
                        templet: function (d) {
                            var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>';
                            return html;
                        }
                    },
                    {field: 'productLists.title', title: '产品', minWidth: 150},
                ]],
                done: function (res, curr, count) {
                    //表格下拉自适应
                    layui.each($('select'), function (index, item) {
                    var elem = $(item);
                    elem.val(elem.data('value')).parents('div.layui-table-cell').css('overflow', 'visible');
                    });
                },
            });

            ea.listen();
        },
    };
    return Controller;
});