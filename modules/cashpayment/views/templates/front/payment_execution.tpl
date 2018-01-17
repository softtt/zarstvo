{*
* 2007-2014 PrestaShop
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
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='cashpayment'}">{l s='Checkout' mod='cashpayment'}</a>
{/capture}

<h2>{l s='Order summary' mod='cashpayment'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='cashpayment'}</p>
{else}

<form action="{$link->getModuleLink('cashpayment', 'validation', [], true)|escape:'html'}" method="post">
<p>
	{l s='Here is a short summary of your order:' mod='cashpayment'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='cashpayment'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='cashpayment'}
    {/if}
</p>
<p>
	{l s='Bank wire account information will be displayed on the next page.' mod='cashpayment'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order".' mod='cashpayment'}</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='cashpayment'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='cashpayment'}</a>
</p>
</form>
{/if}
