/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 23:30:32
 * @LastEditTime: 2021-09-01 17:03:59
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'product.lists/index',
        add_url: 'product.lists/add',
        edit_url: 'product.lists/edit',
        delete_url: 'product.lists/delete',
        export_url: 'product.lists/export',
        modify_url: 'product.lists/modify',
        kong_url: 'product.lists/kong',
        ekong_url: 'product.lists/ekong',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh','add'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'productCate.title', title: '分类', width: 100},
                    {field: 'logo', title: '图标', search: false, width: 100, imageHeight: 25, templet: ea.table.image},
                    {field: 'title', title: '名称', width: 100},
                    {field: 'base', width: 60, title: '基币', tips: '是|否', search: false,
                        templet: function (d) {
                            if (d.base == 1) {
                                return '<span class="layui-btn layui-btn-xs">是</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'code', title: '代码', width: 100},
                    {field: 'types_1', search: false, width: 110, title: '币币交易',
                        templet: function (d) {
                            if (d.types.indexOf("1") != -1) {
                                return '<span class="layui-btn layui-btn-xs">是</span><a class="layui-btn layui-btn-xs" data-open="product.lists/setpro?id='+d.id+'" data-title="配置">配置</a>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'types_2', search: false, width: 110, title: '合约交易',
                        templet: function (d) {
                            if (d.types.indexOf("2") != -1) {
                                return '<span class="layui-btn layui-btn-xs">是</span><a class="layui-btn layui-btn-xs" data-open="product.lists/setpro?id='+d.id+'" data-title="配置">配置</a>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'types_3', search: false, width: 110, title: '期权交易',
                        templet: function (d) {
                            if (d.types.indexOf("3") != -1) {
                                return '<span class="layui-btn layui-btn-xs">是</span><a class="layui-btn layui-btn-xs" data-open="product.lists/setpro?id='+d.id+'" data-title="配置">配置</a>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'types_4', search: false, width: 60, title: '理财',
                        templet: function (d) {
                            if (d.types.indexOf("4") != -1) {
                                return '<span class="layui-btn layui-btn-xs">是</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'ishome', width: 60, title: '首页', tips: '是|否', search: false,
                        templet: function (d) {
                            if (d.ishome == 1) {
                                return '<span class="layui-btn layui-btn-xs">是</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'is_kong', width: 75, title: '空气币', tips: '是|否', search: false,
                        templet: function (d) {
                            if (d.is_kong == 1) {
                                return '<span class="layui-btn layui-btn-xs">是</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'isIeorg', width: 60, title: '认购', tips: '是|否', search: false,
                        templet: function (d) {
                            if (d.isIeorg == 1) {
                                return '<span class="layui-btn layui-btn-xs">是</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }
                        }
                    },
                    {field: 'withdraw_member', width: 110, title: '用户提现', tips: '可用|禁用', search: false,
                        templet: function (d) {
                            if (d.withdraw_member == 1) {
                                return '<span class="layui-btn layui-btn-xs">可用</span><a class="layui-btn layui-btn-xs" data-open="product.lists/setpro?id='+d.id+'" data-title="配置">配置</a>';
                            } else {
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">禁用</span>';
                            }
                        }
                    },
                    {field: 'status', width: 100, title: '状态', tips: '启用|禁用', search: false, templet: ea.table.switch},
                    {field: 'last_price', title: '价值', width: 150, search: false},
                    {field: 'remark', width: 150, title: '备注说明', search: false, templet: ea.table.text},
                    {field: 'create_time', width: 180, title: '创建时间', search: false},
                    {width: 150, title: '操作',templet: ea.table.tool},
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
        setpro: function () {
            ea.listen();
        },
        kong: function () {
            init.index_url= 'product.lists/kong';
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'logo', title: '图标', search: false, width: 100, imageHeight: 25, templet: ea.table.image},
                    {field: 'title', title: '名称'},
                    {field: 'code', title: '代码'},
                    {field: 'last_price', search: false, title: '当前价格' ,minWidth: 200, templet: function(d){
                        return '<span id="close">'+d.close+'</span>';
                    }},
                    {field: 'kong', search: false, title: '当前区间' ,minWidth: 300, templet: function(d){
                        return d.kong_min+'-'+d.kong_max;
                    }},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '设置区间',
                                url: init.ekong_url,
                                method: 'open',
                                auth: 'ekong',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                        ]
                    }
                ]],
            });

            ea.listen();
        },
        ekong: function () {
            ea.listen();
        },
    };
    return Controller;
});