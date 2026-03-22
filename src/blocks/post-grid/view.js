/**
 * Post Grid — front-end view script.
 *
 * Handles the timeline layout positioning (two-column masonry).
 */
document.addEventListener('DOMContentLoaded', function () {
	function updateTimelineLayout() {
		const timelines = document.querySelectorAll(
			'.wp-block-goodblocks-post-grid.timeline'
		);

		timelines.forEach(function (timeline) {
			let leftColumnTop = 0;
			let rightColumnTop = 0;

			const items = timeline.querySelectorAll('.timeline-item');

			items.forEach(function (item) {
				const itemHeight = item.offsetHeight;

				item.classList.remove('is-left', 'is-right');

				if (leftColumnTop <= rightColumnTop) {
					item.style.top = leftColumnTop + 'px';
					item.style.left = '50%';
					item.classList.add('is-right');
					leftColumnTop += itemHeight;
				} else {
					item.style.top = rightColumnTop + 'px';
					item.style.left = '0';
					item.classList.add('is-left');
					rightColumnTop += itemHeight;
				}
			});

			const maxHeight = Math.max(leftColumnTop, rightColumnTop);
			timeline.style.height = maxHeight + 'px';
			timeline.style.opacity = '1';
		});
	}

	updateTimelineLayout();

	window.addEventListener('resize', updateTimelineLayout);
	window.addEventListener('load', updateTimelineLayout);
});
