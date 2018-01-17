{if $is_16}
<div class="panel product-tab">
	<h3>{l s='Related Posts:' mod='ph_relatedposts'}</h3>
	<div class="form-group">
		<div class="col-lg-9 col-lg-offset-3">

			<div class="row">
				<div class="col-lg-12">
					{l s='Type to filter posts:' mod='ph_relatedposts'} <input type="text" id="related_posts_filter" size="60" />
				</div>
			</div>

			<div class="row">
				<div class="col-lg-6">
					<p>{l s='Available posts' mod='ph_relatedposts'}</p>

					<select multiple id="ph_relatedposts_left">
						{foreach $posts as $post}
							<option value="{$post.id_simpleblog_post}">{$post.title}</option>
						{/foreach}
					</select>

					<a href="#" id="ph_relatedposts_move_to_right" class="btn btn-default btn-block">
						{l s='Add' mod='ph_relatedposts'}
						<i class="icon-arrow-right"></i>
					</a>

				</div>
				<div class="col-lg-6">
					<p>{l s='Posts related to this product' mod='ph_relatedposts'}</p>

					<select multiple id="ph_relatedposts_right" name="related_posts[]">
						{foreach $selected_posts as $post}
							<option value="{$post.id_simpleblog_post}">{$post.title}</option>
						{/foreach}
					</select>

					<a href="#" id="move_to_left" class="btn btn-default btn-block">
						<i class="icon-arrow-left"></i>
						{l s='Remove' mod='ph_relatedposts'}
					</a>

				</div>
			</div>
		</div>
	</div>
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save and stay'}</button>
	</div>
</div>
{else}
<div class="margin-form">
	<h4>{l s='Related Posts:' mod='ph_relatedposts'}</h4>
	{l s='Type to filter posts:' mod='ph_relatedposts'} <input type="text" id="related_posts_filter" size="60" />
</div>
<div class="separation"></div>
<div class="margin-form">
<table class="double_select">
	<tr>
		<td>
			<select multiple id="ph_relatedposts_left">
				{foreach $posts as $post}
					<option value="{$post.id_simpleblog_post}">{$post.title}</option>
				{/foreach}
			</select>
			<br /><br />
			<a href="#" id="ph_relatedposts_move_to_right" class="multiple_select_add">
				{l s='Add' mod='ph_relatedposts'} &gt;&gt;
			</a>
		</div>
		</td>
		<td>
			<select multiple id="ph_relatedposts_right" name="related_posts[]">
				{foreach $selected_posts as $post}
					<option value="{$post.id_simpleblog_post}">{$post.title}</option>
				{/foreach}
			</select>
			<br /><br />
			<a href="#" id="move_to_left" class="multiple_select_remove">
				&lt;&lt; {l s='Remove' mod='ph_relatedposts'}
			</a>
		</td>
	</tr>
</table>
</div>
<div class="clear">&nbsp;</div>
{/if}

<script type="text/javascript">
{literal}
jQuery.fn.filterByText = function(textbox, selectSingleMatch) {
  return this.each(function() {
    var select = this;
    var options = [];
    $(select).find('option').each(function() {
      options.push({value: $(this).val(), text: $(this).text()});
    });
    $(select).data('options', options);
    $(textbox).bind('change keyup', function() {
      var options = $(select).empty().scrollTop(0).data('options');
      var search = $.trim($(this).val());
      var regex = new RegExp(search,'gi');

      $.each(options, function(i) {
        var option = options[i];
        if(option.text.match(regex) !== null) {
          $(select).append(
             $('<option>').text(option.text).val(option.value)
          );
        }
      });
      if (selectSingleMatch === true && 
          $(select).children().length === 1) {
        $(select).children().get(0).selected = true;
      }
    });
  });
};
{/literal}
$(function() {
    $('#ph_relatedposts_left').filterByText($('#related_posts_filter'), true);
    $('#ph_relatedposts_move_to_right').click(function(){
		return !$('#ph_relatedposts_left option:selected').remove().appendTo('#ph_relatedposts_right');
	})
	$('#move_to_left').click(function(){
		return !$('#ph_relatedposts_right option:selected').remove().appendTo('#ph_relatedposts_left');
	});
	$('#ph_relatedposts_left option').live('dblclick', function(){
		$(this).remove().appendTo('#ph_relatedposts_right');
	});
	$('#ph_relatedposts_right option').live('dblclick', function(){
		$(this).remove().appendTo('#ph_relatedposts_left');
	});
	$('form').on('submit', function()
	{
		$('#ph_relatedposts_right option').each(function(i){
			$(this).attr("selected", "selected");
		});
	});

});
</script>