jQuery(document).ready(function($) {
    var options = {
        empty_value: 'null',
        indexed: true,
        on_each_change: obj.url,
        preselect: { 'ml_category_id' : obj.pre_selected },
        preselect_only_once: true,
        choose: function( level ) {
            return ( ( level == 0 )?obj.first_level_label:obj.level_label );
        },
        loading_image: obj.image_url,
    };

    $.getJSON( obj.url , function( tree ) {
        $('#ml_category_id').optionTree( tree, options ).change(function() {
            $('#ml_variations_content').remove();
            $('#ml_category_id').parent().append('<img src="' + obj.image_url + '" class="check_variation">');
            $.getJSON( obj.check_variation_url + '&category=' + this.value + '&product_id=' + woocommerce_admin_meta_boxes.post_id , function( data ) {
                $('.check_variation').remove();
                $('#ml_product_data .ml_required_fields').append( data );
            });
            $('#ml_shipping_mode').parent().append('<img src="' + obj.image_url + '" class="check_shipping">');
            $('#ml_shipment_content').remove();
            $('#ml_shipping_mode option').remove();
            $.getJSON( obj.get_shipping_modes_url + '&category=' + this.value , function( data ) {
                $.each(data, function(index, value) {
                    $('#ml_shipping_mode').append($('<option>', {
                        value: value.id,
                        text:  value.name
                    }));
                });
                $('.check_shipping').remove();
            });
        });
    });
});