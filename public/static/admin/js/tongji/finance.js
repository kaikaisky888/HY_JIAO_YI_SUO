/*
 * @Author: Fox Blue
 * @Date: 2021-10-09 15:12:34
 * @LastEditTime: 2021-10-11 21:33:17
 * @Description: Forward, no stop
 */
define(["jquery", "easy-admin"], function ($, ea) {
    var page = 'tongji.finance/index';
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
                // if(t==1){
                //     if(!times){
                //         layer.msg('请选择日期范围', {icon: 5});
                //         return false;
                //     }
                // }
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
                        var html = '';
                        $.each(data,function(i,item){
                            if(item['resault']>0){
                                var st = 'style="color:green"';
                            }else if(item['resault']<0){
                                var st = 'style="color:red"';
                            }else{
                                var st = '';
                            }
                            html += '<tr><td>' + item['title'] + '</td>' +
                                '<td>' + item['recharge'] + '<br>' + item["recharge_usd"] + '$</td>' +
                                '<td>' + item['withdraw'] + '<br>' + item['withdraw_usd'] + '$</td>' +
                                '<td '+st+'>' + item['resault'] + '<br>' + item['resault_usd'] + '$</td></tr>';
                        });
                        $('#list').html(html);
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