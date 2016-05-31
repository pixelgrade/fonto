jQuery(document).ready(function ($) {

    $(".edit_post_link").click(
        function () {
            var post_id = parseInt($(this).attr("id").match(/\d+/)[0], 10);
            $(".loader").show();
            $.post(ajaxurl, {
                    post_id: post_id,
                    action: 'update_post_form'

                }, function (response) {
                    $(".modalBox").show();
                    $(".popupBox").show();
                    $("#edit_post_box").html(response);
                    $(".loader").hide();
                }
            );

        }
    );

    $("#edit_post_box").on("submit", "#edit_post_form", function (e) {
        $(".loader").show();
        $.post(ajaxurl, $(this).serialize(), function (response) {

            var results = $.parseJSON(response);
            var post_id = $("#post_id").val();

            if (results["post_result"]) {
                $("#edit_post_link_" + post_id).text($("#post_title").val());
            }

            $(".popupBox").fadeOut();
            $(".modalBox").fadeOut();
            $(".loader").hide();


        });

        e.preventDefault()
    });

    $('.plugin_data').on("click", ".popupCloser", function () {
        $(".popupBox").fadeOut();
        $(".modalBox").fadeOut();

    });
});