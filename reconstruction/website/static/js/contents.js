function init_contents(news_info) {
    for (var i = 0, len = news_info.length; i < len; ++i) {
        var content = $("#content_" + news_info[i].source_news_id);
        content.hide();

        var link_block = $("<div class='source_address_block'></div>");

        var text = $("<div class='description'>原文地址：</div>");

        var a_tag = $("<a class='source_address element_hover inline' href='" + news_info[i].source_news_link + "'>" + news_info[i].source_news_link + "</a>");

        link_block.append(text);
        link_block.append(a_tag);
        content.append(link_block);

        //append title
        var title_block = $("<div class='source_title_block'></div>");

        var text = $("<div class='description'>原文标题：</div>");

        var title = $("<div class='source_title inline'>" + news_info[i].title + "</div>");
        
        if(head_title == '') {
            head_title = news_info[i].title;
        }

        title_block.append(text);
        title_block.append(title);
        content.append(title_block);

        //append picture
        if ('ext' in news_info[i] && news_info[i].ext instanceof Object &&  
                news_info[i].ext.pic_url !== undefined) {

            var pic_block = $("<div class='source_pic_block'></div>");                                                                                                                                                                       
            var pic = $("<img src='" + news_info[i].ext.pic_url + "'>");

            pic_block.append(pic);
            content.append(pic_block);
        }

        //append passage
        var passage_block = $("<div class='source_passage_block'></div>");
        var passage       = $("<div class='source_passage'>" + news_info[i].content + "</div>");

        passage_block.append(passage);
        content.append(passage_block);
    }
}

function init_events() {
    $('.hide_show_btn').hide();
    var lastshowtabid = '';

    //show or hide passages
    $('.source_icon').click(function(){
        var showtabid = 'content_' + $(this).attr('name');
        if (lastshowtabid != showtabid) {
            if (lastshowtabid != '') {
                lastshowtabid = lastshowtabid.trim();
                $('#' + lastshowtabid).hide();
            }
            $('#' + showtabid).show();
            lastshowtabid = showtabid;
            $('#show_source').hide();
            $('#hide_source').show();
        }
    });
    $('#hide_source').click(function(){
        if (lastshowtabid != '') {
            $('#' + lastshowtabid).hide();
            $(this).hide();
            $('#show_source').show();
            lastshowtabid = ' ' + lastshowtabid;
        }
    });
    $('#show_source').click(function(){
        if (lastshowtabid != '') {
            lastshowtabid = lastshowtabid.trim();
            $('#' + lastshowtabid).show();
            $(this).hide();
            $('#hide_source').show();
        }
    });


}
