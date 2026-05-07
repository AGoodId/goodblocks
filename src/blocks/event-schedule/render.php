<?php
/**
 * Event Schedule — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

$events = goodblocks_get_events( [
	'posts_per_page' => $attributes['eventsToShow'] ?? 200,
	'show_past'      => ! empty( $attributes['showPast'] ),
] );

$days    = [];
$classes = [];
$types   = [];

foreach ( $events as $event ) {
	if ( $event['date_key'] ) {
		$days[ $event['date_key'] ] = $event['date_label'];
	}
	if ( $event['class'] ) {
		$classes[ strtolower( $event['class'] ) ] = $event['class'];
	}
	if ( $event['type'] ) {
		$types[ $event['type'] ] = $event['type_label'];
	}
}

asort( $classes );
$class_list_id = wp_unique_id( 'goodblocks-event-classes-' );
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'goodblocks-event-schedule' ] ); ?>>
	<?php if ( $events ) : ?>
		<div class="goodblocks-event-schedule__toolbar" data-goodblocks-schedule-toolbar>
			<div class="goodblocks-event-schedule__days" role="tablist" aria-label="<?php esc_attr_e( 'Schedule days', 'goodblocks' ); ?>">
				<button type="button" class="is-active" data-day=""><?php esc_html_e( 'All days', 'goodblocks' ); ?></button>
				<?php foreach ( $days as $date_key => $date_label ) : ?>
					<button type="button" data-day="<?php echo esc_attr( $date_key ); ?>"><?php echo esc_html( $date_label ); ?></button>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $attributes['showSearch'] ) || ! empty( $attributes['showTypeFilter'] ) ) : ?>
				<div class="goodblocks-event-schedule__filters">
					<?php if ( ! empty( $attributes['showSearch'] ) ) : ?>
						<label>
							<span><?php esc_html_e( 'Find class', 'goodblocks' ); ?></span>
							<input type="search" list="<?php echo esc_attr( $class_list_id ); ?>" placeholder="<?php esc_attr_e( 'Search class or event', 'goodblocks' ); ?>" data-schedule-search>
							<datalist id="<?php echo esc_attr( $class_list_id ); ?>">
								<?php foreach ( $classes as $class ) : ?>
									<option value="<?php echo esc_attr( $class ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						</label>
					<?php endif; ?>

					<?php if ( ! empty( $attributes['showTypeFilter'] ) && $types ) : ?>
						<label>
							<span><?php esc_html_e( 'Type', 'goodblocks' ); ?></span>
							<select data-schedule-type>
								<option value=""><?php esc_html_e( 'All types', 'goodblocks' ); ?></option>
								<?php foreach ( $types as $type => $label ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="goodblocks-event-schedule__list" data-empty-text="<?php echo esc_attr( $attributes['emptyText'] ?? __( 'No schedule items found.', 'goodblocks' ) ); ?>">
			<?php foreach ( $events as $event ) : ?>
				<article class="goodblocks-event-schedule__item" data-schedule-item data-day="<?php echo esc_attr( $event['date_key'] ); ?>" data-class="<?php echo esc_attr( $event['class'] ); ?>" data-title="<?php echo esc_attr( $event['title'] ); ?>" data-type="<?php echo esc_attr( $event['type'] ); ?>">
					<div class="goodblocks-event-schedule__time">
						<span><?php echo esc_html( $event['time_label'] ); ?></span>
						<small><?php echo esc_html( $event['date_label'] ); ?></small>
					</div>
					<div class="goodblocks-event-schedule__body">
						<div class="goodblocks-event-schedule__meta">
							<?php if ( $event['type'] ) : ?>
								<span><?php echo esc_html( $event['type_label'] ); ?></span>
							<?php endif; ?>
							<?php if ( $event['class'] ) : ?>
								<span><?php echo esc_html( $event['class'] ); ?></span>
							<?php endif; ?>
							<?php if ( $event['venue'] ) : ?>
								<span><?php echo esc_html( $event['venue'] ); ?></span>
							<?php endif; ?>
						</div>
						<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
					</div>
					<div class="goodblocks-event-schedule__actions">
						<?php if ( 'scheduled' !== $event['status'] ) : ?>
							<span class="goodblocks-event-schedule__status is-<?php echo esc_attr( $event['status'] ); ?>"><?php echo esc_html( $event['status_label'] ); ?></span>
						<?php endif; ?>
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
	<?php else : ?>
		<p class="goodblocks-event-schedule__empty"><?php echo esc_html( $attributes['emptyText'] ?? __( 'No schedule items found.', 'goodblocks' ) ); ?></p>
	<?php endif; ?>
</div>
