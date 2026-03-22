/*
 * @Author: Fox Blue
 * @Date: 2021-07-01 16:41:35
 * @LastEditTime: 2021-09-03 16:31:06
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
	
	element.on('nav(demo)', function(elem){
		//console.log(elem)
		layer.msg(elem.text());
	});
	
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
function downloadIamge(selector, name) {
    var image = new Image()
    // 解决跨域 Canvas 污染问题
    image.setAttribute('crossOrigin', 'anonymous')
    image.onload = function () {
        var canvas = document.createElement('canvas')
        canvas.width = image.width
        canvas.height = image.height

        var context = canvas.getContext('2d')
        context.drawImage(image, 0, 0, image.width, image.height)
        var url = canvas.toDataURL('image/png')

        // 生成一个a元素
        var a = document.createElement('a')
        // 创建一个单击事件
        var event = new MouseEvent('click')

        // 将a的download属性设置为我们想要下载的图片名称，若name不存在则使用‘下载图片名称’作为默认名称
        a.download = name || '下载图片名称'
        // 将生成的URL设置为a.href属性
        a.href = url

        // 触发a的单击事件
        a.dispatchEvent(event)
    }

    image.src = document.querySelector(selector).src
}
