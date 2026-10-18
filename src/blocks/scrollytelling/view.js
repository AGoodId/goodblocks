import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin( ScrollTrigger );
const motion = gsap.matchMedia();
motion.add(
	'(min-width: 960px) and (prefers-reduced-motion: no-preference)',
	() => {
		document
			.querySelectorAll( '.gb-scrollytelling .gb-story-step__visual' )
			.forEach( ( visual ) => {
				gsap.fromTo(
					visual,
					{ scale: 0.96 },
					{
						scale: 1,
						ease: 'none',
						scrollTrigger: {
							trigger: visual.closest( '.gb-story-step' ),
							start: 'top 85%',
							end: 'top 30%',
							scrub: true,
							invalidateOnRefresh: true,
						},
					}
				);
			} );
	}
);
