/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-08-12 22:32:25
 * @Description: Forward, no stop
 */
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-06-22 17:56:13
 * @Description: Forward, no stop
 */

define(["jquery", "easy-admin"], function ($, ea) {

    // var tableSelect = layui.tableSelect;
    // tableSelect.render({
    //     elem: '#level_id',	//定义输入框input对象
    //     checkedKey: 'id', //表格的唯一建值，非常重要，影响到选中状态 必填
    //     searchKey: 'username',	//搜索输入框的name值 默认keyword
    //     searchPlaceholder: '搜索业务员',	//搜索输入框的提示文字 默认关键词搜索
    //     table: {	//定义表格参数，与LAYUI的TABLE模块一致，只是无需再定义表格elem
    //         url:'444',
    //         cols: [[]]
    //     },
    //     done: function (elem, data) {
    //     //选择完后的回调，包含2个返回值 elem:返回之前input对象；data:表格返回的选中的数据 []
    //     //拿到data[]后 就按照业务需求做想做的事情啦~比如加个隐藏域放ID...
    //     }
    // })
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.user/index',
        add_url: 'member.user/add',
        edit_url: 'member.user/edit',
        delete_url: 'member.user/delete',
        export_url: 'member.user/export',
        modify_url: 'member.user/modify',
        level_url: 'member.user/level',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', width: 80, title: 'id'},
                    {field: 'head_img', width: 80, title: '头像', imageHeight: 25, search: false, templet: ea.table.image},
                    {field: 'username', width: 180, title: '登录名'},
                    {field: 'invite_code', width: 180, title: '邀请码'},
                    {field: 'is_test', minWidth: 80, title: '测试号', search: false,
                        templet: function (d) {
                            if(d.is_test == 0){
                                return '<span class="layui-btn layui-btn-primary layui-btn-xs">否</span>';
                            }else{
                                return '<span class="layui-btn layui-btn-warm layui-btn-xs">是</span>';
                            }
                        }
                    },
                    {field: 'level_id', minWidth: 180, title: '上级ID',
                        templet: function (d) {
                            if(d.level_id > 0){
                                if(d.admin_id == 0){
                                    return d.level_name+'/'+d.level_id;
                                }else{
                                    return '<span class="layui-btn layui-btn-xs" style="min-width:80px;background-color: #f8b190;">业务员</span>';
                                }
                            }else{
                                return '------';
                            }
                        }
                    },
                    {field: 'holder_id', minWidth: 180, title: '股东ID',
                        templet: function (d) {
                            if(d.holder_id > 0){
                                return d.holder_name+'/'+d.holder_id;
                            }else{
                                return '------';
                            }
                        }
                    },
                    {field: 'phone', width: 180, title: '手机号'},
                    {field: 'email', width: 220, title: '邮箱',
                        templet: function (d) {
                            var html = '<div>' + (d.email || '') + '</div>';
                            html += '<div style="color:#999;font-size:12px;">UID: ' + d.id + '</div>';
                            return html;
                        }
                    },
                    {field: 'login_num', width: 150, title: '登录次数', search: false},
                    {field: 'status', width: 100, title: '状态', tips: '启用|禁用', search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'memberGroup.name', width: 120, title: '用户组'},
                    {field: 'create_time', width: 180, title: '创建时间', search: false},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '调整上级',
                                url: init.level_url,
                                method: 'open',
                                auth: 'level',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'edit',
                            'delete'
                        ]
                    },
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
        level: function () {
            ea.listen();
        },
    };
    return Controller;
});