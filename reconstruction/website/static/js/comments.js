function get_comments(news_info) {
     $.post("/comments",
        {
          ie:"utf-8",
          pn:1,
          rn:10,
          news_info:news_info
        },
        function(data,status){
          alert("Data: " + JSON.stringify(data) + "\nStatus: " + status);
          $('#test').text(JSON.stringify(data));
        }
    );
}
