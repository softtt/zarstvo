{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
{capture name=path}
<a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">
    {l s='My account' mod='frscconnect'}
</a>
<span class="navigation-pipe">{$navigationPipe}</span>{l s='My Socials' mod='frsnconnect'}
{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div>
    <h1>{l s='My Socials' mod='frsnconnect'}</h1>
    {include file="$tpl_dir./errors.tpl"}

    <ul class="footer_links">
        <li class="fleft">
            <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}"><img src="{$img_dir}icon/my-account.gif" alt="" class="icon" /></a>
            <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">{l s='Back to Your Account' mod='frsnconnect'}</a>
        </li>
    </ul>
</div>