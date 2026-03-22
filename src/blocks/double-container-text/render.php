<?php
$leftTitle          = $attributes['leftTitle'] ?? '';
$leftText           = $attributes['leftText'] ?? '';
$leftMedia          = $attributes['leftMedia'] ?? 0;
$leftPosition       = $attributes['leftPosition'] ?? 'top-left';
$leftColor          = $attributes['leftColor'] ?? '#fff';
$leftOverlayColor   = $attributes['leftOverlayColor'] ?? '#000';
$leftOverlayOpacity = $attributes['leftDimRatio'] ?? 0;
$leftOverlayStyle   = 'background-color: ' . esc_attr( $leftOverlayColor ) . '; opacity: ' . esc_attr( $leftOverlayOpacity / 100 ) . ';';
$leftLink           = $attributes['leftLink'] ?? '';

$rightTitle          = $attributes['rightTitle'] ?? '';
$rightText           = $attributes['rightText'] ?? '';
$rightMedia          = $attributes['rightMedia'] ?? 0;
$rightPosition       = $attributes['rightPosition'] ?? 'top-left';
$rightColor          = $attributes['rightColor'] ?? '#fff';
$rightOverlayColor   = $attributes['rightOverlayColor'] ?? '#000';
$rightOverlayOpacity = $attributes['rightDimRatio'] ?? 0;
$rightOverlayStyle   = 'background-color: ' . esc_attr( $rightOverlayColor ) . '; opacity: ' . esc_attr( $rightOverlayOpacity / 100 ) . ';';
$rightLink           = $attributes['rightLink'] ?? '';
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="double-text-container">
		<?php if ( $leftLink ) : ?>
			<a href="<?php echo esc_url( $leftLink ); ?>"
				class="double-container-text-link double-container-text-link-left">
			<?php endif; ?>
			<div class="double-container-text-left text-pos-<?php echo esc_attr( $leftPosition ); ?>">
				<div class="double-container-text-overlay" style="<?php echo esc_attr( $leftOverlayStyle ); ?>"></div>
				<?php if ( $leftMedia ) :
					$left_mime = get_post_mime_type( $leftMedia );
					if ( strpos( $left_mime, 'image/' ) === 0 ) : ?>
						<img src="<?php echo esc_url( wp_get_attachment_image_url( $leftMedia, 'large' ) ); ?>"
							alt="<?php echo esc_attr( get_post_meta( $leftMedia, '_wp_attachment_image_alt', true ) ); ?>" />
					<?php elseif ( strpos( $left_mime, 'video/' ) === 0 ) : ?>
						<video src="<?php echo esc_url( wp_get_attachment_url( $leftMedia ) ); ?>" autoplay muted loop
							playsinline></video>
					<?php endif;
				endif; ?>
				<div class="double-container-text-content" style="color: <?php echo esc_attr( $leftColor ); ?>">
					<?php if ( $leftTitle ) : ?>
						<h4 class="double-container-text-title"><?php echo esc_html( $leftTitle ); ?></h4>
					<?php endif; ?>
					<?php if ( $leftText ) : ?>
						<div class="double-container-text-text"><?php echo esc_html( $leftText ); ?></div>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $leftLink ) : ?>
			</a>
		<?php endif; ?>

		<?php if ( $rightLink ) : ?>
			<a href="<?php echo esc_url( $rightLink ); ?>"
				class="double-container-text-link double-container-text-link-right">
			<?php endif; ?>
			<div class="double-container-text-right text-pos-<?php echo esc_attr( $rightPosition ); ?>">
				<div class="double-container-text-overlay" style="<?php echo esc_attr( $rightOverlayStyle ); ?>"></div>
				<?php if ( $rightMedia ) :
					$right_mime = get_post_mime_type( $rightMedia );
					if ( strpos( $right_mime, 'image/' ) === 0 ) : ?>
						<img src="<?php echo esc_url( wp_get_attachment_image_url( $rightMedia, 'large' ) ); ?>"
							alt="<?php echo esc_attr( get_post_meta( $rightMedia, '_wp_attachment_image_alt', true ) ); ?>" />
					<?php elseif ( strpos( $right_mime, 'video/' ) === 0 ) : ?>
						<video src="<?php echo esc_url( wp_get_attachment_url( $rightMedia ) ); ?>" autoplay muted loop
							playsinline></video>
					<?php endif;
				endif; ?>
				<div class="double-container-text-content" style="color: <?php echo esc_attr( $rightColor ); ?>">
					<?php if ( $rightTitle ) : ?>
						<h4 class="double-container-text-title"><?php echo esc_html( $rightTitle ); ?></h4>
					<?php endif; ?>
					<?php if ( $rightText ) : ?>
						<div class="double-container-text-text"><?php echo esc_html( $rightText ); ?></div>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $rightLink ) : ?>
			</a>
		<?php endif; ?>
	</div>
</div>
