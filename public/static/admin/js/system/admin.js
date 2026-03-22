/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-06-23 14:27:40
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.admin/index',
        add_url: 'system.admin/add',
        edit_url: 'system.admin/edit',
        delete_url: 'system.admin/delete',
        modify_url: 'system.admin/modify',
        export_url: 'system.admin/export',
        password_url: 'system.admin/password',
    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'username', minWidth: 150, title: '登录账户'},
                    {field: 'head_img', imageHeight: 20, minWidth: 80, title: '头像', search: false, templet: ea.table.image},
                    {field: 'adminAuth.title', width: 110, title: '角色',
                        templet: function (d) {
                            var n = d.auth_ids.charAt(d.auth_ids.length-1);
                            if (d.is_team == 1) {
                                return '<span class="layui-btn layui-btn-xs bg-color-'+n+'" style="min-width:80px">'+d.adminAuth.title+'</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-danger layui-btn-xs" style="min-width:80px">'+d.adminAuth.title+'</span>';
                            }
                        }
                    },
                    {field: 'is_team', width: 110, title: '组别', tips: '是|否', search: false,
                        templet: function (d) {
                            if (d.is_team == 1) {
                                return '<span class="layui-btn layui-btn-xs">团队</span>';
                            } else {
                                return '<span class="layui-btn layui-btn-danger layui-btn-xs">管理</span>';
                            }
                        }
                    },
                    {field: 'level_id', minWidth: 150, title: '上级ID',
                        templet: function (d) {
                            if(d.level_id > 0){
                                return d.level_name+'/'+d.level_id;
                            }else{
                                return '------';
                            }
                        }
                    },
                    {field: 'holder_id', minWidth: 150, title: '股东ID',
                        templet: function (d) {
                            if(d.holder_id > 0){
                                return d.holder_name+'/'+d.holder_id;
                            }else{
                                return '------';
                            }
                        }
                    },
                    {field: 'phone', minWidth: 120, title: '手机'},
                    {field: 'email', minWidth: 120, title: '邮箱'},
                    {field: 'login_num', minWidth: 110, title: '登录次数'},
                    {field: 'status', title: '状态', width: 85, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', minWidth: 180, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            [{
                                text: '设置密码',
                                url: init.password_url,
                                method: 'open',
                                auth: 'password',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'delete'
                        ]
                    }
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
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});