//***********************************
//***********************************
if(site_type == 'test'){
    var SOCKET_URL = local_socket;
}else if(site_type == 'online'){
    var SOCKET_URL = api_socket;
}

var socket = {
	socket: null, // socket name
	realTimeData: null, // 请求实时数据的参数
	intervalObj: null, // 定时器的名字
	lastRealTimeData: null, // 上一次请求的产品
	interval: null,
	sendData(historyData, realTimeDatas, history) {
		var _this = this
		// 储存历史数据
		this.historyData = historyData
		this.realTimeData = realTimeDatas
		
		// 如果上一次订阅过产品
		if (this.lastRealTimeData) {
			if(page_out=="deal" || page_out=="leverdeal" || page_out=="seconds"){
				// 如果不是订阅历史产品 那么肯定就是切换周期咯 或者 切换产品
				// 那么就取消订阅上一次的产品实时数据
				if (!history) {
					// console.log('取消订阅'+ this.lastRealTimeData)
					this.sendWsRequest({
					"unsub": this.lastRealTimeData,
					"type": totype,
					"find": tofind,
					"uid": uid,
					"id": "id10"
					})
				}
			
				// 请求这一次的历史
				this.sendWsRequest(this.historyData)
				
				// 如果不是订阅历史产品 那么肯定就是切换周期咯 或者切换产品咯 
				// 那么就订阅一下 这次产品的或者周期的 实时数据
				if (!history) {
					// console.log('订阅新的'+ realTimeDatas)
					this.sendWsRequest({
						"req": realTimeDatas,
						"type": totype,
						"find": tofind,
						"uid": uid,
						"id": "id10"
					})
				}
			}
			
		} else {
			// 如果是第一次订阅，就是说刚进入交易所，
			// 先存起来这一次请求的产品 作为历史产品
			this.lastRealTimeData = this.realTimeData
			// 然后 初始化一下websocket
			this.initWs()
		}
	},
	initWs() {
		var _this = this
		if(this._subInterval){ clearInterval(this._subInterval); this._subInterval = null; }
		if(this.socket){ try{ this.socket.onclose = null; this.socket.onerror = null; this.socket.close(); }catch(e){} }
		this.socket = new WebSocket(SOCKET_URL)
		this.socket.onopen = () => {
			this.sendWsRequest(this.historyData)
			if(page_out=="deal" || page_out=="leverdeal" || page_out=="seconds"){
				_this._subInterval = setInterval(function(){
					_this.sendWsRequest({
						"sub": _this.historyData.req,
						"type": totype,
						"find": tofind,
						"uid": uid,
						"id": "id12"
					})
				}, 3000);
			}
			this.pingMe()
		}
		this.socket.onmessage = resp => {
			this.message(resp)
		}
		this.socket.onclose = () => {
			this.close()
		}
		this.socket.onerror = err => {
			this.error(err)
		}
	},
	error(err) {
		console.log(err, 'depth-socket::error')
	},
	close() {
		// 如果websocket关闭的话，延迟重连避免重复连接
		var _this = this
		if(this._reconnectTimer){ clearTimeout(this._reconnectTimer); }
		this._reconnectTimer = setTimeout(function(){ _this.initWs() }, 2000)
	},
	message(resp) {
		let this_ = this
		let msg = JSON.parse(resp.data);
        // console.log(msg)
		// 如果是实时数据触发Event('realTime') 喂数据
		// console.log(this_.realTimeData)
		if(msg.type == "allticker"){
			// console.log(msg);
			var ticker = msg.ticker
			for (var i = 0; i < ticker.length; i++) {
				var change = parseFloat(ticker[i].change);
				if(page_out=="home"){
					if(change > 0){
						$("#change_"+ticker[i].market).removeClass("bg-red").addClass("bg-green");
						$("#change_"+ticker[i].market).html('+'+change+'%');
						$("#change_top_"+ticker[i].market).removeClass("bg-red").addClass("bg-green");
						$("#change_top_"+ticker[i].market).html('+'+change+'%');
					}else{
						$("#change_"+ticker[i].market).removeClass("bg-green").addClass("bg-red");
						$("#change_"+ticker[i].market).html(change+'%');
						$("#change_top_"+ticker[i].market).removeClass("bg-green").addClass("bg-red");
						$("#change_top_"+ticker[i].market).html(change+'%');
					}
					$("#price_"+ticker[i].market).html(ticker[i].close);
					$("#price_top_"+ticker[i].market).html(ticker[i].close);
					$("#approx_top_"+ticker[i].market).html("≈$ "+ticker[i].close);
				}else if(page_out=="market"){
					if(change > 0){
						$("#change_"+ticker[i].market).removeClass("bg-red").addClass("bg-green");
						$("#change_"+ticker[i].market).html('+'+change+'%');
					}else  if(change < 0){
						$("#change_"+ticker[i].market).removeClass("bg-green").addClass("bg-red");
						$("#change_"+ticker[i].market).html(change+'%');
					}
					$("#price_"+ticker[i].market).html(ticker[i].close);
					$("#high_"+ticker[i].market).html(ticker[i].high);
					$("#low_"+ticker[i].market).html(ticker[i].low);
					$("#amount_"+ticker[i].market).html(ticker[i].volume);
				}
				if(ticker[i].canvas){
					if($('#svg_'+ticker[i].market).length>0){
						var canvas = ticker[i].canvas;
						gobarchart('#svg_'+ticker[i].market, canvas, change,i);
					}
				}
				if(page_out=="deal" || page_out=="leverdeal"){
					$('#left_price_'+ticker[i].market).html(ticker[i].close);
					$("#left_list_"+localsym).addClass("active").siblings().removeClass("active")
					if(change > 0){
						$("#left_change_"+ticker[i].market).removeClass("color-red").addClass("color-green");
						$("#left_change_"+ticker[i].market).html('+'+change+'%');
					}else  if(change < 0){
						$("#left_change_"+ticker[i].market).removeClass("color-green").addClass("color-red");
						$("#left_change_"+ticker[i].market).html(change+'%');
					}
					if(ticker[i].market == localsym){
						if($('#right_price_usd').length>0){
							$('#right_price_usd').html(ticker[i].usd)
						}
						if($('#right_price').length>0){
							$('#right_price').html(ticker[i].close)
						}
						if($('#trading_top_usd').length>0){
							$('#trading_top_usd').html(ticker[i].usd)
						}
						if($('#trading_top_price').length>0){
							if(change > 0){
								$('#trading_top_price').removeClass("color-red").addClass("color-green");
								$('#trading_top_change').removeClass("color-red").addClass("color-green");
								$('#trading_top_change').html('+'+change+'%');
							}else if(change < 0){
								$('#trading_top_price').removeClass("color-green").addClass("color-red");
								$('#trading_top_change').removeClass("color-green").addClass("color-red");
								$('#trading_top_change').html(change+'%');
							}
							$('#trading_top_price').html(ticker[i].close)
						}
						if($('#trading_top_high').length>0){
							$('#trading_top_high').html(ticker[i].high)
						}
						if($('#trading_top_low').length>0){
							$('#trading_top_low').html(ticker[i].low)
						}
						if($('#trading_top_volume').length>0){
							$('#trading_top_volume').html(ticker[i].volume)
						}
					}
				}
				if(page_out=="seconds"){
					$("#left_list_"+localsym).addClass("layui-this").siblings().removeClass("layui-this")
					$("#left_list_show_"+localsym).addClass("layui-show").siblings().removeClass("layui-show")
					if(ticker[i].market == localsym){
						$("#trading_top_volume_"+ticker[i].market).html(ticker[i].amount);
					}
					$('#trading_top_usd_'+ticker[i].market).html(ticker[i].usd)
					if(change > 0){
						$("#trading_top_change_"+ticker[i].market).removeClass("color-red").addClass("color-green");
						$("#trading_top_change_"+ticker[i].market).html('+'+change+'%');
						$("#trading_top_price_"+ticker[i].market).removeClass("color-red").addClass("color-green");
						$("#trading_top_price_"+ticker[i].market).html(ticker[i].close);
					}else  if(change < 0){
						$("#trading_top_change_"+ticker[i].market).removeClass("color-green").addClass("color-red");
						$("#trading_top_change_"+ticker[i].market).html(change+'%');
						$("#trading_top_price_"+ticker[i].market).removeClass("color-red").addClass("color-green");
						$("#trading_top_price_"+ticker[i].market).html(ticker[i].close);
					}
				}
			}
		}
		
		if(page_out=="deal" || page_out=="leverdeal"){
			if(msg.depthlist && msg.market == localsym) {
				// console.log(msg.market+'|'+localsym);
				var bids = msg.depthlist.bid;
				var asks = msg.depthlist.ask;
				var htmlbids = ''
				var htmlasks = ''
				var price
				var total
				var wt
				var bprice
				var btotal
				var bwt
				var totals
				var btotals
				// console.log(bids.length)
				btotals = 0;
				for (var i = 0; i < bids.length; i++) {
					if(i < depthnum){
						btotals = floatAdd(btotals,parseFloat(bids[i]['total']).toFixed(4));
					}
				}
				for (var i = 0; i < bids.length; i++) {
					if(bids[i]['price'] > 1){
						bprice = parseFloat(bids[i]['price']).toFixed(4)
					}else{
						bprice = parseFloat(bids[i]['price']).toFixed(8)
					}
					if(bids[i]['total'] > 10000000){
						btotal = parseFloat(bids[i]['total']).toFixed(0)
					}else{
						btotal = parseFloat(bids[i]['total']).toFixed(6)
					}
					bwt = getRandom(btotal,btotals);
					if(i < depthnum){
						htmlbids +='<tr class="bgbgs" style="background-size: '+bwt+'% 100%;"><td class="color-green">'+bprice+'</td><td>'+btotal+'</td></tr>'
					}
				}
				$("#bids_box").html(htmlbids)
				totals = 0;
				for (var i = 0; i < asks.length; i++) {
					if(i < depthnum){
						totals = floatAdd(totals,parseFloat(asks[i]['total']).toFixed(4));
					}
				}
				for (var i = 0; i < asks.length; i++) {
					if(asks[i]['price'] > 1){
						price = parseFloat(asks[i]['price']).toFixed(4)
					}else{
						price = parseFloat(asks[i]['price']).toFixed(8)
					}
					if(asks[i]['total'] > 10000000){
						total = parseFloat(asks[i]['total']).toFixed(0)
					}else{
						total = parseFloat(asks[i]['total']).toFixed(6)
					}
					wt = getRandom(total,totals);
					if(i < depthnum){
						htmlasks +='<tr class="bgbg" style="background-size: '+wt+'% 100%;"><td class="color-red">'+price+'</td><td>'+total+'</td></tr>'
					}
				}
				$("#asks_box").html(htmlasks)
			}
		}
		if(page_out=="deal"){
			if(msg.tradelog && msg.market == localsym){
				var log =msg.tradelog
				var myDate;
				var rows;
				var price;
				var num;
				rows = document.getElementById("model_logs").rows.length
				// console.log(log.price)
				if(log && log.price){
					myDate = dateFormat("HH:MM:SS", new Date(log.time*1000));
					if(rows >= historynum){
						var trs = $("#model_logs").find("tr")
						for(var t = historynum;t<rows;t++){
							if(trs[t]){
								trs[t].remove()
							}
						}
					}
					if($("#"+log.tradeId).length <= 0){
						var logPrice = parseFloat(log.price);
						var logNum = parseFloat(log.num);
						if(logPrice > 1){
							price = logPrice.toFixed(4)
						}else{
							price = logPrice.toFixed(8)
						}
						if(logNum > 10000000){
							num = logNum.toFixed(0)
						}else{
							num = logNum.toFixed(6)
						}
						if(log.trade_type ==2){
							$("#model_logs").prepend('<tr id='+log.tradeId+'><td>' + myDate + '</td>' + "<td class='color-green'>"+price+"</td>"+"<td>"+num+"</td>" + '</tr>');
							return;
						}else{
							$("#model_logs").prepend('<tr id='+log.tradeId+'><td>' + myDate + '</td>' + "<td class='color-red'>"+price+"</td>"+"<td>"+num+"</td>" + '</tr>');
							return;
						}
					}
				}
			}
		}
		if(page_out=="deal" || page_out=="leverdeal" || page_out=="seconds"){
			this_.lastRealTimeData = this_.realTimeData
			// 如果是历史数据触发Event('data') 绘制数据
			if (msg.req == this_.realTimeData  && msg.id && msg.data && Array.isArray(msg.data)) {
				// console.log(msg)
				Event.emit('data', msg.data)
			}
			if(msg.tick && msg.market) {				
				var data = msg.tick
				var change = data.change
				if(msg.market == localsym){
					Event.emit('realTime', data)
					if($('#trading_top_price').length>0){
						if(change > 0){
							$('#trading_top_change').removeClass("color-red").addClass("color-green");
							$('#trading_top_change').html('+'+change+'%');
							$('#trading_top_price').removeClass("color-red").addClass("color-green");
							$('#trading_top_price').html(data.close)
							$('.trading_top_change_m').removeClass("color-red").addClass("color-green");
							$('.trading_top_change_m').html('+'+change+'%');
						}else if(change < 0){
							$('#trading_top_change').removeClass("color-green").addClass("color-red");
							$('#trading_top_change').html(change+'%');
							$('#trading_top_price').removeClass("color-green").addClass("color-red");
							$('#trading_top_price').html(data.close)
							$('.trading_top_change_m').removeClass("color-green").addClass("color-red");
							$('.trading_top_change_m').html(change+'%');
						}
						$('#right_price').html(data.close)
					}
					if($('#trading_top_usd').length>0){
						$('#trading_top_usd').html(data.usd)
					}
					if($('#right_price').length>0){
						$('#right_price').html(data.close)
					}
					if($('#close_price').length>0){
						$('#close_price').trigger("input"); 
						$('#close_price').val(data.close)
					}
					if($('#right_price_usd').length>0){
						$('#right_price_usd').html(data.usd)
					}
					if($('.right_price_this').length>0){
						var can_use_money = parseFloat($(".can-use-money").text())
						var right_price = parseFloat($('#right_price').text())
						var user_this = floatMul(right_price,can_use_money)
						$('.right_price_this').html(user_this.toFixed(8))
					}
					if($('#times-sec-price').length>0){
						$("#times-sec-change").text(change);
						if(change > 0){
							$('#times-sec-price').removeClass("color-red").addClass("color-green");
							$('#times-sec-price').html(data.close)
						}else if(change < 0){
							$('#times-sec-price').removeClass("color-green").addClass("color-red");
							$('#times-sec-price').html(data.close)
						}
					}
				}
				if(page_out=="leverdeal"){//可用余额
					if(msg.usermoney){
						var money = msg.usermoney;
						if($('.can-use-money').length>0){
				// 			$('.can-use-money').html(money[localsym])
						}
					}
				}
			}
			
		}

	},
	pingMe(){
		var this_ =this
		//响应服务器，避免断开连接
		var timesRun = false;
		if(timesRun == false && this_.socket.readyState===1) {
			var interval = setInterval(function(){
				this_.socket.send(JSON.stringify({
					type:'ping'
				}));
				// console.log('ping')
			}, 25000);
			timesRun = true
		}
	},
	checkSendMessage(options) {
		// 这里处理websocket 连接不上的问题
		var checkTimes = 10
		var i = 0
		this.intervalObj = setInterval(() => {
			i += 1
			if (this.socket.readyState === 1) {
				// ...
				this.socket.send(options)
				clearInterval(this.intervalObj)
				return
			}
			if (i >= checkTimes) {
				clearInterval(this.intervalObj)
				// console.log('send post_data_str timeout.')
			}
		}, 500)
	},
	sendWsRequest(options) {
		// console.log('1此次发送'+ JSON.stringify(options))
		switch (this.socket.readyState) {
			case 0:
				this.checkSendMessage(JSON.stringify(options))
				break
			case 1:
				this.socket.send(JSON.stringify(options))
				break
			case 2:
				console.log('1ws关闭状态')
				break
			case 3:
				this.initWs()
				break
			default:
				console.log('1ws未知错误')
		}
	}
}


//格式化时间
function dateFormat(fmt, date) {
    let ret;
    const opt = {
        // "Y+": date.getFullYear().toString(),        // 年
        // "m+": (date.getMonth() + 1).toString(),     // 月
        // "d+": date.getDate().toString(),            // 日
        "H+": date.getHours().toString(),           // 时
        "M+": date.getMinutes().toString(),         // 分
        "S+": date.getSeconds().toString()          // 秒
        // 有其他格式化字符需求可以继续添加，必须转化成字符串
    };
    for (let k in opt) {
        ret = new RegExp("(" + k + ")").exec(fmt);
        if (ret) {
            fmt = fmt.replace(ret[1], (ret[1].length == 1) ? (opt[k]) : (opt[k].padStart(ret[1].length, "0")))
        }
        ;
    }
    ;
    return fmt;
}

function page_send(type, find, time){
	var interval = null
	interval = setInterval(function(){
		socket.sendData({
			type: type,
			find: find,
		},null,null)
	}, time);
}