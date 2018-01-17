{if $DATA_ORG && $DATA_ORG['YA_ORG_ACTIVE']}
    <div class="payment_method col-md-6 col-xs-12">
        <input id="payment_method_ym_{$pt|escape:'html'}" type="radio" name="id_payment" value="{$link->getModuleLink('yamodule', 'redirectk', ['type' => {$pt|escape:'html'}], true)}">
        <span class="payment-label">
            {$buttontext|escape:'html'}
        </span>
        <span class="payment-icon ym_{$pt|escape:'html'}"></span>
    </div>
{/if}
