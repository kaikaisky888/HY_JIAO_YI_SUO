/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-06-18 16:45:36
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form;
    var table = layui.table;
    form.on('radio(type)', function(data){
        if(data.value == 'radio'){
            $("#radio_type").show();
        }else{
            console.log(data.value);
        }
    });
    window.configbox = function(a,b){
        $("#docTitle li").html(a);
        $("#c-group").val(b);
        $("input[name=group]").val(b);
        table.reload('currentTableIdRenderId', {
            where: { //设定异步数据接口的额外参数，任意设
                group: b,
                filter: JSON.stringify({'group':b}),
                op: JSON.stringify({'group':{}}),
            }
            ,page: {
                curr: 1 //重新从第 1 页开始
            }
        }); 

        return false;
    }

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.config/index',
        add_url: 'system.config/add',
        edit_url: 'system.config/edit',
        delete_url: 'system.config/delete',
    };

    var initlist = {
        table_elem: '#currentTableId',
        table_render_id: 'currentTableIdRenderId',
        index_url: 'system.config/lists',
        add_url: 'system.config/addconfig',
        edit_url: 'system.config/editconfig',
        delete_url: 'system.config/deleteconfig',
        set_url: 'system.config/setconfig',

    }

    var Controller = {
        index: function () {
            ea.table.render({
                init: init,
                elem: '#currentTable',
                toolbar: '#toolbar',
                toolbar: ['add'],
                limit: 1000, // 数据表格默认全部显示
                defaultToolbar:['filter'],
                cols: [[
                    {field: 'remark', minWidth: 100, title: '分组', templet: '#remark'},
                    {field: 'name', minWidth: 100, title: '标识'},
                    {
                        width: 70,
                        title: '操作',
                        templet: ea.table.tool,
                        fixed: "right",
                        operat: ['edit']
                    }
                ]],
                done: function(res, curr, count){
                    // console.log(curr)
                    $('.layui-table-page:first').hide();
                }
            });
            ea.table.render({
                init: initlist,
                elem: '#currentTableId',
                toolbar: '#toolbarId',
                toolbar: ['refresh','add'],
                cols: [[
                    {field: 'remark', width: 180, align: 'left', title: '配置项'},
                    {field: 'name', width: 180, align: 'left', title: '配置参数'},
                    {field: 'value', width: 200, align: 'left', title: '默认值', search: false, templet: function(res){
                        if(res.type=='image'){
                            return '<img style="max-width: 20px; max-height: 20px;" src="'+res.value+'">';
                        }else{
                            return res.value;
                        }
                    }},
                    {field: 'group', minWidth: 80, title: '分组标识'},
                    {field: 'content', width: 200, align: 'left', title: '说明', search: false},
                    {
                        width: 110,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            'delete'
                        ]
                    },
                    {
                        width: 70,
                        title: '设置',
                        templet: ea.table.tool,
                        fixed: "right",
                        operat: [
                            [{
                                text: '设置',
                                url: initlist.set_url,
                                method: 'open',
                                auth: 'setconfig',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }]
                        ]
                    }
                ]]
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        addconfig: function () {
            ea.listen();
        },
        editconfig: function () {
            ea.listen();
        },
        setconfig: function () {
            ea.listen();
        }
        
    };
    return Controller;
});



