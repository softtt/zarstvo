$( document ).ready(function()
{
    attachHandlers();
    $('.product-container .product_list_attribute_select').each(function(i, el) {
        updateProductListDisplay($(el));
    });
});

$( document ).ajaxComplete(function()
{
    attachHandlers();

    $('.product-container .product_list_attribute_select').each(function(i, el) {
        updateProductListDisplay($(el));
    });
});

function attachHandlers()
{
    $('.product-container').on('click', '.product_list_color_pick', function(e){
        e.preventDefault();
        updateProductListDisplay($(this));
    });

    $('.product-container').on('change', '.product_list_attribute_select', function(e){
        e.preventDefault();
        updateProductListDisplay($(this));
    });

    $('.product-container').on('click', '.product_list_attribute_radio', function(e){
        e.preventDefault();
        updateProductListDisplay($(this));
    });
}

function updateProductListDisplay(object)
{
    // @todo: add check for product availability and discounts.
    var choosed_id = object.val();
    var product_id = object.data('product-id');
    var parent = $('.product_attributes[data-product-id=' + product_id + ']');
    var prices_block = $('.custom_prices_block[data-product-id=' + product_id + ']');
    var product_container = $('.product-container[data-product-id=' + product_id + ']');

    prices_block.find('.combination-price-block').hide();
    prices_block.find('.combination_attribute_' + choosed_id).show();


    var combination_id = prices_block.find('.combination_attribute_' + choosed_id).data('combination-id');

    product_container.find('.ajax_add_to_cart_button').hide();
    product_container.find('.ajax_add_to_cart_button[data-combination-id=' + combination_id + ']').show();
}
