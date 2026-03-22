/*
 * @Author: Fox Blue
 * @Date: 2021-06-25 13:59:52
 * @LastEditTime: 2021-09-13 14:48:36
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
    };

    var Controller = {

        recharge: function () {
            init.index_url= 'member.wallet_log/recharge';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'productLists.title', title: '币种', minWidth: 120},
                    {field: 'account', title: '数额', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>发生额：</label>'+d.account+'</div>';
                        dt = dt + '<div class="div-cos"><label>手续费：</label>'+d.account_sxf+'</div>';
                        return dt;
                    }},
                    {field: 'all_account', title: '实际变动', minWidth: 220, search: false},
                    {field: 'after', title: '钱包', minWidth: 220, search: false, templet: function(d){
                        var dt = '';
                        if(d.status == 3){
                            dt = dt + '<div class="div-cos"><label>现在：</label><span class="color-2">'+d.after+'</span></div>';
                        }else if(d.status == 2){
                            dt = dt + '<div class="div-cos"><label>现在：</label><span class="color-3">'+d.after+'</span></div>';
                        }else if(d.status == 1){
                            dt = dt + '<div class="div-cos"><label>现在：</label><span class="color-1">'+d.after+'</span></div>';
                        }
                        dt = dt + '<div class="div-cos"><label>原有：</label><span>'+d.before+'</span></div>';
                        return dt;
                    }},
                    {field: 'status', title: '状态', minWidth: 200, search: 'select', selectList: {1: '处理中', 2: '已成功', 3: '已失败'}, templet: function(d){
                        var dt = '<a class="layui-btn layui-btn-xs layui-btn-warm" data-open="member.wallet_log/erecharge?id='+d.id+'" data-title="处理">处理</a>';
                        var dto = '<a class="layui-btn layui-btn-xs layui-btn-warm" data-open="member.wallet_log/orecharge?id='+d.id+'" data-title="查看">查看</a>';
                        if (d.status == 3) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-danger">已失败</span>'+dto;
                        } else if (d.status == 2) {
                            return '<span class="layui-btn layui-btn-xs">已成功</span>'+dto;
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">处理中</span>'+dt;
                        }
                    }},
                    {field: 'create_time', width: 180, title: '充值时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        erecharge: function () { ea.listen(); },
        orecharge: function () { ea.listen(); },
        withdraw: function () {
            init.index_url= 'member.wallet_log/withdraw';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'productLists.title', title: '币种', minWidth: 120},
                    {field: 'account', title: '数额', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>发生额：</label>'+d.account+'</div>';
                        dt = dt + '<div class="div-cos"><label>手续费：</label>'+d.account_sxf+'</div>';
                        return dt;
                    }},
                    {field: 'all_account', title: '实际结算', minWidth: 220, search: false},
                    {field: 'after', title: '钱包', minWidth: 220, search: false, templet: function(d){
                        var dt = '';
                        if(d.status == 3){
                            dt = dt + '<div class="div-cos"><label>现在：</label><span class="color-2">'+d.after+'</span></div>';
                        }else if(d.status == 2){
                            dt = dt + '<div class="div-cos"><label>现在：</label><span class="color-3">'+d.after+'</span></div>';
                        }else if(d.status == 1){
                            dt = dt + '<div class="div-cos"><label>现在：</label><span class="color-1">'+d.after+'</span></div>';
                        }
                        dt = dt + '<div class="div-cos"><label>原有：</label><span>'+d.before+'</span></div>';
                        return dt;
                    }},
                    {field: 'status', title: '状态', minWidth: 200, search: 'select', selectList: {1: '处理中', 2: '已成功', 3: '已失败'}, templet: function(d){
                        var dt = '<a class="layui-btn layui-btn-xs layui-btn-warm" data-open="member.wallet_log/ewithdraw?id='+d.id+'" data-title="处理">处理</a>';
                        if (d.status == 3) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-danger">已失败</span>';
                        } else if (d.status == 2) {
                            return '<span class="layui-btn layui-btn-xs">已成功</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">处理中</span>'+dt;
                        }
                    }},
                    {field: 'create_time', width: 180, title: '提现时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        ewithdraw: function () { ea.listen(); },
        transfer: function () {
            init.index_url= 'member.wallet_log/transfer';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'productLists.title', title: '币种', minWidth: 120},
                    {field: 'title', title: '划转方向', minWidth: 200, search: false},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'status', title: '状态', minWidth: 200, search: 'select', selectList: {1: '处理中', 2: '已成功', 3: '已失败'}, templet: function(d){
                        if (d.status == 3) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-danger">已失败</span>';
                        } else if (d.status == 2) {
                            return '<span class="layui-btn layui-btn-xs">已成功</span>';
                        } else{
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">处理中</span>';
                        }
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        orders: function () {
            init.index_url= 'member.wallet_log/orders';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'productLists.title', title: '币种', minWidth: 120},
                    {field: 'title', title: '所属', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'order_type', title: '类型', minWidth: 200, search: 'select', selectList: {1: '买入挂', 11: '买入成', 111: '买撤单', 2: '卖出挂', 22: '卖出成', 222: '卖撤单'}, templet: function(d){
                        if (d.order_type == 1) {
                            return '<span class="layui-btn layui-btn-xs">买入挂</span>';
                        }else if (d.order_type == 11) {
                            return '<span class="layui-btn layui-btn-xs">买入成</span>';
                        }else if (d.order_type == 111) {
                            return '<span class="layui-btn layui-btn-xs">买撤单</span>';
                        }else if (d.order_type == 2) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">卖出挂</span>';
                        }else if (d.order_type == 22) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">卖出成</span>';
                        }else if (d.order_type == 222) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">卖撤单</span>';
                        }else if (d.order_type == 551) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">加款</span>';
                        }else if (d.order_type == 552) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">减款</span>';
                        }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        seconds: function () {
            init.index_url= 'member.wallet_log/seconds';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'productLists.title', title: '产品', minWidth: 120},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'order_type', title: '类型', minWidth: 200, search: 'select', selectList: {1: '下单', 2: '赢返'}, templet: function(d){
                        if (d.order_type == 1) {
                            return '<span class="layui-btn layui-btn-xs">下单</span>';
                        }else if (d.order_type == 2) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">赢返</span>';
                        }else if (d.order_type == 551) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">加款</span>';
                        }else if (d.order_type == 552) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">减款</span>';
                        }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        good: function () {
            init.index_url= 'member.wallet_log/good';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'remark', title: '产品', minWidth: 120},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'order_type', title: '类型', minWidth: 200, search: 'select', selectList: {1: '下单', 2: '收益', 3: '返本'}, templet: function(d){
                        if (d.order_type == 1) {
                            return '<span class="layui-btn layui-btn-xs">下单</span>';
                        }else if (d.order_type == 2) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">收益</span>';
                        }else if (d.order_type == 3) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">返本</span>';
                        }else if (d.order_type == 551) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">加款</span>';
                        }else if (d.order_type == 552) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">减款</span>';
                        }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        ieorg: function () {
            init.index_url= 'member.wallet_log/ieorg';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'ieotitle', title: '项目', minWidth: 120},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.productLists.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'status', title: '类型', minWidth: 200, search: 'select', selectList: {21: '认购冻结', 22: '认购释放'}, templet: function(d){
                        if (d.status == 21) {
                            return '<span class="layui-btn layui-btn-xs">认购冻结</span>';
                        }else if (d.status == 22) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">认购释放</span>';
                        }else {
                            return '<span class="layui-btn layui-btn-xs layui-btn-primary">------</span>';
                        }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        winer: function () {
            init.index_url= 'member.wallet_log/winer';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.productLists.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        dt = dt + '<div class="div-cos"><label>币种：</label>'+d.remark+'</div>';
                        return dt;
                    }},
                    {field: 'status', title: '类型', minWidth: 200, search: 'select', selectList: {31: '挖矿冻结', 32: '挖矿释放', 33: '挖矿回报'}, templet: function(d){
                        if (d.status == 31) {
                            return '<span class="layui-btn layui-btn-xs">挖矿冻结</span>';
                        }else if (d.status == 32) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">挖矿释放</span>';
                        }else if (d.status == 33) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">挖矿回报</span>';
                        }else {
                            return '<span class="layui-btn layui-btn-xs layui-btn-primary">------</span>';
                        }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        cmseconds: function () {
            init.index_url= 'member.wallet_log/cmseconds';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.productLists.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'remark', title: '比例', search: false, templet: function(d){
                        if (d.remark) { return d.remark+'%'; }else { return '------'; }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        cmgoods: function () {
            init.index_url= 'member.wallet_log/cmgoods';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.productLists.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'remark', title: '比例', search: false, templet: function(d){
                        if (d.remark) { return d.remark+'%'; }else { return '------'; }
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
        leverdeal: function () {
            init.index_url= 'member.wallet_log/leverdeal';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', title: 'id', search: false},
                    {field: 'memberUser.username', title: '用户', minWidth: 150, templet: function(d){ var html = '<div>' + (d.memberUser ? d.memberUser.username : '') + '</div>'; html += '<div style="color:#999;font-size:12px;">UID: ' + (d.memberUser ? d.memberUser.id : '') + '</div>'; return html; }},
                    {field: 'title', title: '明细', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>交易对：</label>'+d.productLists.title+'</div>';
                        dt = dt + '<div class="div-cos"><label>订单ID：</label>'+d.order_id+'</div>';
                        return dt;
                    }},
                    {field: 'all_account', title: '变动额', minWidth: 200, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>原有：</label>'+d.before+'</div>';
                        dt = dt + '<div class="div-cos"><label>变动：</label>'+d.all_account+'</div>';
                        dt = dt + '<div class="div-cos"><label>现在：</label>'+d.after+'</div>';
                        return dt;
                    }},
                    {field: 'order_type', title: '类型', minWidth: 200, search: 'select', selectList: {1: '手续费', 11: '平仓盈', 12: '平仓亏'}, templet: function(d){
                        if (d.order_type == 1) {
                            return '<span class="layui-btn layui-btn-xs">手续费</span>';
                        }else if (d.order_type == 11) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">平仓盈</span>';
                        }else if (d.order_type == 12) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">平仓亏</span>';
                        }else if (d.order_type == 551) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-warm">加款</span>';
                        }else if (d.order_type == 552) {
                            return '<span class="layui-btn layui-btn-xs layui-btn-normal">减款</span>';
                        }else{ return '------'; }
                    }},
                    {field: 'money', title: '钱包明细', minWidth: 220, search: false, templet: function(d){
                        var dt = '<div class="div-cos"><label>币币账：</label>'+d.memberWallet.ex_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>合约账：</label>'+d.memberWallet.le_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>期权账：</label>'+d.memberWallet.op_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>理财账：</label>'+d.memberWallet.up_money+'</div>';
                        dt = dt + '<div class="div-cos"><label>佣金账：</label>'+d.memberWallet.cm_money+'</div>';
                        return dt;
                    }},
                    {field: 'create_time', width: 180, title: '操作时间', search: 'range'},
                ]],
            });
            ea.listen();
        },
    };
    return Controller;
});