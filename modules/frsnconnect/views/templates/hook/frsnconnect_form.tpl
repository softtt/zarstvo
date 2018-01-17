{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
{if $not_services}
<fieldset>
    <h3 class="page-subheading" >{l s='Social Networking Services' mod='frsnconnect'}</h3>
    <div class="form_content clearfix">
        {if $auth}<p>{l s='You can register or login to the site using your account for some services' mod='frsnconnect'}.</p>{/if}
        <p class="submit">
            {foreach from=$not_services item=v key=k}
            <input type="submit" id="SubmitCreate_{$k}" name="snLogin_{$k}" class="submit_{$k}" value="" title="{$v['sn_service_name_full']}" />
            {/foreach}
        </p>                         
    </div>		
</fieldset>
{/if}
