<?php
/**
 * Event Calendar — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

$timezone     = wp_timezone();
$month_param  = isset( $_GET['goodblocks_month'] ) ? sanitize_text_field( wp_unslash( $_GET['goodblocks_month'] ) ) : '';
$month_param  = preg_match( '/^\d{4}-\d{2}$/', $month_param ) ? $month_param : wp_date( 'Y-m' );
$month_start  = date_create_immutable( $month_param . '-01 00:00:00', $timezone );
$month_start  = $month_start ?: date_create_immutable( current_time( 'mysql' ), $timezone );
$month_start  = $month_start->modify( 'first day of this month' )->setTime( 0, 0, 0 );
$month_end    = $month_start->modify( 'last day of this month' )->setTime( 23, 59, 59 );
$grid_start   = $month_start->modify( 'monday this week' );
$grid_end     = $month_end->modify( 'sunday this week' )->setTime( 23, 59, 59 );
$default_view = in_array( $attributes['defaultView'] ?? 'month', [ 'month', 'agenda' ], true ) ? $attributes['defaultView'] : 'month';
$prev_month   = $month_start->modify( '-1 month' )->format( 'Y-m' );
$next_month   = $month_start->modify( '+1 month' )->format( 'Y-m' );

$events = goodblocks_get_events( [
	'posts_per_page' => absint( $attributes['eventsToShow'] ?? 500 ),
	'show_past'      => ! empty( $attributes['showPast'] ),
	'category_slug'  => $attributes['categorySlug'] ?? '',
	'type'           => $attributes['eventType'] ?? '',
	'from'           => $grid_start->format( 'Y-m-d H:i:s' ),
	'to'             => $grid_end->format( 'Y-m-d H:i:s' ),
] );

$events_by_day = [];
$types         = [];

foreach ( $events as $event ) {
	if ( empty( $event['date_key'] ) ) {
		continue;
	}

	$events_by_day[ $event['date_key'] ][] = $event;
	if ( ! empty( $event['type'] ) ) {
		$types[ $event['type'] ] = $event['type_label'];
	}
}

$weekday_labels = [
	__( 'Mon', 'goodblocks' ),
	__( 'Tue', 'goodblocks' ),
	__( 'Wed', 'goodblocks' ),
	__( 'Thu', 'goodblocks' ),
	__( 'Fri', 'goodblocks' ),
	__( 'Sat', 'goodblocks' ),
	__( 'Sun', 'goodblocks' ),
];

$calendar_label = wp_date( 'F Y', $month_start->getTimestamp() );
$today_key      = wp_date( 'Y-m-d' );
$class_list_id  = wp_unique_id( 'goodblocks-event-calendar-search-' );
?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => 'goodblocks-event-calendar' ] ); ?>
	data-goodblocks-event-calendar
	data-default-view="<?php echo esc_attr( $default_view ); ?>"
>
	<header class="goodblocks-event-calendar__header">
		<div>
			<p class="goodblocks-event-calendar__eyebrow"><?php esc_html_e( 'Calendar', 'goodblocks' ); ?></p>
			<h2><?php echo esc_html( $calendar_label ); ?></h2>
		</div>
		<nav class="goodblocks-event-calendar__nav" aria-label="<?php esc_attr_e( 'Calendar months', 'goodblocks' ); ?>">
			<a href="<?php echo esc_url( add_query_arg( 'goodblocks_month', $prev_month ) ); ?>" aria-label="<?php esc_attr_e( 'Previous month', 'goodblocks' ); ?>">‹</a>
			<a href="<?php echo esc_url( remove_query_arg( 'goodblocks_month' ) ); ?>"><?php esc_html_e( 'Today', 'goodblocks' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'goodblocks_month', $next_month ) ); ?>" aria-label="<?php esc_attr_e( 'Next month', 'goodblocks' ); ?>">›</a>
		</nav>
	</header>

	<?php if ( ! empty( $attributes['showViewToggle'] ) || ! empty( $attributes['showFilters'] ) ) : ?>
		<div class="goodblocks-event-calendar__toolbar">
			<?php if ( ! empty( $attributes['showViewToggle'] ) ) : ?>
				<div class="goodblocks-event-calendar__views" role="tablist" aria-label="<?php esc_attr_e( 'Calendar view', 'goodblocks' ); ?>">
					<button type="button" data-calendar-view="month"><?php esc_html_e( 'Month', 'goodblocks' ); ?></button>
					<button type="button" data-calendar-view="agenda"><?php esc_html_e( 'Agenda', 'goodblocks' ); ?></button>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $attributes['showFilters'] ) ) : ?>
				<div class="goodblocks-event-calendar__filters">
					<label>
						<span><?php esc_html_e( 'Search', 'goodblocks' ); ?></span>
						<input type="search" list="<?php echo esc_attr( $class_list_id ); ?>" placeholder="<?php esc_attr_e( 'Search event, class, or venue', 'goodblocks' ); ?>" data-calendar-search>
						<datalist id="<?php echo esc_attr( $class_list_id ); ?>">
							<?php foreach ( $events as $event ) : ?>
								<?php if ( ! empty( $event['class'] ) ) : ?>
									<option value="<?php echo esc_attr( $event['class'] ); ?>"></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</datalist>
					</label>

					<?php if ( $types ) : ?>
						<label>
							<span><?php esc_html_e( 'Type', 'goodblocks' ); ?></span>
							<select data-calendar-type>
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
	<?php endif; ?>

	<section class="goodblocks-event-calendar__month" data-calendar-panel="month" aria-label="<?php echo esc_attr( $calendar_label ); ?>">
		<div class="goodblocks-event-calendar__weekdays" aria-hidden="true">
			<?php foreach ( $weekday_labels as $weekday_label ) : ?>
				<span><?php echo esc_html( $weekday_label ); ?></span>
			<?php endforeach; ?>
		</div>
		<div class="goodblocks-event-calendar__grid">
			<?php
			$cursor = $grid_start;
			while ( $cursor <= $grid_end ) :
				$date_key       = $cursor->format( 'Y-m-d' );
				$is_other_month = $cursor->format( 'Y-m' ) !== $month_start->format( 'Y-m' );
				$day_events     = $events_by_day[ $date_key ] ?? [];
				?>
				<div class="goodblocks-event-calendar__day<?php echo $is_other_month ? ' is-muted' : ''; ?><?php echo $date_key === $today_key ? ' is-today' : ''; ?>" data-calendar-day="<?php echo esc_attr( $date_key ); ?>">
					<time datetime="<?php echo esc_attr( $date_key ); ?>"><?php echo esc_html( $cursor->format( 'j' ) ); ?></time>
					<?php foreach ( $day_events as $event ) : ?>
						<a
							class="goodblocks-event-calendar__event"
							href="<?php echo esc_url( $event['url'] ); ?>"
							data-calendar-item
							data-type="<?php echo esc_attr( $event['type'] ); ?>"
							data-title="<?php echo esc_attr( $event['title'] ); ?>"
							data-meta="<?php echo esc_attr( trim( $event['class'] . ' ' . $event['venue'] ) ); ?>"
						>
							<span><?php echo esc_html( $event['time_label'] ); ?></span>
							<strong><?php echo esc_html( $event['title'] ); ?></strong>
						</a>
					<?php endforeach; ?>
				</div>
				<?php
				$cursor = $cursor->modify( '+1 day' );
			endwhile;
			?>
		</div>
	</section>

	<section class="goodblocks-event-calendar__agenda" data-calendar-panel="agenda" aria-label="<?php esc_attr_e( 'Agenda', 'goodblocks' ); ?>">
		<?php if ( $events ) : ?>
			<?php foreach ( $events_by_day as $date_key => $day_events ) : ?>
				<div class="goodblocks-event-calendar__agenda-day" data-calendar-group>
					<h3><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $date_key ) ) ); ?></h3>
					<div class="goodblocks-event-calendar__agenda-items">
						<?php foreach ( $day_events as $event ) : ?>
							<article
								class="goodblocks-event-calendar__agenda-item"
								data-calendar-item
								data-type="<?php echo esc_attr( $event['type'] ); ?>"
								data-title="<?php echo esc_attr( $event['title'] ); ?>"
								data-meta="<?php echo esc_attr( trim( $event['class'] . ' ' . $event['venue'] ) ); ?>"
							>
								<div>
									<time datetime="<?php echo esc_attr( $event['start'] ); ?>"><?php echo esc_html( $event['time_label'] ); ?></time>
									<h4><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h4>
								</div>
								<p>
									<?php if ( $event['type'] ) : ?>
										<span><?php echo esc_html( $event['type_label'] ); ?></span>
									<?php endif; ?>
									<?php if ( $event['class'] ) : ?>
										<span><?php echo esc_html( $event['class'] ); ?></span>
									<?php endif; ?>
									<?php if ( $event['venue'] ) : ?>
										<span><?php echo esc_html( $event['venue'] ); ?></span>
									<?php endif; ?>
									<?php if ( $event['is_recurring'] ) : ?>
										<span><?php esc_html_e( 'Repeats', 'goodblocks' ); ?></span>
									<?php endif; ?>
									<?php if ( $event['is_exception'] ) : ?>
										<span><?php esc_html_e( 'Changed occurrence', 'goodblocks' ); ?></span>
									<?php endif; ?>
								</p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="goodblocks-event-calendar__empty"><?php echo esc_html( $attributes['emptyText'] ?? __( 'No calendar events found.', 'goodblocks' ) ); ?></p>
		<?php endif; ?>
	</section>

	<p class="goodblocks-event-calendar__empty" data-calendar-empty hidden>
		<?php echo esc_html( $attributes['emptyText'] ?? __( 'No calendar events found.', 'goodblocks' ) ); ?>
	</p>
</div>
