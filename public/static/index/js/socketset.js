/*
 * @Author: Fox Blue
 * @Date: 2021-04-12 10:29:37
 * @LastEditTime: 2021-09-01 16:43:07
 * @Description: Forward, no stop
 */
// 图表库实例化后储存的函数
var widget = null 
// 进入页面 默认展示的产品
var index_change = 5
// 进入页面 默认展示的产品周期
var index_activeCycle = 1

var totype = 'kline'
var tofind = page_out
var depthnum = 6
var historynum = 13
var localsym = symbol_first

if(!window.localStorage){
  alert("浏览器不支持localstorage");
}else{
  var storage=window.localStorage;
}

// console.log = (function () {
// 	return function () {}
// })(console.log);
// console.error = (function () {
// 	return function () {}
// })(console.error);
// var killErrors = function(value) {
//     return true
// };

// window.onerror = null;
// window.onerror = killErrors;

// window.TradingView.onready 确保在html的dom加载完成后在调用
window.TradingView.onready(function () {
  // chartConfig 在chartConfig.js里面
  // 给chartConfig添加展示周期
  chartConfig.interval = index_activeCycle
  // 给chartConfig添加展示产品
 
  if (storage.page == page_out) {
      localsym = storage.tobol
  }else{
    storage.setItem("tobol",symbol_first);
    localsym = storage.tobol
    storage.setItem("page",page_out)
  }
  chartConfig.symbol = localsym

  get_product(localsym,page_out)
  get_userwallet(localsym,page_out)

  chartConfig.width = '100%',
  chartConfig.height = 480,

  // 初始化 TradingView
  widget = new window.TradingView.widget(chartConfig)

  widget && widget.onChartReady && widget.onChartReady(function () {
    // 这是k线图 展示的 7日均线和30日均线。
    widget.chart().createStudy('Moving Average', false, false, [7], null, {'Plot.linewidth': 2, 'Plot.color': '#2ba7d6'})
    widget.chart().createStudy('Moving Average', false, false, [30], null, {'Plot.linewidth': 2, 'Plot.color': '#de9f66'})
	})
})
var marketDom = document.getElementById('symbol')
var intervalDom = document.getElementById('interval')

// 切换产品
marketDom.addEventListener('click', function (e) {
//   loading =layer.load(1, {shade: [0.1,'#fff']});
  // e.target.dataset.value 就是我们拿到的产品
  localsym = e.target.dataset.value
  storage.setItem("tobol",localsym);
  
  get_product(localsym,page_out)
  get_userwallet(localsym,page_out)
  $("#model_logs").empty();
  $("#bids_box").empty();
  $("#asks_box").empty();
  if(page_out=="deal"){
    $('#historylist').empty();
    $('#nowlist').empty();
  }
  // 5是 5分钟数据
  widget.setSymbol(e.target.dataset.value, 1)
	// 切回平均K线
	widget.chart().setChartType(1)
  $("#interval").find("span").removeClass("active");
  $("#interval").find("span").eq(1).addClass("active");
  // // widget.chart().resetData();

}, false)

// 切换产品周期
intervalDom.addEventListener('click', function (e) {
  // e.target.dataset.value 这个就是获取的产品的周期
	// console.log(e.target.dataset.value)
	// 1 为平均K线； 3 为面积图
	widget.chart().setChartType(e.target.dataset.kline == '1' ? 3 : 1)
    widget.chart().setResolution(e.target.dataset.value)
    // widget.chart().resetData();
    // 这个函数不用看，我为了样式好看 写一个添加删除class
    addClass(intervalDom, e.target)
}, false)

function addClass (fatherDom, dom) {
    [...fatherDom.getElementsByTagName('span')].forEach(function(item){
      item.className = ''
    })
    dom.className = 'active'
  }
function get_product(localsym,page_out){
  var post_cate_id = (typeof cate_id !== 'undefined') ? cate_id : '';
  $.post(Productone,{code:localsym,pages:page_out,cate_id:post_cate_id},function(res){
      if(res.code == 1){
          var data =res.data
          var change = data.change
          // 外汇和大宗商品显示产品title，数字货币显示code
          if($('#trading_top_title').length>0){
              if(data.cate_id == 10 || data.cate_id == 11){
                  $('#trading_top_title').html(data.title)
              }else{
                  var title = localsym
                  title = title.replace('usdt','/USDT').toUpperCase()
                  $('#trading_top_title').html(title)
              }
          }
          if($('.times-tit').length>0){
            if(data.cate_id == 10 || data.cate_id == 11){
                $('.times-tit').html(data.title)
            }else{
                var title = localsym
                title = title.replace('usdt','/USDT').toUpperCase()
                $('.times-tit').html(title)
            }
          }
          if($('#trading_top_price').length>0){
            if(change > 0){
                $('#trading_top_change').removeClass("color-red").addClass("color-green");
                $('#trading_top_change').html('+'+change+'%');
                $('#trading_top_price').removeClass("color-red").addClass("color-green");
                $('#trading_top_price').html(data.close)
            }else if(change < 0){
                $('#trading_top_change').removeClass("color-green").addClass("color-red");
                $('#trading_top_change').html(change+'%');
                $('#trading_top_price').removeClass("color-green").addClass("color-red");
                $('#trading_top_price').html(data.close)
            }
          }
          if($('#trading_top_usd').length>0){
              $('#trading_top_usd').html(data.usd)
          }
          if($('#trading_top_high').length>0){
              $('#trading_top_high').html(data.high)
          }
          if($('#trading_top_low').length>0){
              $('#trading_top_low').html(data.low)
          }
          if($('#trading_top_volume').length>0){
              $('#trading_top_volume').html(data.volume)
          }
          if($('input[name="deal_price"]').length>0){
            $('input[name="deal_price"]').trigger("input"); 
            $('input[name="deal_price"]').val(data.close)
          }
      }
  })
}

function get_userwallet(localsym,page_out){
  $.post(userWallet,{code:localsym,pages:page_out},function(res){
      if(res.code == 1){
        var data =res.data
        if(page_out=="deal"){
          if($('.can-product-box').length>0){
            $('.can-product-box').html(data.money)
            $('.can-product-box-tit').html(data.pro_tit)
          }
          if($('.can-usdt-box').length>0){
            $('.can-usdt-box').html(data.usdt)
            $('.can-usdt-box-tit').html(data.usdt_tit)
          }
          if($('.can-buy-max').length>0){
            $('.can-buy-max').html(data.buy_max)
          }
          if($('.can-sell-max').length>0){
            $('.can-sell-max').html(data.sell_max)
          }
          if($('.ex-buy-min').length>0){
            $('.ex-buy-min').html(data.ex_buy_min)
          }
          if($('.ex-sell-min').length>0){
            $('.ex-sell-min').html(data.ex_sell_min)
          }
        }
        if(page_out=="leverdeal"){
          if($('.pro-tit').length>0){
            $('.can-use-money').html(parseFloat(data.money).toFixed(2))
            $('.pro-tit').html(data.pro_tit)
          }
          if(data.balance_tit && $('.balance-tit').length>0){
            $('.balance-tit').html(data.balance_tit)
          }
          if($('.usdt-account-balance').length>0){
            $('.usdt-account-balance').html(parseFloat(data.usdt_balance).toFixed(2))
            $('.pro-tit-convert').html(data.pro_tit)
            var closePrice = parseFloat(data.close_price);
            if(closePrice > 0){
              var convertAmount = (parseFloat(data.usdt_balance) / closePrice).toFixed(8);
              $('.usdt-convert-coin').html(convertAmount);
            }
          }
          if($('.le-play-time').length>0){
            if(data.le_play_time){
              var le_play_time = data.le_play_time.split(',')
              $('.le-play-time').html(le_play_time[0])
              $('.buy-play-time').html(le_play_time[0])
              $('.sell-play-time').html(le_play_time[0])
            }
            
            $('.le-sx-fee-100').html(data.le_sx_fee_100)
            $('.le-sx-fee').html(data.le_sx_fee)
            $('.le-order-rate').html(data.le_order_rate)
          }
        }
        if(page_out=="seconds"){
          if($('.can-use-money-tit').length>0){
            $('.can-use-money').html(data.money)
            $('.can-use-money-tit').html(data.pro_tit)
          }
          window.onload=function(){
            formRender();
          }
        }
      }
  })
}

$(".depth-biao").on('click',function(){
  var value =$(this).data("value")
  $("#asks_box").html('')
  $("#bids_box").html('')
  if(value == 0){
    depthnum = 6
    $("#asks_box").show()
    $("#bids_box").show()
  }else if(value == 1){
    depthnum = 12
    $("#asks_box").hide()
    $("#bids_box").show()
  }else if(value == 2){
    depthnum = 12
    $("#asks_box").show()
    $("#bids_box").hide()
  }
  $(".depth-biao").css({'border':'1px solid #646464'})
  $(this).css({'border':'1px solid #1717ef'})
});
function getRandom(start,total){
  var num = start / total * 250;
  return num.toFixed(0)
};