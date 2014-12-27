function get_comments(news_info) {
    $.post("/comments",
        {
            ie:"utf-8",
            pn:1,
            rn:10,
            news_info:news_info
        },
        function(data,status){
            //设置评论概括
            var cnt = 0;
            var has_sentence = false;
            var abstraction_block = $(".comment_abstract_content");
            var cmt_classes_num = 2;
            for (var i = 0, len = data.abstract.length; i < len; ++i) {
                var sentence = data.abstract[i].trim();
                if (sentence.length == 0) {
                    continue;
                }

                if (has_sentence) {
                    var seperator_ele = $("<div></div>");
                    seperator_ele.addClass("seperator");
                    seperator_ele.text(' | ');
                    abstraction_block.append(seperator_ele);
                }

                var stc_ele = $("<div></div>");
                stc_ele.addClass("cmt_abs_" + cnt % cmt_classes_num);
                stc_ele.addClass('wrap_line');
                stc_ele.text(sentence);
                abstraction_block.append(stc_ele);

                has_sentence = true;
                cnt += 1;
            }
            
            //设置详细评论
            for (var i = 0, len = data.comments.length; i < len; ++i) {
                var comment = data.comments[i];
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
