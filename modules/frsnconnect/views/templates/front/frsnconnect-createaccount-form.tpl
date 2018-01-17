{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
<fieldset class="account_creation">
    <h3>{l s='Your personal information' mod='frsnconnect'}</h3>
    <div id="frsn_createaccount_errors" class="error" style="display:none;"></div>
    <p class="radio required">
        <span>{l s='Title'  mod='frsnconnect'}</span>
        {foreach from=$genders key=k item=gender}
        <input type="radio" name="id_gender" id="id_gender{$gender->id}" value="{$gender->id}" {if isset($smarty.post.id_gender) && $smarty.post.id_gender == $gender->id}checked="checked"{/if} />
        <label for="id_gender{$gender->id}" class="top">{$gender->name}</label>
        {/foreach}
    </p>
    <p class="required text">
        <label for="customer_firstname">{l s='First name' mod='frsnconnect'} <sup>*</sup></label>
        <input type="text" class="text" id="customer_firstname" name="customer_firstname" value="{if isset($smarty.post.customer_firstname)}{$smarty.post.customer_firstname}{/if}" />
    </p>
    <p class="required text">
        <label for="customer_lastname">{l s='Last name' mod='frsnconnect'} <sup>*</sup></label>
        <input type="text" class="text" id="customer_lastname" name="customer_lastname" value="{if isset($smarty.post.customer_lastname)}{$smarty.post.customer_lastname}{/if}" />
    </p>
    <p class="required text">
        <label for="email">{l s='E-mail' mod='frsnconnect'} <!--sup>*</sup--></label>
        <input type="text" class="text" id="email" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email}{/if}" />
    </p>
    {if $fr_email_warning}
    <p class="inline-infos">
        {l s='We encourage you to register your real e-mail' mod='frsnconnect'} 
    </p>
    {/if}            
    <p class="select">
        <span>{l s='Date of Birth' mod='frsnconnect'}</span>
        <select id="days" name="days">
            <option value="">-</option>
            {foreach from=$days item=day}
            <option value="{$day}" {if ($sl_day == $day)} selected="selected"{/if}>{$day}&nbsp;&nbsp;</option>
            {/foreach}
        </select>
        <select id="months" name="months">
            <option value="">-</option>
            {foreach from=$months key=k item=month}
            <option value="{$k}" {if ($sl_month == $k)} selected="selected"{/if}>{l s=$month}&nbsp;</option>
            {/foreach}
        </select>
        <select id="years" name="years">
            <option value="">-</option>
            {foreach from=$years item=year}
            <option value="{$year}" {if ($sl_year == $year)} selected="selected"{/if}>{$year}&nbsp;&nbsp;</option>
            {/foreach}
        </select>
    </p>
</fieldset>
{if isset($PS_REGISTRATION_PROCESS_TYPE) && $PS_REGISTRATION_PROCESS_TYPE}
<fieldset class="account_creation">
    <h3>{l s='Your address' mod='frsnconnect'}</h3>
    {if $inOrderOpc}
    <p class="required text">
        <label for="address1">{l s='Address' mod='frsnconnect'} <sup>*</sup></label>
        <input type="text" class="text" name="address1" id="address1" value="{if isset($smarty.post.address1)}{$smarty.post.address1}{/if}" />
    </p>
    <p class="required postcode text">
        <label for="postcode">{l s='Zip / Postal code' mod='frsnconnect'} <sup>*</sup></label>
        <input type="text" class="text" name="postcode" id="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode}{/if}" />
    </p>
    {/if}
    <p class="required text">
        <label for="city">{l s='City' mod='frsnconnect'}{if $inOrderOpc}<sup>*</sup>{/if} </label>
        <input type="text" class="text" name="city" id="city" value="{if isset($smarty.post.city)}{$smarty.post.city}{/if}" />
    </p>
    <p class="required select">
        <label for="id_country">{l s='Country' mod='frsnconnect'} {if $inOrderOpc}<sup>*</sup>{/if}</label>
        <select name="id_country" id="id_country">
            <option value="">-</option>
            {foreach from=$countries item=v}
            <option value="{$v.id_country}" {if ($sl_country == $v.id_country)} selected="selected"{/if}>{$v.name}</option>
            {/foreach}
        </select>
    </p>
    {if $onr_phone_at_least}
    <p class="inline-infos">{l s='You must register at least one phone number' mod='frsnconnect'}</p>
    <p class="text">
        <label for="phone">{l s='Home phone' mod='frsnconnect'}</label>
        <input type="text" class="text" name="phone" id="phone" value="{if isset($smarty.post.phone)}{$smarty.post.phone}{/if}" />
    </p>
    <p class="text">
        <label for="phone_mobile">{l s='Mobile phone' mod='frsnconnect'} {if $onr_phone_at_least}<sup>*</sup>{/if}</label>
        <input type="text" class="text" name="phone_mobile" id="phone_mobile" value="{if isset($smarty.post.phone_mobile)}{$smarty.post.phone_mobile}{/if}" />
    </p>
    {/if}
</fieldset>
{/if}
<p class="cart_navigation required submit">
    <input type="hidden" name="passwd" id="passwd" value="{if isset($smarty.post.passwd)}{$smarty.post.passwd}{/if}" />
    <input type="hidden" name="alias" id="alias" value="{if isset($smarty.post.alias)}{$smarty.post.alias}{else}{l s='My address'}{/if}" />
    {if isset($PS_REGISTRATION_PROCESS_TYPE) && !$PS_REGISTRATION_PROCESS_TYPE}
    <input type="hidden" name="city" id="city" value="{if isset($smarty.post.city)}{$smarty.post.city}{/if}" />
    <input type="hidden" name="id_country" id="city" value="{if isset($smarty.post.id_country)}{$smarty.post.id_country}{/if}" />
    <input type="hidden" name="phone" id="phone" value="{if isset($smarty.post.phone)}{$smarty.post.phone}{/if}" />
    <input type="hidden" name="phone_mobile" id="phone_mobile" value="{if isset($smarty.post.phone_mobile)}{$smarty.post.phone_mobile}{/if}" />
    {/if}
    {if !$inOrderOpc}
    <input type="hidden" name="address1" id="address1" value="{if isset($smarty.post.address1)}{$smarty.post.address1}{/if}" />
    <input type="hidden" name="postcode" id="postcode" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode}{/if}" />
    {/if}

    <input type="hidden" name="email_create" value="1" />
    <input type="hidden" name="is_new_customer" value="1" />
    <input type="hidden" name="sn_serv" value="{if isset($smarty.post.sn_serv)}{$smarty.post.sn_serv}{/if}" />
    <input type="hidden" name="sn_serv_uid" value="{if isset($smarty.post.sn_serv_uid)}{$smarty.post.sn_serv_uid}{/if}" />
    {if isset($back)}
    <input type="hidden" class="hidden" name="back" value="{$back|escape:'htmlall':'UTF-8'}" />
    {/if}
    <input type="submit" name="submitAccount" id="submitAccount{if $inOrderOpc}_SN{/if}" value="{if $inOrderOpc}{l s='Save' mod='frsnconnect'}{else}{l s='Register' mod='frsnconnect'}{/if}" class="exclusive" />
    <span><sup>*</sup>{l s='Required field' mod='frsnconnect'}</span>
</p>

