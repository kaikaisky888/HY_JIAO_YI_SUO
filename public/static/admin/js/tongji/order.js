/*
 * @Author: Fox Blue
 * @Date: 2021-10-09 15:12:34
 * @LastEditTime: 2021-10-11 21:33:39
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {
    var page = 'tongji.order/index';
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
                var username = $("#username").val();
                
                ea.request.post({
                    url: page,
                    prefix: true,
                    data: {
                        times: times,
                        username: username
                    },
                }, function (res) {
                    if(res.code){
                        var data = res.data;
                        //处理相关参数
                        if($("#count_deal").length > 0){
                            $("#count_deal").html('<h1 class="no-margins">'+data.count_deal+'</h1>');
                        }
                        if($("#count_seconds").length > 0){
                            $("#count_seconds").html('<h1 class="no-margins">'+data.count_seconds+'</h1>');
                        }
                        if($("#count_leverdeal").length > 0){
                            $("#count_leverdeal").html('<h1 class="no-margins">'+data.count_leverdeal+'</h1>');
                        }
                        if($("#count_good").length > 0){
                            $("#count_good").html('<h1 class="no-margins">'+data.count_good+'</h1>');
                        }
                        if($("#count_ieorg").length > 0){
                            $("#count_ieorg").html('<h1 class="no-margins">'+data.count_ieorg+'</h1>');
                        }
                        if($("#count_winer").length > 0){
                            $("#count_winer").html('<h1 class="no-margins">'+data.count_winer+'</h1>');
                        }
                    }else{
                        layer.msg(res.msg);
                    }
                });
            }
            listdata(0);
        }
    };
    return Controller;
    });