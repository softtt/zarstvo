<tbody>
    {assign var='odd' value=0}
    {assign var='have_non_virtual_products' value=false}
    <tr class="empty-line"></tr>
    {foreach $products as $product}
        {if $product.is_virtual == 0}
            {assign var='have_non_virtual_products' value=true}
        {/if}
        {assign var='productId' value=$product.id_product}
        {assign var='productAttributeId' value=$product.id_product_attribute}
        {assign var='quantityDisplayed' value=0}
        {assign var='odd' value=($odd+1)%2}
        {assign var='ignoreProductLast' value=isset($customizedDatas.$productId.$productAttributeId) || count($gift_products)}
        {* Display the product line *}
        {include file="$tpl_dir./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}
        {* Then the customized datas ones*}
        {if isset($customizedDatas.$productId.$productAttributeId)}
            {foreach $customizedDatas.$productId.$productAttributeId[$product.id_address_delivery] as $id_customization=>$customization}
                <tr
                    id="product_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}_{$product.id_address_delivery|intval}"
                    class="product_customization_for_{$product.id_product}_{$product.id_product_attribute}_{$product.id_address_delivery|intval}{if $odd} odd{else} even{/if} customization alternate_item {if $product@last && $customization@last && !count($gift_products)}last_item{/if}">
                    <td></td>
                    <td colspan="3">
                        {foreach $customization.datas as $type => $custom_data}
                            {if $type == $CUSTOMIZE_FILE}
                                <div class="customizationUploaded">
                                    <ul class="customizationUploaded">
                                        {foreach $custom_data as $picture}
                                            <li><img src="{$pic_dir}{$picture.value}_small" alt="" class="customizationUploaded" /></li>
                                        {/foreach}
                                    </ul>
                                </div>
                            {elseif $type == $CUSTOMIZE_TEXTFIELD}
                                <ul class="typedText">
                                    {foreach $custom_data as $textField}
                                        <li>
                                            {if $textField.name}
                                                {$textField.name}
                                            {else}
                                                {l s='Text #'}{$textField@index+1}
                                            {/if}
                                            : {$textField.value}
                                        </li>
                                    {/foreach}
                                </ul>
                            {/if}
                        {/foreach}
                    </td>
                    <td class="cart_quantity" colspan="1">
                        {if isset($cannotModify) AND $cannotModify == 1}
                            <span>{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count}{else}{$product.cart_quantity-$quantityDisplayed}{/if}</span>
                        {else}
                            <input type="hidden" value="{$customization.quantity}" name="quantity_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}_{$product.id_address_delivery|intval}_hidden"/>
                            <input type="text" value="{$customization.quantity}" class="cart_quantity_input form-control grey" name="quantity_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}_{$product.id_address_delivery|intval}"/>
                            <div class="cart_quantity_button clearfix">
                                {if $product.minimal_quantity < ($customization.quantity -$quantityDisplayed) OR $product.minimal_quantity <= 1}
                                    <a
                                        id="cart_quantity_down_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}_{$product.id_address_delivery|intval}"
                                        class="cart_quantity_down btn btn-default button-minus"
                                        href="{$link->getPageLink('cart', true, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_address_delivery={$product.id_address_delivery}&amp;id_customization={$id_customization}&amp;op=down&amp;token={$token_cart}")|escape:'html':'UTF-8'}"
                                        rel="nofollow"
                                        title="{l s='Subtract'}">
                                        <span><i class="icon-minus"></i></span>
                                    </a>
                                {else}
                                    <a
                                        id="cart_quantity_down_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}"
                                        class="cart_quantity_down btn btn-default button-minus disabled"
                                        href="#"
                                        title="{l s='Subtract'}">
                                        <span><i class="icon-minus"></i></span>
                                    </a>
                                {/if}
                                <a
                                    id="cart_quantity_up_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}_{$product.id_address_delivery|intval}"
                                    class="cart_quantity_up btn btn-default button-plus"
                                    href="{$link->getPageLink('cart', true, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_address_delivery={$product.id_address_delivery}&amp;id_customization={$id_customization}&amp;token={$token_cart}")|escape:'html':'UTF-8'}"
                                    rel="nofollow"
                                    title="{l s='Add'}">
                                    <span><i class="icon-plus"></i></span>
                                </a>
                            </div>
                        {/if}
                    </td>
                    <td>
                    </td>
                    <td class="cart_delete text-center">
                        {if isset($cannotModify) AND $cannotModify == 1}
                        {else}
                            <a
                                id="{$product.id_product}_{$product.id_product_attribute}_{$id_customization}_{$product.id_address_delivery|intval}"
                                class="cart_quantity_delete"
                                href="{$link->getPageLink('cart', true, NULL, "delete=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;id_address_delivery={$product.id_address_delivery}&amp;token={$token_cart}")|escape:'html':'UTF-8'}"
                                rel="nofollow"
                                title="{l s='Delete'}">
                                <i class="icon-trash"></i>
                            </a>
                        {/if}
                    </td>
                </tr>
                {assign var='quantityDisplayed' value=$quantityDisplayed+$customization.quantity}
            {/foreach}

            {* If it exists also some uncustomized products *}
            {if $product.quantity-$quantityDisplayed > 0}{include file="$tpl_dir./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}{/if}
        {/if}
    {/foreach}
    {assign var='last_was_odd' value=$product@iteration%2}
    {foreach $gift_products as $product}
        {assign var='productId' value=$product.id_product}
        {assign var='productAttributeId' value=$product.id_product_attribute}
        {assign var='quantityDisplayed' value=0}
        {assign var='odd' value=($product@iteration+$last_was_odd)%2}
        {assign var='ignoreProductLast' value=isset($customizedDatas.$productId.$productAttributeId)}
        {assign var='cannotModify' value=1}
        {* Display the gift product line *}
        {include file="$tpl_dir./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}
    {/foreach}

    {if sizeof($discounts)}
        {*

        {foreach $discounts as $discount}
            <tr class="cart_discount {if $discount@last}last_item{elseif $discount@first}first_item{else}item{/if}" id="cart_discount_{$discount.id_discount}">
                <td class="cart_discount_name" colspan="4">{$discount.name}</td>
                <td class="cart_discount_price">
                    <span class="price-discount price">{if !$priceDisplay}{displayPrice price=$discount.value_real*-1}{else}{displayPrice price=$discount.value_tax_exc*-1}{/if}</span>
                </td>
                <td></td>
            </tr>
        {/foreach}
        *}
    {/if}
    <tr class="empty-line"></tr>

</tbody>
