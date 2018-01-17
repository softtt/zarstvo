<form action="{$currentIndex}&amp;vieworder&amp;token={$smarty.get.token}" method="post"
      class="form-horizontal hidden-print">
    <div class="row">
        <div class="col-lg-9">
            <input type="text" name="tracking_number" value="{$currentCode}">
            <input type="hidden" name="id_order" value="{$id}"/>
            <input type="hidden" name="quick" value="true"/>
            <input type="hidden" name="id_order_carrier" value="{$id}"/>
        </div>
        <div class="">
            <button type="submit" name="submitShippingNumber" class="btn btn-primary">
                <i class="icon-plus" ></i>
            </button>
        </div>
    </div>
</form>
