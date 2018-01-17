{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
{if isset($html)}
    {literal}
    <script type="text/javascript">
        $('document').ready( function() {
        var form = $('#login_form'); 
        if (!form)
            return;
        var newForm = document.createElement('form');
    {/literal}
        newForm.setAttribute('action', '{$link->getModuleLink("frsnconnect", "actions", ["process" => "accAuth"], true)}');
        newForm.setAttribute('method', 'post');
        newForm.setAttribute('id', 'frsnconnect_form');
        newForm.setAttribute('class', 'std clearfix');
        newForm.innerHTML = '{$html}';
    {literal}
        $(newForm).insertAfter(form);
        });
    </script>
    {/literal}
{else}
    {if $not_services}    
        <input type="hidden" value="{$back}" name="back"/>                            
        {include file="$tpl_path"}            
    {/if} 
{/if}


