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
<div id="shopping_cart_content">
    {capture name=path}{l s='Your shopping cart'}{/capture}

    <h3 id="cart" class="page-subheading">{l s='Shopping-cart summary'}</h3>

    {if isset($account_created)}
        <p class="alert alert-success">
            {l s='Your account has been created.'}
        </p>
    {/if}

    {assign var='current_step' value='summary'}
    {include file="$tpl_dir./order-steps.tpl"}
    {include file="$tpl_dir./errors.tpl"}

    {if isset($empty)}
        <p class="alert alert-warning">{l s='Your shopping cart is empty.'}</p>
    {elseif $PS_CATALOG_MODE}
        <p class="alert alert-warning">{l s='This store has not accepted your new order.'}</p>
    {else}
        <p style="display:none" id="emptyCartWarning"
           class="alert alert-warning">{l s='Your shopping cart is empty.'}</p>
        {*
        {if isset($lastProductAdded) AND $lastProductAdded}
            <div class="cart_last_product">
                <div class="cart_last_product_header">
                    <div class="left">{l s='Last product added'}</div>
                </div>
                <a class="cart_last_product_img" href="{$link->getProductLink($lastProductAdded.id_product, $lastProductAdded.link_rewrite, $lastProductAdded.category, null, null, $lastProductAdded.id_shop)|escape:'html':'UTF-8'}">
                    <img src="{$link->getImageLink($lastProductAdded.link_rewrite, $lastProductAdded.id_image, 'small_default')|escape:'html':'UTF-8'}" alt="{$lastProductAdded.name|escape:'html':'UTF-8'}"/>
                </a>
                <div class="cart_last_product_content">
                    <p class="product-name">
                        <a href="{$link->getProductLink($lastProductAdded.id_product, $lastProductAdded.link_rewrite, $lastProductAdded.category, null, null, null, $lastProductAdded.id_product_attribute)|escape:'html':'UTF-8'}">
                            {$lastProductAdded.name|escape:'html':'UTF-8'}
                        </a>
                    </p>
                    {if isset($lastProductAdded.attributes) && $lastProductAdded.attributes}
                        <small>
                            <a href="{$link->getProductLink($lastProductAdded.id_product, $lastProductAdded.link_rewrite, $lastProductAdded.category, null, null, null, $lastProductAdded.id_product_attribute)|escape:'html':'UTF-8'}">
                                {$lastProductAdded.attributes|escape:'html':'UTF-8'}
                            </a>
                        </small>
                    {/if}
                </div>
            </div>
        {/if}
        *}
        {assign var='total_discounts_num' value="{if $total_discounts != 0}1{else}0{/if}"}
        {assign var='use_show_taxes' value="{if $use_taxes && $show_taxes}2{else}0{/if}"}
        {assign var='total_wrapping_taxes_num' value="{if $total_wrapping != 0}1{else}0{/if}"}
        {* eu-legal *}
        {hook h="displayBeforeShoppingCartBlock"}
        <div id="order-detail-content" class="table_block table-responsive">
            <table id="cart_summary"
                   class="table {if $PS_STOCK_MANAGEMENT}stock-management-on{else}stock-management-off{/if}">
                <thead>
                <tr>
                    <th class="cart_product first_item">{l s='Product'}</th>
                    <!-- <th class="cart_description item">{l s='Description'}</th> -->
                    {if $PS_STOCK_MANAGEMENT}
                        {assign var='col_span_subtotal' value='3'}
                        <th class="cart_avail item text-center">{l s='Availability'}</th>
                    {else}
                        {assign var='col_span_subtotal' value='2'}
                    {/if}
                    <th class="cart_unit item">{l s='Unit price'}</th>
                    <th class="cart_quantity item text-center">{l s='Quantity'}</th>
                    <th class="cart_total item">{l s='Total sum'}</th>
                    <th class="cart_delete last_item">&nbsp;</th>
                </tr>
                </thead>
                <tfoot>
                {assign var='rowspan_total' value=2+$total_discounts_num+$total_wrapping_taxes_num}

                {if $use_taxes && $show_taxes && $total_tax != 0}
                    {assign var='rowspan_total' value=$rowspan_total+1}
                {/if}

                {if $priceDisplay != 0}
                    {assign var='rowspan_total' value=$rowspan_total+1}
                {/if}

                {if $total_shipping_tax_exc <= 0 && !isset($virtualCart)}
                    {assign var='rowspan_total' value=$rowspan_total+1}
                {else}
                    {if $use_taxes && $total_shipping_tax_exc != $total_shipping}
                        {if $priceDisplay && $total_shipping_tax_exc > 0}
                            {assign var='rowspan_total' value=$rowspan_total+1}
                        {elseif $total_shipping > 0}
                            {assign var='rowspan_total' value=$rowspan_total+1}
                        {/if}
                    {elseif $total_shipping_tax_exc > 0}
                        {assign var='rowspan_total' value=$rowspan_total+1}
                    {/if}
                {/if}

                <tr class="cart_total_price">
                    <td class="empty-row" colspan="2"></td>
                    <td colspan="2" class="row-title row-bordered">{l s='Total products'}</td>
                    <td colspan="1" class="price row-bordered"
                        id="total_product">{displayPrice price=$total_products}</td>
                </tr>

                <tr class="cart_order_bonus"
                    {if $total_discounts == 0 || sizeof($discounts) <= 1}style="display:none"{/if}>
                    <td class="empty-row" colspan="2"></td>

                    <td colspan="3" class="row-bordered order_bonus">
                        <p class="bonus_title">{l s='Choose your bonus'}</p>
                        <span class="bonuses_list">
                            {if sizeof($discounts)}
                                {foreach $discounts as $d}
                                    <p class="bonus_name" id="bonus_name_{$d.id_cart_rule}"
                                       data-rule-id="{$d.id_cart_rule}"
                                       data-type="{if $d.free_shipping}free_shipping{else}discount{/if}">
                                        {$d.name}
                                        <i>{if $d.value_real > 0}{displayPrice price=$d.value_real*-1}{/if}</i>
                                    </p>

                                                                            {if !$d@last}
                                    <p>{l s='or'}</p>
                                {/if}
                                {/foreach}
                            {/if}
                        </span>
                    </td>
                </tr>

                <tr class="cart_total_voucher"
                    {if sizeof($discounts) > 1 || !sizeof($discounts)}style="display:none"{/if}>
                    <td class="empty-row" colspan="2"></td>

                    {foreach $discounts as $d}
                        {if $d.value_real > 0}
                            <td colspan="2" class="row-title row-bordered">
                                <i class="discount_title">
                                    {$d.name}
                                </i>
                            </td>
                            <td colspan="1" class="price-discount price row-bordered" id="total_discount">
                                {displayPrice price=$d.value_real*-1}
                            </td>
                            {break}
                        {/if}
                        {foreachelse}
                        <td colspan="2" class="row-title row-bordered">
                            <i class="discount_title">
                            </i>
                        </td>
                        <td colspan="1" class="price-discount price row-bordered" id="total_discount">
                        </td>
                    {/foreach}

                </tr>


                <tr class="cart_total_shipping" {if $total_shipping_tax_exc == 0}style="display: none;"{/if}>
                    <td class="empty-row" colspan="2"></td>
                    <td colspan="2" class="row-title row-bordered">
                        <span>Стоимость доставки:</span>
                    </td>
                    <td colspan="1" class="price row-bordered">
                        <span id="total_shipping_cost">{displayPrice price=$total_shipping_tax_exc}</span>
                    </td>
                </tr>


                <tr class="cart_total_price">
                    <td class="empty-row" colspan="2"></td>
                    <td colspan="2" class="total_price_container row-title row-bordered">
                        <span>{l s='Total'}</span>
                    </td>
                    <td colspan="1" class="price row-bordered" id="total_price_container">
                        <span id="total_price">{displayPrice price=$total_price_without_tax}</span>
                    </td>
                </tr>

                <tr class="empty-line"></tr>
                <tr class="order_submit">
                    <td class="empty-row" colspan="2"></td>
                    <td colspan="3" class="order_submit_td">
                        <button type="submit" id="placeOrder" class="btn btn-default button button-medium">
                            <span>{l s='Order'}</span></button>
                    </td>
                </tr>
                </tfoot>

                {include file="$tpl_dir./shopping-cart-tbody.tpl"}

            </table>
        </div>
        <!-- end order-detail-content -->

        {if $show_option_allow_separate_package}
            <p>
                <input type="checkbox" name="allow_seperated_package" id="allow_seperated_package"
                       {if $cart->allow_seperated_package}checked="checked"{/if} autocomplete="off"/>
                <label for="allow_seperated_package">{l s='Send available products first'}</label>
            </p>
        {/if}

        {* Define the style if it doesn't exist in the PrestaShop version*}
        {* Will be deleted for 1.5 version and more *}
        {if !isset($addresses_style)}
            {$addresses_style.company = 'address_company'}
            {$addresses_style.vat_number = 'address_company'}
            {$addresses_style.firstname = 'address_name'}
            {$addresses_style.lastname = 'address_name'}
            {$addresses_style.address1 = 'address_address1'}
            {$addresses_style.address2 = 'address_address2'}
            {$addresses_style.city = 'address_city'}
            {$addresses_style.country = 'address_country'}
            {$addresses_style.phone = 'address_phone'}
            {$addresses_style.phone_mobile = 'address_phone_mobile'}
            {$addresses_style.alias = 'address_title'}
        {/if}

        {if ((!empty($delivery_option) AND !isset($virtualCart)) OR $delivery->id OR $invoice->id) AND !$opc}
            <div class="order_delivery clearfix row">
                {if !isset($formattedAddresses) || (count($formattedAddresses.invoice) == 0 && count($formattedAddresses.delivery) == 0) || (count($formattedAddresses.invoice.formated) == 0 && count($formattedAddresses.delivery.formated) == 0)}
                    {if $delivery->id}
                        <div class="col-xs-12 col-sm-6"{if !$have_non_virtual_products} style="display: none;"{/if}>
                            <ul id="delivery_address" class="address item box">
                                <li><h3 class="page-subheading">{l s='Delivery address'}&nbsp;<span
                                                class="address_alias">({$delivery->alias})</span></h3></li>
                                {if $delivery->company}
                                    <li class="address_company">{$delivery->company|escape:'html':'UTF-8'}</li>{/if}
                                <li class="address_name">{$delivery->firstname|escape:'html':'UTF-8'} {$delivery->lastname|escape:'html':'UTF-8'}</li>
                                <li class="address_address1">{$delivery->address1|escape:'html':'UTF-8'}</li>
                                {if $delivery->address2}
                                    <li class="address_address2">{$delivery->address2|escape:'html':'UTF-8'}</li>{/if}
                                <li class="address_city">{$delivery->postcode|escape:'html':'UTF-8'} {$delivery->city|escape:'html':'UTF-8'}</li>
                                <li class="address_country">{$delivery->country|escape:'html':'UTF-8'} {if $delivery_state}({$delivery_state|escape:'html':'UTF-8'}){/if}</li>
                            </ul>
                        </div>
                    {/if}
                    {if $invoice->id}
                        <div class="col-xs-12 col-sm-6">
                            <ul id="invoice_address" class="address alternate_item box">
                                <li><h3 class="page-subheading">{l s='Invoice address'}&nbsp;<span
                                                class="address_alias">({$invoice->alias})</span></h3></li>
                                {if $invoice->company}
                                    <li class="address_company">{$invoice->company|escape:'html':'UTF-8'}</li>{/if}
                                <li class="address_name">{$invoice->firstname|escape:'html':'UTF-8'} {$invoice->lastname|escape:'html':'UTF-8'}</li>
                                <li class="address_address1">{$invoice->address1|escape:'html':'UTF-8'}</li>
                                {if $invoice->address2}
                                    <li class="address_address2">{$invoice->address2|escape:'html':'UTF-8'}</li>{/if}
                                <li class="address_city">{$invoice->postcode|escape:'html':'UTF-8'} {$invoice->city|escape:'html':'UTF-8'}</li>
                                <li class="address_country">{$invoice->country|escape:'html':'UTF-8'} {if $invoice_state}({$invoice_state|escape:'html':'UTF-8'}){/if}</li>
                            </ul>
                        </div>
                    {/if}
                {else}
                    {foreach from=$formattedAddresses key=k item=address}
                        <div class="col-xs-12 col-sm-6"{if $k == 'delivery' && !$have_non_virtual_products} style="display: none;"{/if}>
                            <ul class="address {if $address@last}last_item{elseif $address@first}first_item{/if} {if $address@index % 2}alternate_item{else}item{/if} box">
                                <li>
                                    <h3 class="page-subheading">
                                        {if $k eq 'invoice'}
                                            {l s='Invoice address'}
                                        {elseif $k eq 'delivery' && $delivery->id}
                                            {l s='Delivery address'}
                                        {/if}
                                        {if isset($address.object.alias)}
                                            <span class="address_alias">({$address.object.alias})</span>
                                        {/if}
                                    </h3>
                                </li>
                                {foreach $address.ordered as $pattern}
                                    {assign var=addressKey value=" "|explode:$pattern}
                                    {assign var=addedli value=false}
                                    {foreach from=$addressKey item=key name=foo}
                                        {$key_str = $key|regex_replace:AddressFormat::_CLEANING_REGEX_:""}
                                        {if isset($address.formated[$key_str]) && !empty($address.formated[$key_str])}
                                            {if (!$addedli)}
                                                {$addedli = true}
                                                <li><span class="{if isset($addresses_style[$key_str])}{$addresses_style[$key_str]}{/if}">
                                            {/if}
                                            {$address.formated[$key_str]|escape:'html':'UTF-8'}
                                        {/if}
                                        {if ($smarty.foreach.foo.last && $addedli)}
                                            </span></li>
                                        {/if}
                                    {/foreach}
                                {/foreach}
                            </ul>
                        </div>
                    {/foreach}
                {/if}
            </div>
        {/if}
        <div id="HOOK_SHOPPING_CART">{$HOOK_SHOPPING_CART}</div>
        {if !empty($HOOK_SHOPPING_CART_EXTRA)}
            <div class="clear"></div>
            <div class="cart_navigation_extra">
                <div id="HOOK_SHOPPING_CART_EXTRA">{$HOOK_SHOPPING_CART_EXTRA}</div>
            </div>
        {/if}
        {strip}
            {addJsDef currencySign=$currencySign|html_entity_decode:2:"UTF-8"}
            {addJsDef currencyRate=$currencyRate|floatval}
            {addJsDef currencyFormat=$currencyFormat|intval}
            {addJsDef currencyBlank=$currencyBlank|intval}
            {addJsDef total_price=$total_price}
            {addJsDef total_products=$total_products}
            {addJsDef discounts=$discounts}
            {addJsDef deliveryAddress=$cart->id_address_delivery|intval}
            {addJsDefL name=txtProduct}{l s='product' js=1}{/addJsDefL}
            {addJsDefL name=txtProducts}{l s='products' js=1}{/addJsDefL}
        {/strip}
    {/if}
</div>