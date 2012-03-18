<?php if( is_array( $results ) && count( $results ) > 0 ): ?>
	<ul>
		<?php foreach( $results as $result ): ?>
			<li><?php echo date( "Y-m-d H:i:s", strtotime( $result->created_at ) )?> - <?php echo $result->text;?></li>
		<?php endforeach ?>
	</ul>
<?php endif; ?>

