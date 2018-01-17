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
<div style="font-size: 9pt; color: #444">

<table style="width: 100%">
	<tr>
		<td style="width: 50%">
			<table style="width: 100%">
				<tr>
					<td style="width: 100%; padding-right: 7px; text-align: left; vertical-align: top">
						<!-- CUSTOMER INFORMATIONS -->
						<b>{l s='Order Number:' pdf='true'}</b><br />
						{$order->getUniqReference()}<br />
						<br />
						<b>{l s='Order Date:' pdf='true'}</b><br />
						{dateFormat date=$order->date_add full=0}<br />
						<br />
						{foreach from=$order_invoice->getOrderPaymentCollection() item=payment}
							<b>{l s='Payment Method:' pdf='true'}</b><br />
							<table style="width: 100%;">
								<tr>
									<td style="width: 50%">{$payment->payment_method}</td>
									<td style="width: 50%">{displayPrice price=$payment->amount currency=$order->id_currency}</td>
								</tr>
							</table>
							<br />
						{/foreach}
						{if isset($carrier)}
						<b>{l s='Carrier:' pdf='true'}</b><br />
						{$carrier->name}<br />
						<br />
						{/if}
						<!-- / CUSTOMER INFORMATIONS -->
					</td>
				</tr>
			</table>
		</td>
		<td style="width: 50%">
			<table style="width: 100%">
				<tr>
					<td style="width: 100%">
						{l s='Billing & Delivery Address' pdf='true'}<br />
						{$delivery_address}
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<!-- / ADDRESSES -->

<table>
	<tr><td style="line-height: 4px">&nbsp;</td></tr>
</table>

<!-- PRODUCTS TAB -->
<table style="width: 100%">
	<tr>
		<td style="width: 100%; text-align: right">
			<table style="width: 100%" border="1">
				<tr style="line-height:4px;">
					<td style="text-align: left; background-color: #4D4D4D; color: #FFF; padding-left: 10px; font-weight: bold; width: 70%">{l s='PRODUCT' pdf='true'}</td>
					<td style="background-color: #4D4D4D; color: #FFF; text-align: center; font-weight: bold; width: 10%">{l s='PRICE' pdf='true'}</td>
					<td style="background-color: #4D4D4D; color: #FFF; text-align: center; font-weight: bold; width: 10%">{l s='QTY' pdf='true'}</td>
					<td style="background-color: #4D4D4D; color: #FFF; text-align: center; font-weight: bold; width: 10%">{l s='SUM' pdf='true'}</td>
				</tr>
				{foreach $order_details as $order_detail}
				{cycle values='#FFF,#DDD' assign=bgcolor}
				<tr style="line-height:4px;background-color:{$bgcolor};">
					<td style="text-align: left; width: 70%">{$order_detail.product_name}</td>
					{if $order_detail.reduction_percent > 0}
						<td style="text-align: center; width: 10%">{displayPrice price=$order_detail.product_quantity_discount}</td>
					{else}
						<td style="text-align: center; width: 10%">{displayPrice price=$order_detail.product_price}</td>
					{/if}
					<td style="text-align: center; width: 10%">{$order_detail.product_quantity}</td>
					<td style="text-align: center; width: 10%">{displayPrice price=$order_detail.total_price_tax_excl|intval}</td>
				</tr>
					{foreach $order_detail.customizedDatas as $customizationPerAddress}
						{foreach $customizationPerAddress as $customizationId => $customization}
							<tr style="line-height:6px;background-color:{$bgcolor};">
								<td style="line-height:3px; text-align: left; width: 70%; vertical-align: top">
										<blockquote>
											{if isset($customization.datas[$smarty.const._CUSTOMIZE_TEXTFIELD_]) && count($customization.datas[$smarty.const._CUSTOMIZE_TEXTFIELD_]) > 0}
												{foreach $customization.datas[$smarty.const._CUSTOMIZE_TEXTFIELD_] as $customization_infos}
													{$customization_infos.name}: {$customization_infos.value}
													{if !$smarty.foreach.custo_foreach.last}<br />
													{else}
													<div style="line-height:0.2pt">&nbsp;</div>
													{/if}
												{/foreach}
											{/if}

											{if isset($customization.datas[$smarty.const._CUSTOMIZE_FILE_]) && count($customization.datas[$smarty.const._CUSTOMIZE_FILE_]) > 0}
												{count($customization.datas[$smarty.const._CUSTOMIZE_FILE_])} {l s='image(s)' pdf='true'}
											{/if}
										</blockquote>
								</td>
								<td style="text-align: right; width: 10%"></td>
								<td style="text-align: center; width: 10%; vertical-align: top">({$customization.quantity})</td>
								<td style="text-align: right; width: 10%"></td>
							</tr>
						{/foreach}
					{/foreach}
				{/foreach}

				<tr>
					<td style="text-align: right; width: 90%" colspan="3">{l s="Total paid" pdf='true'}</td>
					<td style="text-align: center; width: 10%">{displayPrice price=$order->total_products}</td>
				</tr>
				<tr>
					<td style="text-align: right; width: 90%" colspan="3">{l s="Order Discount" pdf='true'}</td>
					<td style="text-align: center; width: 10%">{displayPrice price=$order->total_discounts}</td>
				</tr>
				<tr>
					<td style="text-align: right; width: 90%" colspan="3">{l s="Total" pdf='true'}</td>
					<td style="text-align: center; width: 10%">{displayPrice price=$order->total_paid}</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<!-- / PRODUCTS TAB -->

<table>
	<tr><td style="line-height: 8px">&nbsp;</td></tr>
</table>

{if isset($HOOK_DISPLAY_PDF)}
	<div style="line-height: 1pt">&nbsp;</div>
	<table style="width: 100%">
		<tr>
			<td style="width: 15%"></td>
			<td style="width: 85%">
				{$HOOK_DISPLAY_PDF}
			</td>
		</tr>
	</table>
{/if}

</div>

