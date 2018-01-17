<div id="opc_new_account" class="opc-main-block">

    <form action="{$link->getPageLink('authentication', true)|escape:'html':'UTF-8'}" method="post" id="new_account_form" class="std" autocomplete="on" autofill="on">
        <fieldset>
            <div class="box">
                <h3 id="new_account_title" class="page-subheading">{l s='Checkout'}</h3>

                <div id="opc_account_form">

                <!-- Error return block -->
                <div id="opc_account_errors" class="alert alert-danger" style="display: none;"></div>
                <!-- END Error return block -->
                <!-- Account -->
                <input type="hidden" id="is_new_customer" name="is_new_customer" value="0" />
                <input type="hidden" id="opc_id_customer" name="opc_id_customer" value="{if isset($guestInformations) && isset($guestInformations.id_customer) && $guestInformations.id_customer}{$guestInformations.id_customer}{else}0{/if}" />
                <input type="hidden" id="opc_id_address_delivery" name="opc_id_address_delivery" value="{if isset($guestInformations) && isset($guestInformations.id_address_delivery) && $guestInformations.id_address_delivery}{$guestInformations.id_address_delivery}{else}0{/if}" />
                <input type="hidden" id="opc_id_address_invoice" name="opc_id_address_invoice" value="{if isset($guestInformations) && isset($guestInformations.id_address_delivery) && $guestInformations.id_address_delivery}{$guestInformations.id_address_delivery}{else}0{/if}" />

                <div class="required text form-group">
                    <label for="email">{l s='Email'} <sup>*</sup></label>
                    <input type="text" class="text form-control validate" id="email" name="email" data-validate="isEmail" value="{if isset($guestInformations) && isset($guestInformations.email) && $guestInformations.email}{$guestInformations.email}{/if}" />
                </div>

                <div class="required form-group">
                    <label for="phone">{l s='Phone'} <sup>*</sup></label>
                    <input type="text" class="text form-control validate" name="phone" id="phone"  data-validate="isPhoneNumber" value="{if isset($guestInformations) && isset($guestInformations.phone) && $guestInformations.phone}{$guestInformations.phone}{/if}" />
                </div>

                <div class="required form-group">
                    <label for="firstname">{l s='First name'} <sup>*</sup></label>
                    <input type="text" class="text form-control validate" id="customer_firstname" name="customer_firstname" onblur="$('#firstname').val($(this).val());" data-validate="isName" value="{if isset($guestInformations) && isset($guestInformations.customer_firstname) && $guestInformations.customer_firstname}{$guestInformations.customer_firstname}{/if}" />
                </div>

                <div class="required form-group">
                    <label for="lastname">{l s='Last name'} <sup>*</sup></label>
                    <input type="text" class="form-control validate" id="customer_lastname" name="customer_lastname" onblur="$('#lastname').val($(this).val());" data-validate="isName" value="{if isset($guestInformations) && isset($guestInformations.customer_lastname) && $guestInformations.customer_lastname}{$guestInformations.customer_lastname}{/if}" />
                </div>

                {foreach from=$dlv_all_fields item=field_name}
                {if $field_name eq "city"}
                <div class="required text form-group">
                    <label for="city">{l s='City'} <sup>*</sup></label>
                    <input type="text" class="text form-control validate" name="city" id="city" data-validate="isCityName" value="{if isset($guestInformations) && isset($guestInformations.city) && $guestInformations.city}{$guestInformations.city}{/if}" />
                </div>
                {elseif $field_name eq "address1"}
                <div class="required text form-group">
                    <label for="address1">{l s='Address'} <sup>*</sup></label>
                    <input type="text" class="text form-control validate" name="address1" id="address1" data-validate="isAddress" value="{if isset($guestInformations) && isset($guestInformations.address1) && isset($guestInformations) && isset($guestInformations.address1) && $guestInformations.address1}{$guestInformations.address1}{/if}" />
                </div>
                {elseif $field_name eq "postcode"}
                <div class="required postcode text form-group">
                    <label for="postcode">{l s='Zip/Postal code'} <sup>*</sup></label>
                    <input type="text" class="text form-control validate" name="postcode" id="postcode" data-validate="isPostCode" value="{if isset($guestInformations) && isset($guestInformations.postcode) && $guestInformations.postcode}{$guestInformations.postcode}{/if}" onkeyup="$('#postcode').val($('#postcode').val().toUpperCase());" />
                </div>
                {elseif $field_name eq "country" || $field_name eq "Country:name"}
                <div class="required select form-group">
                    <label for="id_country">{l s='Country'} <sup>*</sup></label>
                    <select name="id_country" id="id_country" class="form-control">
                        {foreach from=$countries item=v}
                        <option value="{$v.id_country}"{if (isset($guestInformations) && isset($guestInformations.id_country) && $guestInformations.id_country == $v.id_country) || (!isset($guestInformations) && $sl_country == $v.id_country)} selected="selected"{/if}>{$v.name|escape:'html':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
                {/if}
                {/foreach}

                <input type="hidden" name="alias" id="alias" value="{l s='My address'}"/>

                {if isset($newsletter) && $newsletter}
                <div class="checkbox">
                    <label for="newsletter">
                    <input type="checkbox" name="newsletter" id="newsletter" checked value="1"{if isset($guestInformations) && isset($guestInformations.newsletter) && $guestInformations.newsletter} checked="checked"{/if} autocomplete="off"/>
                    {l s='Sign up for our newsletter!'}</label>
                    {if array_key_exists('newsletter', $field_required)}
                        <sup> *</sup>
                    {/if}
                </div>
                <div class="checkbox">
                    <label for="optin">
                    <input type="checkbox" name="optin" id="optin" checked value="1"{if isset($guestInformations) && isset($guestInformations.optin) && $guestInformations.optin} checked="checked"{/if} autocomplete="off"/>
                    {l s='Receive special offers from our partners!'}</label>
                    {if array_key_exists('optin', $field_required)}
                        <sup> *</sup>
                    {/if}
                </div>
                {/if}

                <div class="submit opc-add-save clearfix">
                    <button type="submit" name="submitAccount" id="submitAccount" class="btn btn-default button button-medium"><span>{l s='Save'}<i class="icon-chevron-right right"></i></span></button>
                </div>
                <div style="display: none;" id="opc_account_saved" class="alert alert-success">
                    {l s='Account information saved successfully'}
                </div>
                <!-- END Account -->
            </div>
            </div>
        </fieldset>
    </form>
</div>
{strip}
{if isset($guestInformations) && isset($guestInformations.id_state) && $guestInformations.id_state}
    {addJsDef idSelectedState=$guestInformations.id_state|intval}
{else}
    {addJsDef idSelectedState=false}
{/if}
{if isset($guestInformations) && isset($guestInformations.id_state_invoice) && $guestInformations.id_state_invoice}
    {addJsDef idSelectedStateInvoice=$guestInformations.id_state_invoice|intval}
{else}
    {addJsDef idSelectedStateInvoice=false}
{/if}
{if isset($guestInformations) && isset($guestInformations.id_country) && $guestInformations.id_country}
    {addJsDef idSelectedCountry=$guestInformations.id_country|intval}
{else}
    {addJsDef idSelectedCountry=false}
{/if}
{if isset($guestInformations) && isset($guestInformations.id_country_invoice) && $guestInformations.id_country_invoice}
    {addJsDef idSelectedCountryInvoice=$guestInformations.id_country_invoice|intval}
{else}
    {addJsDef idSelectedCountryInvoice=false}
{/if}
{if isset($countries)}
    {addJsDef countries=$countries}
{/if}
{if isset($vatnumber_ajax_call) && $vatnumber_ajax_call}
    {addJsDef vatnumber_ajax_call=$vatnumber_ajax_call}
{/if}
{/strip}
