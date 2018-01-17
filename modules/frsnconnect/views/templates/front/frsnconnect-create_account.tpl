{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
{capture name=path}{l s='Login' mod='frsnconnect'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h1>{l s='Create your account' mod='frsnconnect'}</h1>

{include file="$tpl_dir./errors.tpl"}

<form action="{$link->getModuleLink('frsnconnect', 'actions', ['process' => 'create'], true)}" method="post" id="account-creation_form" class="std clearfix">
    {include file="$tpl_form_path"}            
</form>
