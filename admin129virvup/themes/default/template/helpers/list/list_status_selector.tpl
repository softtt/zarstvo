<form action="{$currentIndex}&amp;vieworder&amp;token={$smarty.get.token}" method="post" style="border: none;"
      class="form-horizontal hidden-print">
    <div class="row">
        <div class="col-lg-9">
            <select id="" class="chosen form-control" name="id_order_state">
                {foreach from=$states item=state}
                    <option value="{$state['id_order_state']|intval}"{if isset($currentState) && $state['id_order_state'] == $currentState->id} selected="selected" disabled="disabled"{/if}>{$state['name']|escape}</option>
                {/foreach}
            </select>
            <input type="hidden" name="id_order" value="{$id}"/>
            <input type="hidden" name="quick" value="true"/>
        </div>
        <div class="col-lg-3">
            <button type="submit" name="submitState" class="btn btn-primary" style="margin-left: 15px;" >
                <i class="icon-plus" ></i>
            </button>
        </div>
    </div>
</form>

