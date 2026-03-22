<?php
$question        = $attributes['question'] ?? '';
$answers         = $attributes['answers'] ?? [];
$correctIndex    = $attributes['correctIndex'] ?? null;
$backgroundMedia = $attributes['backgroundMedia'] ?? null;
$style           = '';

if ( $backgroundMedia ) {
	$style = "background-image: url(" . esc_url( $backgroundMedia['url'] ) . ");";
}

$uid      = uniqid();
$block_id = 'quiz-' . $uid;

if ( ! empty( $question ) && is_array( $answers ) && count( $answers ) === 3 && $correctIndex !== null ) :
	?>
	<div id="<?php echo esc_attr( $block_id ); ?>" <?php echo get_block_wrapper_attributes( array( 'style' => $style ) ); ?>>
		<div class="question-quiz-container">
			<div class="question-title">
				<h2><?php echo esc_html( $question ); ?></h2>
			</div>
			<div class="question-block-answers">
				<?php foreach ( $answers as $index => $answer ) : ?>
					<div class="question show">
						<div class="form-check">
							<input type="radio" id="answer-<?php echo esc_attr( $uid . '-' . $index ); ?>"
								name="answer-<?php echo esc_attr( $uid ); ?>" class="form-check-input"
								value="<?php echo esc_attr( $index ); ?>" <?php echo $index === $correctIndex ? 'data-correct="true"' : ''; ?> />
							<label for="answer-<?php echo esc_attr( $uid . '-' . $index ); ?>" class="form-check-label">
								<?php echo esc_html( $answer ); ?>
							</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
else :
	?>
	<div class="question-block__error">
		<?php esc_html_e( 'Block is misconfigured.', 'goodblocks' ); ?>
	</div>
	<?php
endif;
?>
