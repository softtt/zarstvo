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
{capture name=path}{l s='Your addresses'}{/capture}
<div class="box">
	<h1 class="page-subheading">{l s='Your addresses'}</h1>
	<p class="info-title">
		{if isset($id_address) && (isset($smarty.post.alias) || isset($address->alias))}
			{l s='Modify address'}
			{if isset($smarty.post.alias)}
				"{$smarty.post.alias}"
			{else}
				{if isset($address->alias)}"{$address->alias|escape:'html':'UTF-8'}"{/if}
			{/if}
		{else}
			{l s='To add a new address, please fill out the form below.'}
		{/if}
	</p>
	{include file="$tpl_dir./errors.tpl"}
	<p class="required"><sup>*</sup>{l s='Required field'}</p>
	<form action="{$link->getPageLink('address', true)|escape:'html':'UTF-8'}" method="post" class="std" id="add_address">
		{assign var="stateExist" value=false}
		{assign var="postCodeExist" value=false}
		{assign var="dniExist" value=false}
		{assign var="homePhoneExist" value=false}
		{assign var="mobilePhoneExist" value=false}
		{assign var="atLeastOneExists" value=false}
		{foreach from=$ordered_adr_fields item=field_name}
			{if $field_name eq 'vat_number'}
				<div id="vat_area">
					<div id="vat_number">
						<div class="form-group">
							<label for="vat-number" {if in_array($field_name, $required_fields)}class="required"{/if}>
								{l s='VAT number'}
							</label>
							<input type="text" class="form-control validate" data-validate="{$address_validation.$field_name.validate}" id="vat-number" name="vat_number" value="{if isset($smarty.post.vat_number)}{$smarty.post.vat_number}{else}{if isset($address->vat_number)}{$address->vat_number|escape:'html':'UTF-8'}{/if}{/if}" />
						</div>
					</div>
				</div>
			{/if}
			{if $field_name eq 'firstname'}
				<div class="required form-group">
					<label for="firstname" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='First name'}
					</label>
					<input class="is_required validate form-control" data-validate="{$address_validation.$field_name.validate}" type="text" name="firstname" id="firstname" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname}{else}{if isset($address->firstname)}{$address->firstname|escape:'html':'UTF-8'}{/if}{/if}" />
				</div>
			{/if}
			{if $field_name eq 'lastname'}
				<div class="required form-group">
					<label for="lastname" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='Last name'}
					</label>
					<input class="is_required validate form-control" data-validate="{$address_validation.$field_name.validate}" type="text" id="lastname" name="lastname" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname}{else}{if isset($address->lastname)}{$address->lastname|escape:'html':'UTF-8'}{/if}{/if}" />
				</div>
			{/if}
			{if $field_name eq 'city'}
				<div class="required form-group">
					<label for="city" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='City'}
					</label>
					<input class="is_required validate form-control" data-validate="{$address_validation.$field_name.validate}" type="text" name="city" id="city" value="{if isset($smarty.post.city)}{$smarty.post.city}{else}{if isset($address->city)}{$address->city|escape:'html':'UTF-8'}{/if}{/if}" maxlength="64" />
				</div>
				{* if customer hasn't update his layout address, country has to be verified but it's deprecated *}
			{/if}
			{if $field_name eq 'address1'}
				<div class="required form-group">
					<label for="address1" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='Address'}
					</label>
					<input class="is_required validate form-control" data-validate="{$address_validation.$field_name.validate}" type="text" id="address1" name="address1" value="{if isset($smarty.post.address1)}{$smarty.post.address1}{else}{if isset($address->address1)}{$address->address1|escape:'html':'UTF-8'}{/if}{/if}" />
				</div>
			{/if}
			{if $field_name eq 'address2'}
				<div class="required form-group">
					<label for="address2" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='Region'}
					</label>
					<input class="is_required validate form-control" data-validate="{$address_validation.$field_name.validate}" type="text" id="address2" name="address2" value="{if isset($smarty.post.address2)}{$smarty.post.address2}{else}{if isset($address->address2)}{$address->address2|escape:'html':'UTF-8'}{/if}{/if}" />
				</div>
			{/if}
			{if $field_name eq 'postcode'}
				{assign var="postCodeExist" value=true}
				<div class="required postcode form-group unvisible">
					<label for="postcode" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='Zip/Postal Code'}
					</label>
					<input class="is_required validate form-control" data-validate="{$address_validation.$field_name.validate}" type="text" id="postcode" name="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode}{else}{if isset($address->postcode)}{$address->postcode|escape:'html':'UTF-8'}{/if}{/if}" />
				</div>
			{/if}
			{if $field_name eq 'Country:name' || $field_name eq 'country'}
				<div class="required form-group" style="display: none;">
					<label for="id_country">
						{l s='Country'}
					</label>
					<select id="id_country" class="form-control" name="id_country">{$countries_list}</select>
				</div>
			{/if}
			{if $field_name eq 'State:name'}
				{assign var="stateExist" value=true}
				<div class="required id_state form-group">
					<label for="id_state">
						{l s='State'}
					</label>
					<select name="id_state" id="id_state" class="form-control">
						<option value="">-</option>
					</select>
				</div>
			{/if}
			{if $field_name eq 'phone'}
				{assign var="homePhoneExist" value=true}
				<div class="form-group phone-number">
					<label for="phone" {if in_array($field_name, $required_fields)}class="required"{/if}>
						{l s='Phone'}
					</label>
					<input class="{if isset($one_phone_at_least) && $one_phone_at_least}is_required{/if} validate form-control" data-validate="{$address_validation.phone.validate}" type="tel" id="phone" name="phone" value="{if isset($smarty.post.phone)}{$smarty.post.phone}{else}{if isset($address->phone)}{$address->phone|escape:'html':'UTF-8'}{/if}{/if}"  />
				</div>
				<div class="clearfix"></div>
			{/if}
		{/foreach}
		{if !$postCodeExist}
			<div class="required postcode form-group unvisible">
				<label for="postcode" {if in_array($field_name, $required_fields)}class="required"{/if}>
					{l s='Zip/Postal Code'}
				</label>
				<input class="is_required validate form-control" data-validate="{$address_validation.postcode.validate}" type="text" id="postcode" name="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode}{else}{if isset($address->postcode)}{$address->postcode|escape:'html':'UTF-8'}{/if}{/if}" />
			</div>
		{/if}
		{if !$stateExist}
			<div class="required id_state form-group unvisible">
				<label for="id_state" {if in_array($field_name, $required_fields)}class="required"{/if}>
					{l s='State'}
				</label>
				<select name="id_state" id="id_state" class="form-control">
					<option value="">-</option>
				</select>
			</div>
		{/if}
		{if !$dniExist}
			<div class="required dni form-group unvisible">
				<label for="dni">{l s='Identification number'} <sup>*</sup></label>
				<input class="is_required form-control" data-validate="{$address_validation.dni.validate}" type="text" name="dni" id="dni" value="{if isset($smarty.post.dni)}{$smarty.post.dni}{else}{if isset($address->dni)}{$address->dni|escape:'html':'UTF-8'}{/if}{/if}" />
				<span class="form_info">{l s='DNI / NIF / NIE'}</span>
			</div>
		{/if}
		<div class="form-group">
			<label for="other">{l s='Additional information'}</label>
			<textarea class="validate form-control" data-validate="{$address_validation.other.validate}" id="other" name="other" cols="26" rows="3" >{if isset($smarty.post.other)}{$smarty.post.other}{else}{if isset($address->other)}{$address->other|escape:'html':'UTF-8'}{/if}{/if}</textarea>
		</div>
		{if !$homePhoneExist}
			<div class="form-group phone-number">
				<label for="phone">{l s='Home phone'}</label>
				<input class="{if isset($one_phone_at_least) && $one_phone_at_least}is_required{/if} validate form-control" data-validate="{$address_validation.phone.validate}" type="tel" id="phone" name="phone" value="{if isset($smarty.post.phone)}{$smarty.post.phone}{else}{if isset($address->phone)}{$address->phone|escape:'html':'UTF-8'}{/if}{/if}"  />
			</div>
		{/if}
		<div class="clearfix"></div>
		<div class="required form-group" id="adress_alias">
			<label for="alias">{l s='Please assign an address title for future reference.'} <sup>*</sup></label>
			<input type="text" id="alias" class="is_required validate form-control" data-validate="{$address_validation.alias.validate}" name="alias" value="{if isset($smarty.post.alias)}{$smarty.post.alias}{else if isset($address->alias)}{$address->alias|escape:'html':'UTF-8'}{elseif !$select_address}{l s='My address'}{/if}" />
		</div>
		<p class="submit2">
			{if isset($id_address)}<input type="hidden" name="id_address" value="{$id_address|intval}" />{/if}
			{if isset($back)}<input type="hidden" name="back" value="{$back}" />{/if}
			{if isset($mod)}<input type="hidden" name="mod" value="{$mod}" />{/if}
			{if isset($select_address)}<input type="hidden" name="select_address" value="{$select_address|intval}" />{/if}
			<input type="hidden" name="token" value="{$token}" />
			<button type="submit" name="submitAddress" id="submitAddress" class="btn btn-default button button-medium">
				<span>
					{l s='Save'}
				</span>
			</button>
		</p>
	</form>
</div>
<ul class="footer_links clearfix">
	<li>
		<a class="btn btn-defaul button button-small" href="{$link->getPageLink('addresses', true)|escape:'html':'UTF-8'}">
			<span>{l s='Back to your addresses'}</span>
		</a>
	</li>
</ul>
{strip}
{if isset($smarty.post.id_state) && $smarty.post.id_state}
	{addJsDef idSelectedState=$smarty.post.id_state|intval}
{else if isset($address->id_state) && $address->id_state}
	{addJsDef idSelectedState=$address->id_state|intval}
{else}
	{addJsDef idSelectedState=false}
{/if}
{if isset($smarty.post.id_country) && $smarty.post.id_country}
	{addJsDef idSelectedCountry=$smarty.post.id_country|intval}
{else if isset($address->id_country) && $address->id_country}
	{addJsDef idSelectedCountry=$address->id_country|intval}
{else}
	{addJsDef idSelectedCountry=false}
{/if}
{if isset($countries)}
	{addJsDef countries=$countries}
{/if}
{if isset($vatnumber_ajax_call) && $vatnumber_ajax_call}
	{addJsDef vatnumber_ajax_call=$vatnumber_ajax_call}
{/if}
{/strip}
