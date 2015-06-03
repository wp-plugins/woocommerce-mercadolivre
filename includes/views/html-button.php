<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
		<?php echo $this->get_tooltip_html( $data ); ?>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
			<a href="<?php echo esc_attr( $data['href'] ); ?>" class="<?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></a>
			<?php echo $this->get_description_html( $data ); ?>
		</fieldset>
	</td>
</tr>