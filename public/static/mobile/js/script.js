/*
 * @Author: Fox Blue
 * @Date: 2021-07-01 16:41:35
 * @LastEditTime: 2021-09-03 16:35:38
 * @Description: Forward, no stop
 */
if(!window.localStorage){
	alert("浏览器不支持localstorage");
}else{
	var storage=window.localStorage;
}

layui.use(['layer','element','slider','jquery'], function(){
	var layer = layui.layer
	,element = layui.element
	,$ = layui.jquery
	,slider = layui.slider;
	
	$('#userLeft').on('click', function(elem){
		layer.open({
			type: 1,
			id: "LAY_userPopupR",
			anim: -1,
			title: !1,
			closeBtn: !1,
			offset: "l",
			shade: .1,
			shadeClose: !0,
			skin: "layui-anim layui-anim-rl fox-user-left",
			area: "75%",
			content: $("#leftUser")
		})
	});

	$('body').on('click', '.my-left-close', function(){
		layer.closeAll();
	});

	$('body').on("click",".kline_coin_left",function(){
		layer.open({
			type: 1,
			id: "LAY_userPopupL",
			anim: -1,
			title: !1,
			closeBtn: !1,
			offset: "l",
			shade: .1,
			shadeClose: !0,
			skin: "layui-anim layui-anim-rl fox-user-left",
			area: "75%",
			content: $("#kline_lists_box")
		})
	});

	$("#symbol").on('click', function(elem){
		layer.close(layer.index);
	})
	
	window.changelang = function(lang){
		$.get(langSec,{lang:lang},function(res){
			if(res.code == 1){
				window.location.reload()
			}
		})
	}
});

$(document).ready(function() {
	$("#onoffswitch").on('click', function(){
		clickSwitch()
	});
 
	var clickSwitch = function() {
		if ($("#onoffswitch").is(':checked')) {
			$.get(themeSec,{theme:'White'},function(res){
				if(res.code == 1){
					window.location.reload()
				}
			})
		} else {
			$.get(themeSec,{theme:'Dark'},function(res){
				if(res.code == 1){
					window.location.reload()
				}
			})
		}
	};
})


//加法运算   
function floatAdd(arg1,arg2){   
	var r1,r2,m;   
	try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}   
	try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}   
	m=Math.pow(10,Math.max(r1,r2));   
	return (arg1*m+arg2*m)/m;   
}   	  

//减法运算   
function floatSub(arg1,arg2){   
   var r1,r2,m,n;   
   try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}   
   try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}   
   m=Math.pow(10,Math.max(r1,r2));   
   //动态控制精度长度   
   n=(r1>=r2)?r1:r2;   
   return ((arg1*m-arg2*m)/m).toFixed(n);   
}   

//乘法运算   
function floatMul(arg1,arg2)   {    
   var m=0,s1=arg1.toString(),s2=arg2.toString();    
   try{m+=s1.split(".")[1].length}catch(e){}    
   try{m+=s2.split(".")[1].length}catch(e){}    
   return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);    
}    
	  
//除法运算 
function floatp(arg1,arg2){    
	 var t1=0,t2=0,r1,r2;    
	 try{t1=arg1.toString().split(".")[1].length}catch(e){}    
	 try{t2=arg2.toString().split(".")[1].length}catch(e){}    
	 r1=Number(arg1.toString().replace(".","")); 
	 r2=Number(arg2.toString().replace(".",""));    
	 return (r1/r2)*Math.pow(10,t2-t1);    
}
function html_decode(str) 
{ 
	var s = ""; 
	if (str.length == 0) return ""; 
	s = str.replace(/&amp;/g, "&"); 
	s = s.replace(/&lt;/g, "<"); 
	s = s.replace(/&gt;/g, ">"); 
	s = s.replace(/&nbsp;/g, " "); 
	s = s.replace(/&#39;/g, "\'"); 
	s = s.replace(/&quot;/g, "\""); 
	s = s.replace(/<br\/>/g, "\n"); 
	let out = s.replace(/<img[^>]*>/gi, function (match, capture) {
	// return match.replace(/(<img[^>]*)(\/?>)/gi, "$1width='100%' $2") // 添加width="100%"
		return match.replace(/style\s*?=\s*?([‘"])[\s\S]*?\1/ig, 'style="max-width:100%;height:auto;"') // 替换style
	})
	return out; 
} 

function hide_all(){
	$("body").find(".hidebox").fadeOut();
	$(".first_box").fadeIn();
}

function show_kline(obj){
	$(".first_box").fadeOut();
	$("#"+obj).fadeIn();
}
function show_klines(obj,ob){
	$(".first_box").fadeOut();
	$("."+obj).fadeIn();
	if(ob){
		$("."+ob).fadeOut();
	}
}
function hide_klines(obj,ob){
	$(".first_box").fadeOut();
	$("."+obj).fadeOut();
	if(ob){
		$("."+ob).fadeIn();
	}
}
function clickSwitchs() {
	if(theme == 'Dark'){
		var th = 'White';
	}else{
		var th = 'Dark';
	}
	$.get(themeSec,{theme:th},function(res){
		if(res.code == 1){
			window.location.reload()
		}
	})
};
