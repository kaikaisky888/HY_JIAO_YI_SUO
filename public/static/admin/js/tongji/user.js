/*
 * @Author: Fox Blue
 * @Date: 2021-10-09 15:12:34
 * @LastEditTime: 2021-10-09 20:50:11
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {
    var page = 'tongji.user/index';
    var Controller = {
    
        index: function () {
            ea.listen();
            $('.search-reset').on("click", function () {
                window.location.reload();
            });
            $('.search-submit').on("click", function () {
                listdata(1);
            });
            window.listdata = function(t){
                var times = $("#times").val();
                if(t==1){
                    if(!times){
                        layer.msg('请选择日期范围', {icon: 5});
                        return false;
                    }
                }
                ea.request.post({
                    url: page,
                    prefix: true,
                    data: {
                        times: times
                    },
                }, function (res) {
                    if(res.code){
                        var data = res.data;
                        //处理相关参数
                        if($("#list_a").length > 0){
                            $("#list_a").html('<h1 class="no-margins">'+data.count_a+'</h1>');
                        }
                        if($("#list_b").length > 0){
                            $("#list_b").html('<h1 class="no-margins">'+data.count_b+'</h1>');
                        }
                        if($("#list_c").length > 0){
                            $("#list_c").html('<h1 class="no-margins">'+data.count_c+'</h1>');
                        }
                    }
                });
            }
            listdata(0);
        }
    };
    return Controller;
    });