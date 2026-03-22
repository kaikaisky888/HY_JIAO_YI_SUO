if(('standalone' in window.navigator)&&window.navigator.standalone){
var noddy,remotes=false;
document.addEventListener('click',function(event){
noddy=event.target;
while(noddy.nodeName!=='A'&& noddy.nodeName!=='HTML' && noddy.parentNode) 
  noddy=noddy.parentNode;
if(noddy.nodeName==='A' && 'href' in noddy && noddy.href.indexOf('http')!==-1&&(noddy.href.indexOf(document.location.host)!==-1||remotes)){
event.preventDefault();
document.location.href=noddy.href;
}
},false);
        }

document.addEventListener('plusready', function() {  
            var first = null;  
            var webview = plus.webview.currentWebview();  
            plus.key.addEventListener('backbutton', function() {  
                webview.canBack(function(e) {  
                    if (e.canBack) {  
                        webview.back(); //这里不建议修改自己跳转的路径  
                    } else {  
                        //首次按键，提示‘再按一次退出应用’  
                        if (!first) {  
                            first = new Date().getTime(); //获取第一次点击的时间戳  
                            // console.log('再按一次退出应用');//用自定义toast提示最好  
                            // toast('双击返回键退出应用'); //调用自己写的吐丝提示 函数  
                            plus.nativeUI.toast("再按一次退出应用", {  
                                duration: 'short'  
                            }); //通过H5+ API 调用Android 上的toast 提示框  
                            setTimeout(function() {  
                                first = null;  
                            }, 1000);  
                        } else {  
                            if (new Date().getTime() - first < 1000) { //获取第二次点击的时间戳, 两次之差 小于 1000ms 说明1s点击了两次,  
                                plus.runtime.quit(); //退出应用  
                            }  
                        }  
                    }  
                })  
            });  
        });