{foreach $list as $cart_rule}
	<tr class="conf_body" style="color: #DF5656; font-style: italic;">
		<td colspan="3" style="border:1px solid #f2ecde;padding:7px 10px; text-align: right; border-left: none;">
			<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
				<strong><i>{$cart_rule['voucher_name']}</i></strong>
			</font>
		</td>
		<td colspan="3" style="border:1px solid #f2ecde;border-right: none;padding:7px 0; text-align: center;">
			<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
				<strong><i>{$cart_rule['voucher_reduction']}</i></strong>
			</font>
		</td>
	</tr>
{/foreach}
