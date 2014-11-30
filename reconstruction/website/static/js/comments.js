function get_comments(news_info) {
    $.post("/comments",
        {
            ie:"utf-8",
            pn:1,
            rn:10,
            news_info:news_info
        },
        function(data,status){
            //alert("Data: " + JSON.stringify(data) + "\nStatus: " + status);
            //$('#test').text(JSON.stringify(data));
            for (var i = 0, len = data.length; i < len; ++i) {
                comment = data[i];
                var cm      = $("<div class='comment_entry'></div>");
                var info    = $("<div class='comment_info'></div>");
                var icon    = "<img class='source_icon' src='/static/icons/" + comment.source + ".png' width='15' height='15'>";
                var user    = "<div class='comment_user'>" + comment.user + "</div>";
                var time    = "<div class='comment_time'>" + timestampToADtime(comment.time) + "</div>";
                var content = "<div class='comment_content'>" + comment.content + "</div>";

                info.append(icon, user, time);
                cm.append(info, content);
                $('#hot_comments').append(cm);
            }
        }
    );
}
