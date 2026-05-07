<?php
/**
 * My Class Schedule — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

$events = goodblocks_get_events( [
	'posts_per_page' => $attributes['eventsToShow'] ?? 300,
	'show_past'      => true,
] );

$classes = [];

foreach ( $events as $event ) {
	if ( ! $event['class'] ) {
		continue;
	}

	$classes[ strtolower( $event['class'] ) ] = $event['class'];
}

asort( $classes );
$list_id = wp_unique_id( 'goodblocks-class-list-' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'goodblocks-event-class-schedule' ] ); ?>>
	<?php if ( ! empty( $attributes['heading'] ) ) : ?>
		<h2 class="goodblocks-event-class-schedule__heading"><?php echo esc_html( $attributes['heading'] ); ?></h2>
	<?php endif; ?>

	<div class="goodblocks-event-class-schedule__search">
		<input type="search" list="<?php echo esc_attr( $list_id ); ?>" placeholder="<?php echo esc_attr( $attributes['placeholder'] ?? __( 'Search class', 'goodblocks' ) ); ?>" data-class-schedule-search>
		<datalist id="<?php echo esc_attr( $list_id ); ?>">
			<?php foreach ( $classes as $class ) : ?>
				<option value="<?php echo esc_attr( $class ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
	</div>

	<div class="goodblocks-event-class-schedule__results" data-empty-text="<?php echo esc_attr( $attributes['emptyText'] ?? __( 'Search for your class to see times.', 'goodblocks' ) ); ?>" data-no-results-text="<?php echo esc_attr( $attributes['noResultsText'] ?? __( 'No class matched your search.', 'goodblocks' ) ); ?>">
		<p class="goodblocks-event-class-schedule__message" data-class-schedule-message><?php echo esc_html( $attributes['emptyText'] ?? __( 'Search for your class to see times.', 'goodblocks' ) ); ?></p>

		<?php foreach ( $events as $event ) : ?>
			<?php if ( ! $event['class'] ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<article class="goodblocks-event-class-schedule__item" hidden data-class-schedule-item data-class="<?php echo esc_attr( $event['class'] ); ?>" data-title="<?php echo esc_attr( $event['title'] ); ?>">
				<div class="goodblocks-event-class-schedule__type"><?php echo esc_html( $event['type_label'] ); ?></div>
				<div class="goodblocks-event-class-schedule__main">
					<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
					<p>
						<span><?php echo esc_html( $event['range_label'] ); ?></span>
						<?php if ( $event['venue'] ) : ?>
							<span><?php echo esc_html( $event['venue'] ); ?></span>
						<?php endif; ?>
					</p>
				</div>
				<div class="goodblocks-event-class-schedule__links">
					<?php if ( $event['stream'] ) : ?>
						<a href="<?php echo esc_url( $event['stream'] ); ?>"><?php esc_html_e( 'Stream', 'goodblocks' ); ?></a>
					<?php endif; ?>
					<?php if ( $event['results'] ) : ?>
						<a href="<?php echo esc_url( $event['results'] ); ?>"><?php esc_html_e( 'Results', 'goodblocks' ); ?></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
