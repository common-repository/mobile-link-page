jQuery(document).ready(function($) {
    $('#upload_custom_image_button').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: 'Upload Image',
            multiple: false
        }).open()
        .on('select', function() {
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            var image_id = uploaded_image.toJSON().id;
            $('#custom-image-container').html(
                '<div class="image-wrapper">' +
                    '<img src="' + image_url + '" style="max-width: 100%;" />' +
                    '<span id="remove_custom_image_button" class="remove-custom-image">' +
                        '<img src="' + mobileLinks.pluginUrl + 'xmark-solid.svg" style="width: 24px; height: 24px;" alt="Remove Image">' +
                    '</span>' +
                '</div>'
            );
            $('#mobile_links_custom_photo_id').val(image_id);
        });
    });

    $(document).on('click', '#remove_custom_image_button', function(e) {
        e.preventDefault();
        $('#custom-image-container').html('');
        $('#mobile_links_custom_photo_id').val('');
    });
});
