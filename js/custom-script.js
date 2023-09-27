jQuery(document).ready(function ($) {
    // Po kliknięciu przycisku
    $('.like-button').on('click', function () {
        var button = $(this); // Zapisz przycisk w zmiennej

        var postId = button.data('post-id');
        var container = button.closest('.like-container');
        var image = container.find('.like-img');
        var likeCount = container.find('.like-count');

        var isLiked = image.attr('data-liked') === 'true';

        // Zaktualizuj stan przycisku i obrazka od razu po kliknięciu
        if (isLiked) {
            image.attr('data-liked', 'false');
            image.attr('src', imagePath + 'images/beer-empty.svg');
            button.text('Daj piwko');
        } else {
            image.attr('data-liked', 'true');
            image.attr('src', imagePath + 'images/beer-full.svg');
            button.text('Polubione');
        }

        // Przesyłamy również aktualną ilość "like" jako dane w żądaniu AJAX
        var currentLikes = parseInt(likeCount.text()); // Pobierz aktualną liczbę "like" z interfejsu użytkownika
        $.ajax({
            type: 'POST',
            url: beer_rating_ajax_object.ajax_url,
            data: {
                action: 'update_likes_count',
                post_id: postId,
                is_liked: isLiked,
                current_likes: currentLikes // Przesyłamy aktualną ilość "like"
            },
            success: function (response) {
                if (response.success) {
                    // Zaktualizuj licznik polubień na stronie
                    likeCount.text(response.data.likes_count);
                    console.log(response);
                }
            },
            error: function (error) {
                console.error(error);
            }
        });
    });
});
