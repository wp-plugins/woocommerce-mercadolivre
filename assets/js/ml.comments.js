jQuery(document).ready(function($) {
    $( "#ml-comments" ).comments({
        getCommentsUrl: obj.get_url,
        postCommentUrl: obj.post_url,
        deleteCommentUrl: obj.delete_url,
        displayHeader: false,
        displayCount: false,
        loadWhenVisible: false,
        displayAvatar: true,
        localization: {
            commentPlaceHolderText: obj.comment_placeholder,
            sendButtonText: obj.send_text,
            replyButtonText: obj.reply_text,
            deleteButtonText: obj.delete_text
        },
        callback: {
            afterCommentAdd: function(comment) {
                $("li.comment[data-commentid='" + comment.ParentId + "'] a[data-action='replay']").remove();
                $("li.comment[data-commentid='" + comment.ParentId + "'] a[data-action='delete']").remove();
            }
        }
    });

    $("#ml-comments div.newComment").remove();
});