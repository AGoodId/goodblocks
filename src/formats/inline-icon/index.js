/**
 * Inline Lucide Icon Format
 */

import { __, sprintf } from '@wordpress/i18n';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { Button, Popover, SearchControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { registerFormatType, insertObject } from '@wordpress/rich-text';
import { renderToStaticMarkup } from 'react-dom/server';
import {
	AlarmClock,
	ArrowRight,
	BadgeCheck,
	BookOpen,
	Bus,
	Calendar,
	Camera,
	Car,
	ChevronRight,
	CircleHelp,
	Clock,
	Coffee,
	ExternalLink,
	Globe,
	Heart,
	Info,
	Mail,
	Map,
	MapPin,
	Megaphone,
	Mic,
	Music,
	Phone,
	Sparkles,
	Star,
	Ticket,
	Train,
	Users,
	Utensils,
	Video,
} from 'lucide-react';

import './style.scss';

const FORMAT_NAME = 'goodblocks/inline-icon';

const ICONS = [
	{
		name: 'calendar',
		label: 'Calendar',
		component: Calendar,
		terms: 'date day schedule',
	},
	{
		name: 'map-pin',
		label: 'Map pin',
		component: MapPin,
		terms: 'place location venue address',
	},
	{
		name: 'ticket',
		label: 'Ticket',
		component: Ticket,
		terms: 'entry admission pass',
	},
	{ name: 'clock', label: 'Clock', component: Clock, terms: 'time hour' },
	{
		name: 'alarm-clock',
		label: 'Alarm clock',
		component: AlarmClock,
		terms: 'time reminder',
	},
	{ name: 'map', label: 'Map', component: Map, terms: 'location area' },
	{
		name: 'train',
		label: 'Train',
		component: Train,
		terms: 'transport transit',
	},
	{ name: 'bus', label: 'Bus', component: Bus, terms: 'transport transit' },
	{ name: 'car', label: 'Car', component: Car, terms: 'parking transport' },
	{
		name: 'utensils',
		label: 'Utensils',
		component: Utensils,
		terms: 'food restaurant dinner',
	},
	{
		name: 'coffee',
		label: 'Coffee',
		component: Coffee,
		terms: 'break cafe fika',
	},
	{ name: 'music', label: 'Music', component: Music, terms: 'concert sound' },
	{ name: 'mic', label: 'Mic', component: Mic, terms: 'speaker talk' },
	{
		name: 'users',
		label: 'Users',
		component: Users,
		terms: 'people audience group',
	},
	{ name: 'heart', label: 'Heart', component: Heart, terms: 'favorite care' },
	{
		name: 'star',
		label: 'Star',
		component: Star,
		terms: 'featured highlight',
	},
	{
		name: 'sparkles',
		label: 'Sparkles',
		component: Sparkles,
		terms: 'special',
	},
	{
		name: 'badge-check',
		label: 'Badge check',
		component: BadgeCheck,
		terms: 'verified approved',
	},
	{
		name: 'info',
		label: 'Info',
		component: Info,
		terms: 'information notice',
	},
	{
		name: 'circle-help',
		label: 'Help',
		component: CircleHelp,
		terms: 'question faq',
	},
	{
		name: 'external-link',
		label: 'External link',
		component: ExternalLink,
		terms: 'url open',
	},
	{
		name: 'arrow-right',
		label: 'Arrow right',
		component: ArrowRight,
		terms: 'next cta',
	},
	{
		name: 'chevron-right',
		label: 'Chevron right',
		component: ChevronRight,
		terms: 'next more',
	},
	{ name: 'mail', label: 'Mail', component: Mail, terms: 'email contact' },
	{ name: 'phone', label: 'Phone', component: Phone, terms: 'call contact' },
	{
		name: 'globe',
		label: 'Globe',
		component: Globe,
		terms: 'website world language',
	},
	{
		name: 'camera',
		label: 'Camera',
		component: Camera,
		terms: 'photo media',
	},
	{ name: 'video', label: 'Video', component: Video, terms: 'film media' },
	{
		name: 'book-open',
		label: 'Book open',
		component: BookOpen,
		terms: 'program guide',
	},
	{
		name: 'megaphone',
		label: 'Megaphone',
		component: Megaphone,
		terms: 'announcement',
	},
];

const ICON_BY_NAME = ICONS.reduce( ( acc, icon ) => {
	acc[ icon.name ] = icon;
	return acc;
}, {} );

function InlineIcon( { iconName = 'calendar', size = 18 } ) {
	const icon = ICON_BY_NAME[ iconName ] || ICONS[ 0 ];
	const Component = icon.component;

	return (
		<Component
			aria-hidden="true"
			focusable="false"
			size={ size }
			strokeWidth={ 2.15 }
		/>
	);
}

function iconToMarkup( iconName ) {
	const icon = ICON_BY_NAME[ iconName ] || ICONS[ 0 ];
	const Component = icon.component;

	return renderToStaticMarkup(
		<Component
			aria-hidden="true"
			focusable="false"
			width="1em"
			height="1em"
			strokeWidth={ 2.15 }
		/>
	);
}

function IconPicker( { value, onSelect } ) {
	const [ search, setSearch ] = useState( '' );
	const normalizedSearch = search.trim().toLowerCase();
	const filteredIcons = useMemo( () => {
		if ( ! normalizedSearch ) {
			return ICONS;
		}

		return ICONS.filter( ( icon ) =>
			[ icon.name, icon.label, icon.terms ]
				.join( ' ' )
				.toLowerCase()
				.includes( normalizedSearch )
		);
	}, [ normalizedSearch ] );

	return (
		<div className="goodblocks-inline-icon-picker">
			<SearchControl
				label={ __( 'Search icons', 'goodblocks' ) }
				value={ search }
				onChange={ setSearch }
				placeholder={ __(
					'Search calendar, map-pin, ticket…',
					'goodblocks'
				) }
				__nextHasNoMarginBottom
			/>
			<div className="goodblocks-inline-icon-picker__grid">
				{ filteredIcons.map( ( icon ) => (
					<Button
						key={ icon.name }
						className="goodblocks-inline-icon-picker__option"
						isPressed={ value === icon.name }
						onClick={ () => onSelect( icon.name ) }
						label={ sprintf(
							/* translators: %s: icon label. */
							__( 'Insert %s icon', 'goodblocks' ),
							icon.label
						) }
					>
						<InlineIcon iconName={ icon.name } size={ 22 } />
						<span>{ icon.label }</span>
					</Button>
				) ) }
			</div>
			{ filteredIcons.length === 0 && (
				<p className="goodblocks-inline-icon-picker__empty">
					{ __(
						'No matching icons in the curated set.',
						'goodblocks'
					) }
				</p>
			) }
		</div>
	);
}

function Edit( {
	value,
	onChange,
	onFocus,
	isObjectActive,
	activeObjectAttributes,
} ) {
	const [ isPickerOpen, setIsPickerOpen ] = useState( false );
	const activeIconName =
		activeObjectAttributes?.[ 'data-icon' ] || 'calendar';

	const insertIcon = ( iconName ) => {
		const nextValue = insertObject( value, {
			type: FORMAT_NAME,
			attributes: {
				'data-icon': iconName,
				'aria-hidden': 'true',
			},
			innerHTML: iconToMarkup( iconName ),
		} );

		nextValue.start = nextValue.end;
		onChange( nextValue );
		onFocus();
		setIsPickerOpen( false );
	};

	return (
		<>
			<RichTextToolbarButton
				icon={ <InlineIcon iconName={ activeIconName } size={ 20 } /> }
				title={ __( 'Inline icon', 'goodblocks' ) }
				onClick={ () => setIsPickerOpen( ! isPickerOpen ) }
				isActive={ isObjectActive || isPickerOpen }
			/>
			{ isPickerOpen && (
				<Popover
					placement="bottom-start"
					onClose={ () => setIsPickerOpen( false ) }
				>
					<IconPicker
						value={ activeIconName }
						onSelect={ insertIcon }
					/>
				</Popover>
			) }
		</>
	);
}

registerFormatType( FORMAT_NAME, {
	title: __( 'Inline icon', 'goodblocks' ),
	tagName: 'span',
	className: 'goodblocks-inline-icon',
	attributes: {
		'data-icon': 'data-icon',
		'aria-hidden': 'aria-hidden',
	},
	contentEditable: false,
	edit: Edit,
} );
