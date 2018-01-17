<div id="opc_order_info" class="opc-main-block">

    <form action="{$link->getPageLink('authentication', true)|escape:'html':'UTF-8'}" method="post" id="new_account_form" class="std" autocomplete="on" autofill="on">
        <fieldset>
            <div class="box">
                <h2 class="page-heading">{l s='Checkout'}</h2>
                <h3 class="page-subheading">{l s='Delivery and payment'}</h3>

                <div id="opc_account_form">

                    <!-- Error return block -->
                    <div id="opc_account_errors" class="alert alert-danger" style="display: none;"></div>
                    <!-- END Error return block -->
                    <!-- Account -->
                    <input type="hidden" id="is_new_customer" name="is_new_customer" value="0" />
                    <input type="hidden" id="opc_id_customer" name="opc_id_customer" value="{if isset($guestInformations) && isset($guestInformations.id_customer) && $guestInformations.id_customer}{$guestInformations.id_customer}{else}0{/if}" />
                    <input type="hidden" id="opc_id_address_delivery" name="opc_id_address_delivery" value="{if isset($guestInformations) && isset($guestInformations.id_address_delivery) && $guestInformations.id_address_delivery}{$guestInformations.id_address_delivery}{else}0{/if}" />
                    <input type="hidden" id="opc_id_address_invoice" name="opc_id_address_invoice" value="{if isset($guestInformations) && isset($guestInformations.id_address_delivery) && $guestInformations.id_address_delivery}{$guestInformations.id_address_delivery}{else}0{/if}" />
                    <input type="hidden" id="id_country" name="id_country" value="{$sl_country}">


                    <div class="required text form-group block-left">
                        <label for="email">{l s='Email'} <sup>*</sup></label>
                        <input type="text" class="text form-control validate is_required" id="email" name="email" data-validate="isEmail" value="{if isset($guestInformations) && isset($guestInformations.email) && $guestInformations.email}{$guestInformations.email}{/if}" placeholder="{l s='Email'}"/>
                    </div>

                    <div class="required form-group">
                        <label for="phone">{l s='Phone'} <sup>*</sup></label>
                        <input type="text" class="text form-control validate is_required" name="phone" id="phone"  data-validate="isPhoneNumber" value="{if isset($guestInformations) && isset($guestInformations.phone) && $guestInformations.phone}{$guestInformations.phone}{else}{/if}" data-mask="+0 (000) 000-00-00" placeholder="{l s='Phone'}"/>
                    </div>

                    <div class="required form-group block-left">
                        <label for="customer_firstname">{l s='First name'} <sup>*</sup></label>
                        <input type="text" class="text form-control validate is_required" id="customer_firstname" name="customer_firstname" onblur="$('#firstname').val($(this).val());" data-validate="isName" value="{if isset($guestInformations) && isset($guestInformations.customer_firstname) && $guestInformations.customer_firstname}{$guestInformations.customer_firstname}{/if}" placeholder="{l s='First name'}"/>
                    </div>

                    <div class="required form-group">
                        <label for="customer_lastname">{l s='Last name'} <sup>*</sup></label>
                        <input type="text" class="form-control validate is_required" id="customer_lastname" name="customer_lastname" onblur="$('#lastname').val($(this).val());" data-validate="isName" value="{if isset($guestInformations) && isset($guestInformations.customer_lastname) && $guestInformations.customer_lastname}{$guestInformations.customer_lastname}{/if}" placeholder="{l s='Last name'}"/>
                    </div>

                    <!-- Delivery methods -->
                    <div class="required select form-group block-full-width">
                        <label for="id_delivery">{l s='Delivery method'}</label>
                        <select name="id_delivery" id="id_delivery" class="form-control">
                        {if isset($delivery_option_list)}
                            {foreach $delivery_option_list as $id_address => $option_list}
                                {foreach $option_list as $key => $option}
                                    {foreach $option.carrier_list as $carrier}
                                        {if (isset($point_of_delivery) && $point_of_delivery && $carrier.instance->is_pickup) || !$carrier.instance->is_pickup}
                                            <option id="delivery_option_{$id_address|intval}_{$option@index}"
                                                name="delivery_option[{$id_address|intval}]" value="{$key}"
                                                data-key="{$key}" data-id_address="{$id_address|intval}"
                                                data-is_pickup="{$carrier.instance->is_pickup}"
                                                {if isset($delivery_option[$id_address]) && $delivery_option[$id_address] == $key} selected{/if}
                                            >
                                                {$carrier.instance->name|escape:'htmlall':'UTF-8'}
                                            </option>
                                        {/if}
                                    {/foreach}
                                {/foreach}
                            {/foreach}
                        {/if}
                        </select>
                        <p class="order-note">
                            <sup>*</sup><a href="https://ru.zarstvo-shop.com/content/1-delivery-and-payment#delivery">доставка оплачивается по тарифам перевозчика</a>
                            <br>
                            <span class="order-note-delivery">Стоимость доставки составит <span class="order-note-delivery-cost"></span></span>
                        </p>
                    </div>

                    <div class="address_block">
                        <div class="required text form-group block-left">
                            <label for="address2">{l s='Region'} <sup>*</sup></label>
                            <input type="text" class="text form-control validate is_required" name="address2" id="address2" data-validate="isAddress" value="{if isset($guestInformations) && isset($guestInformations.region) && isset($guestInformations) && isset($guestInformations.region) && $guestInformations.region}{$guestInformations.region}{/if}"
                            placeholder="{l s='Region'}" />
                        </div>

                        <div class="required text form-group">
                            <label for="city">{l s='Locality'} <sup>*</sup></label>
                            <input type="text" class="text form-control validate is_required" name="city" id="city" data-validate="isCityName" value="{if isset($guestInformations) && isset($guestInformations.city) && $guestInformations.city}{$guestInformations.city}{/if}" placeholder="{l s='Locality'}" />
                        </div>
                    </div>

                    <div class="address_block">
                        <div class="required postcode text form-group">
                            <label for="postcode">{l s='Zip/Postal code'} <sup>*</sup></label>
                            <input type="text" class="text form-control validate is_required" name="postcode" id="postcode" data-validate="isPostCode" value="{if isset($guestInformations) && isset($guestInformations.postcode) && $guestInformations.postcode}{$guestInformations.postcode}{/if}" placeholder="{l s='Zip/Postal code'}"/>
                        </div>

                        <div class="required street form-group">
                            <label for="street">{l s='Street'} <sup>*</sup></label>
                            <input type="text" class="text form-control validate is_required" name="street" id="street" data-validate="isAddress" placeholder="{l s='Street'}"/>
                        </div>

                        <div class="required house form-group">
                            <label for="house">{l s='House'} <sup>*</sup></label>
                            <input type="text" class="text form-control validate is_required" name="house" id="house" data-validate="isAddress" placeholder="{l s='House'}"/>
                        </div>

                        <div class="housing form-group">
                            <label for="housing">{l s='Housing'}</label>
                            <input type="text" class="text form-control validate" name="housing" id="housing" data-validate="isAddress" placeholder="{l s='Housing'}"/>
                        </div>

                        <div class="apartment form-group">
                            <label for="apartment">{l s='Apartment'}</label>
                            <input type="text" class="text form-control validate" name="apartment" id="apartment" data-validate="isAddress" placeholder="{l s='Apartment'}"/>
                        </div>

                        <div class="required text form-group address1">
                            <label for="address1">{l s='Address'} <sup>*</sup></label>
                            <input type="text" class="text form-control validate" name="address1" id="address1" data-validate="isAddress" value="{if isset($guestInformations) && isset($guestInformations.address1) && isset($guestInformations) && isset($guestInformations.address1) && $guestInformations.address1}{$guestInformations.address1}{/if}"
                            placeholder="{l s='Address'}"/>
                        </div>
                    </div>

                    {if $HOOK_PAYMENT}
                        <!-- Payment methods -->
                        <label for="id_payment">{l s='Payment method'}</label>
                        <div class="required form-group block-full-width payment-methods" id="id_payment">
                            {$HOOK_PAYMENT}
                        </div>
                    {else}
                        <p class="alert alert-warning">{l s='No payment modules have been installed.'}</p>
                    {/if}

                    <div id="select_bonus" class="form-group block-full-width" style="display: none;">
                        <label>{l s='For Prepayment method you have to choose a bonus:'}</label>

                        <div class="bonus col-md-6 col-xs-12">
                            <input id="discount_id" type="radio" name="bonus" value="">
                            <label for="discount_id" class="bonus-label">
                                {l s='Discount'}
                            </label>
                        </div>

                        <div class="bonus col-md-6 col-xs-12">
                            <input id="free_shipping_id" type="radio" name="bonus" value="">
                            <label for="free_shipping_id" class="bonus-label">
                                {l s='Free shipping'}
                            </label>
                        </div>
                    </div>

                    <div class="text form-group message-block">
                        <p class="title">{l s='Message'}</p>
                        <textarea class="form-control message" cols="120" rows="3" name="message" id="message"></textarea>
                    </div>

                    <input type="hidden" name="alias" id="alias" value="{l s='My address'}"/>

                    {if isset($newsletter) && $newsletter}
                        <div class="checkbox">
                            <label for="newsletter">
                            <input type="checkbox" name="newsletter" id="newsletter" checked value="1"{if isset($guestInformations) && isset($guestInformations.newsletter) && $guestInformations.newsletter} checked="checked"{/if} autocomplete="off"/>
                            {l s='Sign up for our newsletter!'}</label>
                        </div>
                    {/if}

                    {if $conditions AND $cms_id}
                        <div class="checkbox">
                            <input type="checkbox" name="cgv" id="cgv" value="1" checked="checked" />
                            <label for="cgv">{l s='I agree to the terms of service and will adhere to them unconditionally.'}</label>
                            <a href="{$link_conditions|escape:'html':'UTF-8'}" class="iframe" rel="nofollow">{l s='(Read the Terms of Service)'}</a>
                        </div>
                    {/if}

                    <div class="form-group personal-discount-info">
                        {if isset($guestInformations) && isset($guestInformations.discount) && $guestInformations.discount && $guestInformations.discount->getDiscountPercent() > 0 && isset($guestInformations.id_customer) && $guestInformations.id_customer > 0}
                            <p type="text" class="text fixed-text" id="discount_card">
                                {l s='Your discount percent: '} <b>{$guestInformations.discount->getDiscountPercent()|number_format}%</b>
                            </p>
                        {elseif $guestInformations.id_customer == 0}
                            <p type="text" class="text fixed-text">
                                <a href="{$link->getPageLink('authentication', true)|escape:'html':'UTF-8'}">{l s='Login or register to use your presonal discout card'}</a>
                            </p>
                        {/if}

                        <p type="text" class="text text-fixed" style="text-align: center; margin-top: 9px;">
                            <b><a href="https://ru.zarstvo-shop.com/content/57-diskontnaya-sistema-i-nakopitelnaya-skidka?content_only=1" class="iframe" rel="nofollow" style="color: #DF5656;">Узнайте подробности о системе накопительных скидок!</a></b>
                        </p>
                    </div>

                    <div class="submit opc-add-save clearfix">
                        <p class="order-note-delivery">Стоимость доставки составит <span class="order-note-delivery-cost"></span></p>

                        <button type="submit" name="submitAccount" id="submitAccount" class="btn btn-default button button-medium"><span>{l s='Order'}</span></button>
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

