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

{capture name=path}{l s='Order confirmation'}{/capture}

<h1 class="page-subheading">{l s='Order successfully placed'}</h1>

<h2 class="confirmation-subheading">{l s='Your order is complete successfully'}</h2>

<h2 class="confirmation-subheading">{l s='Order details'}</h2>

<p><strong>Код заказа: {$id_order}</strong></p>

<p><strong>Способ доставки: {if $carrier->name == "0"}{$shop_name|escape:'html':'UTF-8'}{else}{$carrier->name|escape:'html':'UTF-8'}{/if}</strong></p>

{if $carrier->is_pickup}
    <p>{l s='Pickup address:'} {$point_of_delivery['city']}, {$point_of_delivery['address1']}</p>
{else}
    <p>{l s='Delivery address:'}</p>
    <ul class="address-list">
        {foreach from=$dlv_adr_fields name=dlv_loop item=field_item}
            {if $field_item eq "company" && isset($address_delivery->company)}<li class="address_company">{$address_delivery->company|escape:'html':'UTF-8'}</li>
            {elseif $field_item eq "address2" && $address_delivery->address2}<li class="address_address2">{$address_delivery->address2|escape:'html':'UTF-8'}</li>
            {elseif $field_item eq "phone_mobile" && $address_delivery->phone_mobile}<li class="address_phone_mobile">{$address_delivery->phone_mobile|escape:'html':'UTF-8'}</li>
            {else}
                    {assign var=address_words value=" "|explode:$field_item}
                    <li>{foreach from=$address_words item=word_item name="word_loop"}{if !$smarty.foreach.word_loop.first} {/if}<span class="address_{$word_item|replace:',':''}">{$deliveryAddressFormatedValues[$word_item|replace:',':'']|escape:'html':'UTF-8'}</span>{/foreach}</li>
            {/if}
        {/foreach}
    </ul>
{/if}

<p><strong>Способ оплаты: {$order->payment}</strong></p>
<p>{if isset($HOOK_ORDER_CONFIRMATION)}
        {$HOOK_ORDER_CONFIRMATION}
    {/if}
</p>
<p><strong>Итого к оплате: <span class="price">{displayPrice price=$order->total_paid}</span></strong></p>

<table class="order-confirmation-cart-summary">
    <thead>
        <th>{l s='Product'}</th>
        <th>{l s='Unit price'}</th>
        <th>{l s='Quantity'}</th>
        <th>{l s='Total sum'}</th>
    </thead>
    <tbody>
    {foreach $order_details as $product}
        <tr>
            <td>
                {$product['product_name']}
            </td>
            <td>
                {if $product['unit_price_tax_excl'] > 0}
                    {displayPrice price=$product['unit_price_tax_excl']}
                {else}
                    {displayPrice price=$product['product_price']}
                {/if}
            </td>
            <td>
                {$product['product_quantity']}
            </td>
            <td class="unit-total">
                {displayPrice price=$product['total_price_tax_excl']}
            </td>
        </tr>
    {/foreach}
    <tr>
        <td colspan="3" class="total-label">{l s='Total:'}</td>
        <td class="unit-total">{displayPrice price=$order->total_products}</td>
    </tr>
    {if $order->total_discounts > 0}
        <tr class="discount">
            <td colspan="3" class="total-label">{l s='Total discounts:'}</td>
            <td class="unit-total">{displayPrice price=$order->total_discounts}</td>
        </tr>
    {/if}

    {foreach $cart_rules as $rule}
        {if $rule.free_shipping && $order->total_discounts == 0}
            <tr class="discount">
                <td colspan="4" class="total-label">{$rule.name}</td>
            </tr>
        {/if}
    {/foreach}

    {if isset($order->total_shipping_tax_excl) && $order->total_shipping_tax_excl > 0}
        <tr class="">
            <td colspan="3" class="total-label">Стоимость доставки:</td>
            <td class="unit-total">{displayPrice price=$order->total_shipping_tax_excl}</td>
        </tr>
    {/if}

    <tr class="total-sum">
        <td colspan="3" class="total-label">{l s='Total sum to pay:'}</td>
        <td class="unit-total">{displayPrice price=$order->total_paid}</td>
    </tr>
    </tbody>
</table>

<p>{l s='Your order details have been sent via email.'}</p>

<p>{l s='If you have questions, comments or concerns, please contact our'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team'}.</a></p>

{if $is_guest}
    <p class="cart_navigation exclusive">
    	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('guest-tracking', true, NULL, "id_order={$reference_order|urlencode}&email={$email|urlencode}")|escape:'html':'UTF-8'}" title="{l s='Follow my order'}">{l s='Follow my order'}</a>
    </p>
{else}
    <p class="cart_navigation exclusive">
    	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}" title="{l s='Go to your order history page'}">{l s='View your order history'}</a>
    </p>
{/if}
