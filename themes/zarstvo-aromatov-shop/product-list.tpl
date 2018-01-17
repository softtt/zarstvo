{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if isset($products) && $products}
    {*define number of products per line in other page for desktop*}
    {if $page_name == 'index' || $page_name == 'product'}
        {assign var='nbItemsPerLine' value=5}
        {assign var='nbItemsPerLineTablet' value=3}
        {assign var='nbItemsPerLineMobile' value=2}
    {elseif $page_name == 'cart' || $page_name == 'order-opc'}
        {assign var='nbItemsPerLine' value=4}
        {assign var='nbItemsPerLineTablet' value=2}
        {assign var='nbItemsPerLineMobile' value=2}
    {else}
        {assign var='nbItemsPerLine' value=4}
        {assign var='nbItemsPerLineTablet' value=2}
        {assign var='nbItemsPerLineMobile' value=2}
    {/if}
    {*define numbers of product per line in other page for tablet*}
    {assign var='nbLi' value=$products|@count}
    {math equation="nbLi/nbItemsPerLine" nbLi=$nbLi nbItemsPerLine=$nbItemsPerLine assign=nbLines}
    {math equation="nbLi/nbItemsPerLineTablet" nbLi=$nbLi nbItemsPerLineTablet=$nbItemsPerLineTablet assign=nbLinesTablet}
    <!-- Products list -->
    {if $slider}
        <div class="products_slider {if isset($id) && $id}{$id}{/if}">
            <span class="slider-prev"></span>
            <span class="slider-next"></span>
    {/if}
    <ul{if isset($id) && $id} id="{$id}"{/if} class="product_list grid row{if isset($class) && $class} {$class}{/if} {if $slider}slider_list{/if}">
    {foreach from=$products item=product name=products}
        {math equation="(total%perLine)" total=$smarty.foreach.products.total perLine=$nbItemsPerLine assign=totModulo}
        {math equation="(total%perLineT)" total=$smarty.foreach.products.total perLineT=$nbItemsPerLineTablet assign=totModuloTablet}
        {math equation="(total%perLineT)" total=$smarty.foreach.products.total perLineT=$nbItemsPerLineMobile assign=totModuloMobile}
        {if $totModulo == 0}{assign var='totModulo' value=$nbItemsPerLine}{/if}
        {if $totModuloTablet == 0}{assign var='totModuloTablet' value=$nbItemsPerLineTablet}{/if}
        {if $totModuloMobile == 0}{assign var='totModuloMobile' value=$nbItemsPerLineMobile}{/if}
        <li class="ajax_block_product{if $page_name == 'index' || $page_name == 'product'} col-xs-12 col-sm-4 col-md-5ths{else} col-xs-12 col-sm-6 col-md-3{/if}{if $smarty.foreach.products.iteration%$nbItemsPerLine == 0} last-in-line{elseif $smarty.foreach.products.iteration%$nbItemsPerLine == 1} first-in-line{/if}{if $smarty.foreach.products.iteration > ($smarty.foreach.products.total - $totModulo)} last-line{/if}{if $smarty.foreach.products.iteration%$nbItemsPerLineTablet == 0} last-item-of-tablet-line{elseif $smarty.foreach.products.iteration%$nbItemsPerLineTablet == 1} first-item-of-tablet-line{/if}{if $smarty.foreach.products.iteration%$nbItemsPerLineMobile == 0} last-item-of-mobile-line{elseif $smarty.foreach.products.iteration%$nbItemsPerLineMobile == 1} first-item-of-mobile-line{/if}{if $smarty.foreach.products.iteration > ($smarty.foreach.products.total - $totModuloMobile)} last-mobile-line{/if} {if $slider}product-slide{/if}">
            <div class="product-container" itemscope itemtype="http://schema.org/Product" data-product-id="{{$product.id_product}}">
                <div class="left-block">
                    <div class="product-image-container">
                        <a class="product_img_link" href="{$product.link|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}" itemprop="url">
                            <img class="replace-2x img-responsive" src="{$link->getImageLink($product.link_rewrite, $product.id_image)|escape:'html':'UTF-8'}" alt="{if !empty($product.legend)}{$product.legend|escape:'html':'UTF-8'}{else}{$product.name|escape:'html':'UTF-8'}{/if}" title="{if !empty($product.legend)}{$product.legend|escape:'html':'UTF-8'}{else}{$product.name|escape:'html':'UTF-8'}{/if}" {if isset($homeSize)} width="{$homeSize.width}" height="{$homeSize.height}"{/if} itemprop="image" />
                        </a>
                        {if (isset($product.new) && $product.new == 1) || (isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE) || (isset($product.show_new) && $product.show_new == 1)}
                            <a class="new-box" href="{$product.link|escape:'html':'UTF-8'}">
                                {if (isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE)}
                                    <span class="new-label sale">{l s='Sale!'}</span>
                                {elseif (isset($product.new) && $product.new == 1) || (isset($product.show_new) && $product.show_new == 1)}
                                    <span class="new-label">{l s='New'}</span>
                                {/if}
                            </a>
                        {/if}
                    </div>
                    {hook h="displayProductDeliveryTime" product=$product}
                    {hook h="displayProductPriceBlock" product=$product type="weight"}
                </div>
                <div class="right-block">
                    <h5 itemprop="name">
                        {if isset($product.pack_quantity) && $product.pack_quantity}{$product.pack_quantity|intval|cat:' x '}{/if}
                        <a class="product-name" href="{$product.link|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}" itemprop="url" >
                            {$product.name|escape:'html':'UTF-8'}
                        </a>
                    </h5>

                    {hook h='displayProductListReviews' product=$product}

                    {hook h='displayProductModifications' product=$product}
                    <div class="wishlist">
                        <a href="#" id="wishlist_button" onclick="WishlistCart('wishlist_block_list', 'add', '{$product.id_product|intval}', false, 1); return false;" class="addToWishlist "><i class="icon-heart-empty"></i> В избранное</a>
                    </div>
{*
                    {if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
                    <div itemprop="offers" itemscope itemtype="http://schema.org/Offer" class="content_price custom_prices_block">
                        {if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}
                            <meta itemprop="priceCurrency" content="{$currency->iso_code}" />
                            {if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction) && $product.specific_prices.reduction > 0}
                                {hook h="displayProductPriceBlock" product=$product type="old_price"}
                                <span class="old-price product-price">
                                    <span class="text">{l s='Old price'}</span>
                                    <span>{displayWtPrice p=$product.price_without_reduction}</span>
                                </span>
                                {hook h="displayProductPriceBlock" id_product=$product.id_product type="old_price"}
                                <span itemprop="price" class="price product-price">
                                    <span class="price-tooltip">
                                        <span class="text">{l s='New price'}</span>
                                        <span>{if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}</span>
                                    </span>
                                </span>
                            {else}
                                <span itemprop="price" class="price product-price regular-price">
                                    {if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}
                                </span>
                            {/if}
                            {hook h="displayProductPriceBlock" product=$product type="price"}
                            {hook h="displayProductPriceBlock" product=$product type="unit_price"}
                        {/if}
                    </div>
                    {/if}
*}

{*
                    <div class="button-container">
                        {if ($product.id_product_attribute == 0 || (isset($add_prod_display) && ($add_prod_display == 1))) && $product.available_for_order && !isset($restricted_country_mode) && $product.customizable != 2 && !$PS_CATALOG_MODE}
                            {if (!isset($product.customization_required) || !$product.customization_required) && ($product.allow_oosp || $product.quantity > 0)}
                                {capture}add=1&amp;id_product={$product.id_product|intval}{if isset($static_token)}&amp;token={$static_token}{/if}{/capture}
                                {if ($cart->containsProduct($product.id_product))}
                                    <a class="button ajax_add_to_cart_button in_cart btn btn-default" rel="nofollow" title="{l s='Already in cart'}" data-id-product="{$product.id_product|intval}" data-minimal_quantity="{if isset($product.product_attribute_minimal_quantity) && $product.product_attribute_minimal_quantity > 1}{$product.product_attribute_minimal_quantity|intval}{else}{$product.minimal_quantity|intval}{/if}">
                                        <span>
                                            {l s='Already in cart'}
                                        </span>
                                    </a>
                                {else}
                                    <a class="button ajax_add_to_cart_button btn btn-default" rel="nofollow" title="{l s='Add to cart'}" data-id-product="{$product.id_product|intval}" data-minimal_quantity="{if isset($product.product_attribute_minimal_quantity) && $product.product_attribute_minimal_quantity > 1}{$product.product_attribute_minimal_quantity|intval}{else}{$product.minimal_quantity|intval}{/if}">
                                        <span>
                                            {l s='Add to cart'}
                                        </span>
                                    </a>
                                {/if}
                            {else}
                                <span class="button ajax_add_to_cart_button btn btn-default disabled">
                                    <span>{l s='Out of stock'}</span>
                                </span>
                            {/if}

                        {/if}
                    </div>
*}

                </div>
            </div><!-- .product-container> -->
        </li>
    {/foreach}
    </ul>
    {if $slider}
        </div>
    {/if}
{addJsDefL name=min_item}{l s='Please select at least one product' js=1}{/addJsDefL}
{addJsDefL name=max_item}{l s='You cannot add more than %d product(s) to the product comparison' sprintf=$comparator_max_item js=1}{/addJsDefL}
{addJsDefL name=out_of_stock}{l s='Out of stock' js=1}{/addJsDefL}
{addJsDefL name=add_to_cart}{l s='Add to cart' js=1}{/addJsDefL}
{addJsDefL name=already_to_cart}{l s='Already in cart' js=1}{/addJsDefL}
{addJsDef comparator_max_item=$comparator_max_item}
{addJsDef orderProcess='product-list'}
{addJsDef comparedProductsIds=$compared_products}

{else}
    <ul{if isset($id) && $id} id="{$id}"{/if} class="product_list grid row{if isset($class) && $class} {$class}{/if}">
    </ul>
{/if}
