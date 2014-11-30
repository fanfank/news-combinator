//给window对象绑定scroll事件
$(window).bind("scroll", function() {
    //获取网页文档对象滚动条的垂直偏移
    var scrollTopNum = $(document).scrollTop(),
        winHeight = $(window).height(),//获取浏览器当前窗口的高度
        returnTop = $("div.returnTop");

    //滚动条的垂直偏移大于0时显示，反之隐藏
    (scrollTopNum > 0) ? returnTop.fadeIn("fast") : returnTop.fadeOut("fast");

    //给IE6定位
    if (!-[1,]&&!window.XMLHttpRequest) {
        returnTop.css("top", scrollTopNum + winHeight - 50);
    }

});

//点击按钮后，滚动条的垂直方向的值逐渐变为0，也就是滑动向上的效果
$("div.returnTop").click(function(){
    $("html, body").animate({scrollTop: 0}, 100);
});

function fillWith(str, len, c, isBack) {
    str = str.toString();
    if(str.length >= len) {
        return str;
    }
    var padding = '';
    for(var i = 0; i < len - str.length; i++) {
        padding += c;
    }
    if(isBack) {
        return str + padding;
    } else {
        return padding + str;
    }
}

function timestampToADtime(timestamp) {
    var day     = new Date(timestamp * 1000);
    var year    = fillWith(day.getFullYear(), 4, '0', false);
    var month   = fillWith(day.getMonth() + 1, 2, '0', false);
    var date    = fillWith(day.getDate() + 1, 2, '0', false);
    var hours    = fillWith(day.getHours(), 2, '0', false);
    var minutes = fillWith(day.getMinutes(), 2, '0', false);
    var seconds = fillWith(day.getMinutes(), 2, '0', false);
    return (year+"-"+month+"-"+date+" "+hours+":"+minutes+":"+seconds);
}
