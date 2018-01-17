{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
<script type="text/javascript">
    {literal}
    $('document').ready(function() {
    $('img[rel^=ajax_id_serv_]').click(function() {
        var idSrv =  $(this).attr('rel').replace('ajax_id_serv_', '');
        var parent = $(this).parent().parent();
        $.ajax({
    {/literal}        
            url: "{$link->getModuleLink('frsnconnect', 'actions', ['process' => 'remove'], true)}",
    {literal} 
            type: "POST",
            dataType: "json",
            async: true,
            cache: false,
            data: {'id_sn_service': idSrv, 'ajax': true},
            success: function(data) {
                if (!data.hasError) {
                    $("form#frsnconnect_form").slideUp("slow", function() {
                        $("form#frsnconnect_form").html(data.form);
                    });
                    $("form#frsnconnect_form").slideDown("slow");

                    parent.fadeOut("normal", function() {
                        parent.remove();
                    });
                }
            },
            error: function (data, status, e) {
                alert(e);
            }				
        });
    });
    });
    {/literal}
</script>
{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">{l s='My account' mod='frsnconnect'}</a>
    <span class="navigation-pipe">{$navigationPipe}</span>{l s='My Socials' mod='frsnconnect'}
{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<div>
    <h1>{l s='My Socials' mod='frsnconnect'}</h1>
    <p>{l s='You can connect your account to your accounts in social networks and use them to login to our website.' mod='frsnconnect'}</p>
    {include file="$tpl_dir./errors.tpl"}
    <form method="post" id="frsnconnect_form" class="std clearfix" action="{$link->getModuleLink('frsnconnect', 'actions', ['process' => 'accAdd'], true)}">
        {include file="$tpl_path"}            
    </form>        
    {if $connect_serv}
    <div>
        {foreach from=$connect_serv  item=service  key=lkey } 
	<div class="frsnconnect-myaccount clearfix">
            <a href="#" class="serv_img_link {$lkey}_48" title="{$service.sn_service_name_full|escape:'htmlall':'UTF-8'}"></a>
            <h3>{$service.id|escape:'htmlall':'UTF-8'}</h3>
            <div class="remove">
                <img rel="ajax_id_serv_{$service.id_sn_service|escape:'htmlall':'UTF-8'}" src="{$img_dir}icon/delete.gif" alt="" class="icon" />
            </div>
	</div>
	{/foreach}
    </div>
    {/if}

    <ul class="footer_links">
        <li class="fleft">
            <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">
                <img src="{$img_dir}icon/my-account.gif" alt="" class="icon" />
            </a>
            <a href="{$link->getPageLink('my-account', true)|escape:'htmlall':'UTF-8'}">
                {l s='Back to Your Account' mod='frsnconnect'}
            </a>
        </li>
    </ul>
</div>
