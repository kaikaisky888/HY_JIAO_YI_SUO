define(["jquery", "easy-admin", "echarts", "echarts-theme", "miniAdmin", "miniTab"], function ($, ea, echarts, undefined, miniAdmin, miniTab) {

    var Controller = {
        index: function () {
            var options = {
                iniUrl: ea.url('ajax/initAdmin'),    // 初始化接口
                clearUrl: ea.url("ajax/clearCache"), // 缓存清理接口
                urlHashLocation: true,      // 是否打开hash定位
                bgColorDefault: false,      // 主题默认配置
                multiModule: true,          // 是否开启多模块
                menuChildOpen: false,       // 是否默认展开菜单
                loadingTime: 0,             // 初始化加载时间
                pageAnim: true,             // iframe窗口动画
                maxTabNum: 20,              // 最大的tab打开数量
            };
            miniAdmin.render(options);

            $('.login-out').on("click", function () {
                ea.request.get({
                    url: 'login/out',
                    prefix: true,
                }, function (res) {
                    ea.msg.success(res.msg, function () {
                        window.location = ea.url('login/index');
                    })
                });
            });
        },
        welcome: function () {

            miniTab.listen();

            /**
             * 查看公告信息
             **/
            $('body').on('click', '.layuimini-notice', function () {
                var title = $(this).children('.layuimini-notice-title').text(),
                    noticeTime = $(this).children('.layuimini-notice-extra').text(),
                    content = $(this).children('.layuimini-notice-content').html();
                var html = '<div style="padding:15px 20px; text-align:justify; line-height: 22px;border-bottom:1px solid #e2e2e2;background-color: #2f4056;color: #ffffff">\n' +
                    '<div style="text-align: center;margin-bottom: 20px;font-weight: bold;border-bottom:1px solid #718fb5;padding-bottom: 5px"><h4 class="text-danger">' + title + '</h4></div>\n' +
                    '<div style="font-size: 12px">' + content + '</div>\n' +
                    '</div>\n';
                layer.open({
                    type: 1,
                    title: '系统公告' + '<span style="float: right;right: 1px;font-size: 12px;color: #b1b3b9;margin-top: 1px">' + noticeTime + '</span>',
                    area: '300px;',
                    shade: 0.8,
                    id: 'layuimini-notice',
                    btn: ['查看', '取消'],
                    btnAlign: 'c',
                    moveType: 1,
                    content: html,
                });
            });

            /**
             * 报表功能
             */
            
            window.getData = function(){
                $.ajax({
                    url:ea.url('ajax/getdata'),
                    async:false,
                    dataType:'json',
                    type:'post',
                    success:function(res){
                        if(res.code > 0){
                            listday = res.data['listday'];
                            legend = res.data['legend'];
                            data.push(res.data['deal']);
                            name.push(res.data['deal_title']);
                            data.push(res.data['leverdeal']);
                            name.push(res.data['leverdeal_title']);
                            data.push(res.data['seconds']);
                            name.push(res.data['seconds_title']);
                            data.push(res.data['coinwin']);
                            name.push(res.data['coinwin_title']);
                            data.push(res.data['ieorg']);
                            name.push(res.data['ieorg_title']);
                            data.push(res.data['winer']);
                            name.push(res.data['winer_title']);
                            return data,name,listday;
                        }
                    }
                });
            }
            var echartsEl = document.getElementById('echarts-records');
            if (!echartsEl) { return; }
            var echartsRecords = echarts.init(echartsEl, 'walden');
            var data = [];
            var legend = [];
            var listday = [];
            var name = [];
            var tit = '总量';
            var typ = 'line';
            getData();
            var optionRecords = {
                title: {
                    text: '订单统计'
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: legend
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                toolbox: {
                    feature: {
                        saveAsImage: {}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: listday
                },
                yAxis: {
                    type: 'value'
                },
                series: [
                    {
                        name: name[0],
                        type: typ,
                        stack: tit,
                        data: data[0]
                    },
                    {
                        name: name[1],
                        type: typ,
                        stack: tit,
                        data: data[1]
                    },
                    {
                        name: name[2],
                        type: typ,
                        stack: tit,
                        data: data[2]
                    },
                    {
                        name: name[3],
                        type: typ,
                        stack: tit,
                        data: data[3]
                    },
                    {
                        name: name[4],
                        type: typ,
                        stack: tit,
                        data: data[4]
                    },
                    {
                        name: name[5],
                        type: typ,
                        stack: tit,
                        data: data[5]
                    },
                ]
            };
            echartsRecords.setOption(optionRecords);
            window.addEventListener("resize", function () {
                echartsRecords.resize();
            });
        },
        editAdmin: function () {
            ea.listen();
        },
        editPassword: function () {
            ea.listen();
        }
    };
    return Controller;
});
