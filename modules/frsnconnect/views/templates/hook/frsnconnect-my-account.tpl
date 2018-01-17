{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
<li>
    <a href="{$link->getModuleLink('frsnconnect', 'snaccount')|escape:'htmlall':'UTF-8'}" title="{l s='My Socials' mod='frsnconnect'}">
        {if !$in_footer}<img {if isset($mobile_hook)}src="{$module_template_dir}img/snicon.png" class="ui-li-icon ui-li-thumb"{else}src="{$module_template_dir}img/snicon.png" class="icon"{/if} alt="{l s='My Socials' mod='frsnconnect'}"/>{/if}
	{l s='My Socials' mod='frsnconnect'}
    </a>
</li>
