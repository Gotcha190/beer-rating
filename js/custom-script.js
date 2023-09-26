jQuery(document).ready(function ($) {
    // Po kliknięciu przycisku
    $('.like-button').on('click', function () {
        // Znajdź przycisk i obrazek w ramach tego samego kontenera
        var postId = $(this).data('post-id');
        var container = $(this).closest('.like-container');
        var image = container.find('.like-img');
        var likeCount = container.find('.like-count');

        var isLiked = image.attr('data-liked') === 'true';

        $.ajax({
            type: 'POST',
            url: beer_rating_ajax_object.ajax_url,
            data: {
                action: 'update_likes_count',
                post_id: postId,
                is_liked: isLiked
            },
            success: function (response) {
                if (response.success) {
                    // Zaktualizuj licznik polubień na stronie
                    likeCount.text(response.data.likes_count);
                    console.log(response);
                    console.log(response.data.likes_count);
                    if (image.attr('data-liked') === 'true') {
                        image.attr('data-liked', 'false');
                        image.attr('src', imagePath + 'images/beer-empty.svg'); // Ścieżka do obrazka, który nie jest polubiony
                    } else {
                        image.attr('data-liked', 'true');
                        image.attr('src', imagePath + 'images/beer-full.svg'); // Ścieżka do obrazka, który jest polubiony
                    }
                }
            }
        });
    });
});