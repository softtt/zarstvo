{if !isset($priceDisplayPrecision)}
    {assign var='priceDisplayPrecision' value=2}
{/if}
{if !$priceDisplay || $priceDisplay == 2}
    {assign var='productPrice' value=$mod_product->getPrice(true, $smarty.const.NULL, $priceDisplayPrecision)}
    {assign var='productPriceWithoutReduction' value=$mod_product->getPriceWithoutReduct(false, $smarty.const.NULL)}
{elseif $priceDisplay == 1}
    {assign var='productPrice' value=$mod_product->getPrice(false, $smarty.const.NULL, $priceDisplayPrecision)}
    {assign var='productPriceWithoutReduction' value=$mod_product->getPriceWithoutReduct(true, $smarty.const.NULL)}
{/if}

{if isset($mod_groups) && count($mod_groups)}
    <div class="product_attributes clearfix" data-product-id="{{$mod_product->id}}">
        <!-- attributes -->
        <div class="attributes">
            <div class="clearfix"></div>
            {foreach from=$mod_groups key=id_attribute_group item=group}
                {if $group.attributes|@count}
                    <fieldset class="attribute_fieldset">
                        <label class="attribute_label">{$group.name|escape:'html':'UTF-8'}&nbsp;</label>
                        {assign var="groupName" value="group_$id_attribute_group"}
                        <div class="attribute_list">
                            {if ($group.group_type == 'select')}
                                <select class="form-control product_list_attribute_select no-print" data-product-id="{{$mod_product->id}}" value="{{$group.default}}">
                                    {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                        <option value="{$id_attribute|intval}"{if (isset($smarty.get.$groupName) && $smarty.get.$groupName|intval == $id_attribute) || $group.default == $id_attribute} selected{/if} title="{$group_attribute|escape:'html':'UTF-8'}">{$group_attribute|escape:'html':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            {elseif ($group.group_type == 'color')}
                                <ul class="color_to_pick_list clearfix">
                                    {assign var="default_colorpicker" value=""}
                                    {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                        {assign var='img_color_exists' value=file_exists($col_img_dir|cat:$id_attribute|cat:'.jpg')}
                                        <li{if $group.default == $id_attribute} class="selected"{/if}>
                                            <a href="{$link->getProductLink($mod_product)|escape:'html':'UTF-8'}" name="{$mod_colors.$id_attribute.name|escape:'html':'UTF-8'}" class="color_{$id_attribute|intval} product_list_color_pick{if ($group.default == $id_attribute)} selected{/if}"{if !$img_color_exists && isset($mod_colors.$id_attribute.value) && $mod_colors.$id_attribute.value} style="background:{$mod_colors.$id_attribute.value|escape:'html':'UTF-8'};"{/if} title="{$mod_colors.$id_attribute.name|escape:'html':'UTF-8'}" data-product-id="{{$mod_product->id}}">
                                                {if $img_color_exists}
                                                    <img src="{$img_col_dir}{$id_attribute|intval}.jpg" alt="{$mod_colors.$id_attribute.name|escape:'html':'UTF-8'}" title="{$mod_colors.$id_attribute.name|escape:'html':'UTF-8'}" width="20" height="20" />
                                                {/if}
                                            </a>
                                        </li>
                                        {if ($group.default == $id_attribute)}
                                            {$default_colorpicker = $id_attribute}
                                        {/if}
                                    {/foreach}
                                </ul>
                                <input type="hidden" class="color_pick_hidden" name="{$groupName|escape:'html':'UTF-8'}" value="{$default_colorpicker|intval}" />
                            {elseif ($group.group_type == 'radio')}
                                <ul>
                                    {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                        <li>
                                            <input type="radio" class="product_list_attribute_radio" name="{$groupName|escape:'html':'UTF-8'}" value="{$id_attribute}" {if ($group.default == $id_attribute)} checked="checked"{/if} data-product-id="{{$mod_product->id}}"/>
                                            <span>{$group_attribute|escape:'html':'UTF-8'}</span>
                                        </li>
                                    {/foreach}
                                </ul>
                            {/if}
                        </div> <!-- end attribute_list -->
                    </fieldset>
                {/if}
            {/foreach}
        </div> <!-- end attributes -->
    </div> <!-- end product_attributes -->
{/if}


<div class="content_price clearfix custom_prices_block" data-product-id="{{$mod_product->id}}">
    <!-- prices -->
    <div class="offers" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
        {if $mod_product->quantity > 0}<link itemprop="availability" href="http://schema.org/InStock"/>{/if}

        {if isset($mod_combinations) && $mod_combinations}
            {foreach from=$mod_combinations key=combination_id item=combination}
                <div class="combination-price-block combination_attribute_{$combination['attributes'][0]}"
                    {if $combination@iteration > 1}style="display: none"{/if}
                    data-combination-id="{{$combination_id}}"
                    data-quantity="{$combination['quantity']}"
                >
                {if isset($combination['specific_price']) && $combination['specific_price']}
                    <span class="old-price product-price">
                        <span class="text">{l s='Old price' mod='productcombinationsinproductlist'}</span>
                        <span>{convertPrice price=$combination['price']}</span>
                    </span>
                    <span class="price product-price">
                        <span class="price-tooltip">
                            <span class="text">{l s='New price' mod='productcombinationsinproductlist'}</span>
                            <meta itemprop="priceCurrency" content="{$currency->iso_code}" />
                            <span itemprop="price">
                                {if $combination['specific_price']['reduction_type'] == 'percentage'}
                                    {convertPrice price=($combination['price']*(1 - $combination['specific_price']['reduction']))}
                                {else}
                                    {convertPrice price=($combination['price']-$combination['specific_price']['reduction'])}
                                {/if}
                            </span>
                        </span>
                    </span>
                {else}
                    <span itemprop="price" class="price product-price regular-price">
                        {convertPrice price=$combination['price']}
                    </span>
                    <meta itemprop="priceCurrency" content="{$currency->iso_code}" />
                {/if}
                </div>
            {/foreach}
        {else}
            {if isset($mod_product->specificPrice) && $mod_product->specificPrice && $mod_group_reduction == 0}
                {hook h="displayProductPriceBlock" product=$mod_product type="old_price"}
                <span class="old-price product-price">
                    <span class="text">{l s='Old price' mod='productcombinationsinproductlist'}</span>
                    <span>{if $productPriceWithoutReduction > $productPrice}{convertPrice price=$productPriceWithoutReduction}{/if}</span>
                </span>
                <span class="price product-price">
                    <span class="price-tooltip">
                        <span class="text">{l s='New price' mod='productcombinationsinproductlist'}</span>
                        <meta itemprop="priceCurrency" content="{$currency->iso_code}" />
                        <span class="" itemprop="price">{convertPrice price=$productPrice}</span>
                    </span>
                </span>
            {else}
                <span itemprop="price" class="price product-price regular-price">
                    {convertPrice price=$productPrice}
                </span>
                <meta itemprop="priceCurrency" content="{$currency->iso_code}" />
            {/if}
        {/if}

    </div> <!-- end prices -->

    <div class="clear"></div>
</div> <!-- end content_prices -->


<div class="button-container" data-product-id="{{$mod_product->id}}">
    {if $mod_product->available_for_order && $mod_product->customizable != 2 && !$PS_CATALOG_MODE}

        {if isset($mod_combinations) && $mod_combinations}
            {foreach from=$mod_combinations key=combination_id item=combination}
                {if ($mod_allow_oosp || $combination['quantity'] > 0)}
                    {capture}add=1&amp;id_product={$mod_product->id|intval}{if isset($static_token)}&amp;token={$static_token}{/if}{/capture}
                    {if ($cart->containsProduct($mod_product->id, $combination_id))}
                        <a class="button ajax_add_to_cart_button in_cart btn btn-default" rel="nofollow" title="{l s='Already in cart' mod='productcombinationsinproductlist'}"
                            data-id-product="{$mod_product->id|intval}"
                            data-minimal_quantity="{$combination['minimal_quantity']|intval}"
                            data-combination-id="{{$combination_id}}">
                            <span>
                                {l s='Already in cart' mod='productcombinationsinproductlist'}
                            </span>
                        </a>
                    {else}
                        <a class="button ajax_add_to_cart_button btn btn-default" rel="nofollow" title="{l s='Add to cart' mod='productcombinationsinproductlist'}" data-id-product="{$mod_product->id|intval}" data-minimal_quantity="{$combination['minimal_quantity']|intval}" data-combination-id="{{$combination_id}}">
                            <span>
                                {l s='Add to cart' mod='productcombinationsinproductlist'}
                            </span>
                        </a>
                    {/if}
                {else}
                    <span class="button ajax_add_to_cart_button btn btn-default disabled" data-combination-id="{{$combination_id}}">
                        <span>{l s='Out of stock' mod='productcombinationsinproductlist'}</span>
                    </span>
                {/if}
            {/foreach}
        {else}
            {if ($mod_allow_oosp || $mod_product->quantity > 0)}
                {capture}add=1&amp;id_product={$mod_product->id|intval}{if isset($static_token)}&amp;token={$static_token}{/if}{/capture}
                {if ($cart->containsProduct($mod_product->id))}
                    <a class="button ajax_add_to_cart_button in_cart btn btn-default" rel="nofollow" title="{l s='Already in cart' mod='productcombinationsinproductlist'}" data-id-product="{$mod_product->id|intval}" data-minimal_quantity="{$mod_product->minimal_quantity|intval}">
                        <span>
                            {l s='Already in cart' mod='productcombinationsinproductlist'}
                        </span>
                    </a>
                {else}
                    <a class="button ajax_add_to_cart_button btn btn-default" rel="nofollow" title="{l s='Add to cart' mod='productcombinationsinproductlist'}" data-id-product="{$mod_product->id|intval}" data-minimal_quantity="{$mod_product->minimal_quantity|intval}">
                        <span>
                            {l s='Add to cart' mod='productcombinationsinproductlist'}
                        </span>
                    </a>
                {/if}
            {else}
                <span class="button ajax_add_to_cart_button btn btn-default disabled">
                    <span>{l s='Out of stock' mod='productcombinationsinproductlist'}</span>
                </span>
            {/if}
        {/if}

    {/if}

</div>
