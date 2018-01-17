{foreach $list as $product}
<tr style="color:#3B3B3B">
	<td style="text-align: left; border:1px solid #f2ecde; padding: 7px 10px; line-height: 16px; border-left: none;">
		<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
			{$product['name']}
		</font>
	</td>
	<td style="text-align: center; border:1px solid #f2ecde; width: 70px">
		<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
			{$product['unit_price']}
		</font>
	</td>
	<td style="text-align: center; border:1px solid #f2ecde;">
		<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
			{$product['quantity']}
		</font>
	</td>
	<td style="text-align: center; border:1px solid #f2ecde; border-right: none; width: 100px">
		<font size="2" face="Open-sans,Arial,Helvetica,sans-serif;color:#748946">
			{$product['price']}
		</font>
	</td>
</tr>
	{*
	{foreach $product['customization'] as $customization}
		<tr>
		<td colspan="2" style="border:1px solid #f2ecde;">
			<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
				<strong>{$product['name']}</strong><br>
				{$customization['customization_text']}
			</font>
		</td>
		<td style="border:1px solid #f2ecde;">
			<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
				{$product['unit_price']}
			</font>
		</td>
		<td style="border:1px solid #f2ecde;">
			<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
				{$customization['customization_quantity']}
			</font>
		</td>
		<td style="border:1px solid #f2ecde;">
			<font size="2" face="Open-sans,Arial,Helvetica,sans-serif">
				{$customization['quantity']}
			</font>
		</td>
	</tr>
	{/foreach}
	*}
{/foreach}
