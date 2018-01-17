{if $disable == false}
<div id="productOfTheDay" >
    <div class="homepage-heading newproducts">
        <div class="heading">
            <h1>Товар дня</h1>
        </div>
        <hr>
    </div>
    <script>
        $(document).ready(function(){
            $("#homepage-slider").width("80%");
        });
    </script>
    {include file="$tpl_dir./product-list.tpl" products=$product onePerLine=true class='tab-pane' slider=0}
</div>
{/if}

