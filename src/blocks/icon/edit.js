/**
 * Icon Block — Editor
 */

import { __, sprintf } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Popover,
	SearchControl,
	SelectControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import {
	Accessibility,
	AlarmClock,
	ArrowRight,
	BadgeCheck,
	Bike,
	BookOpen,
	Building2,
	Bus,
	Calendar,
	Camera,
	Car,
	ChevronRight,
	CircleHelp,
	Clock,
	CloudSun,
	Coffee,
	Compass,
	ExternalLink,
	Gift,
	Globe,
	Handshake,
	Heart,
	Image,
	Info,
	Landmark,
	Link,
	Mail,
	Map,
	MapPin,
	Megaphone,
	Mic,
	Moon,
	Music,
	Navigation,
	Phone,
	Plane,
	Presentation,
	Sparkles,
	Star,
	Sun,
	Ticket,
	Train,
	Trees,
	Users,
	Utensils,
	Video,
	Volume2,
	Wifi,
} from 'lucide-react';

import './editor.scss';

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
		name: 'navigation',
		label: 'Navigation',
		component: Navigation,
		terms: 'direction route',
	},
	{
		name: 'compass',
		label: 'Compass',
		component: Compass,
		terms: 'direction explore',
	},
	{
		name: 'train',
		label: 'Train',
		component: Train,
		terms: 'transport transit',
	},
	{ name: 'bus', label: 'Bus', component: Bus, terms: 'transport transit' },
	{ name: 'car', label: 'Car', component: Car, terms: 'parking transport' },
	{
		name: 'bike',
		label: 'Bike',
		component: Bike,
		terms: 'cycling transport',
	},
	{ name: 'plane', label: 'Plane', component: Plane, terms: 'travel' },
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
		name: 'volume-2',
		label: 'Volume',
		component: Volume2,
		terms: 'sound audio',
	},
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
		terms: 'special magic',
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
	{ name: 'link', label: 'Link', component: Link, terms: 'url chain' },
	{
		name: 'camera',
		label: 'Camera',
		component: Camera,
		terms: 'photo media',
	},
	{ name: 'image', label: 'Image', component: Image, terms: 'photo media' },
	{ name: 'video', label: 'Video', component: Video, terms: 'film media' },
	{
		name: 'book-open',
		label: 'Book open',
		component: BookOpen,
		terms: 'program guide',
	},
	{
		name: 'presentation',
		label: 'Presentation',
		component: Presentation,
		terms: 'talk seminar',
	},
	{
		name: 'megaphone',
		label: 'Megaphone',
		component: Megaphone,
		terms: 'announcement',
	},
	{ name: 'gift', label: 'Gift', component: Gift, terms: 'offer reward' },
	{
		name: 'handshake',
		label: 'Handshake',
		component: Handshake,
		terms: 'partner meet',
	},
	{
		name: 'accessibility',
		label: 'Accessibility',
		component: Accessibility,
		terms: 'accessible',
	},
	{
		name: 'building-2',
		label: 'Building',
		component: Building2,
		terms: 'venue office',
	},
	{
		name: 'landmark',
		label: 'Landmark',
		component: Landmark,
		terms: 'museum place',
	},
	{
		name: 'trees',
		label: 'Trees',
		component: Trees,
		terms: 'park outdoor nature',
	},
	{ name: 'sun', label: 'Sun', component: Sun, terms: 'day weather' },
	{ name: 'moon', label: 'Moon', component: Moon, terms: 'night evening' },
	{
		name: 'cloud-sun',
		label: 'Cloud sun',
		component: CloudSun,
		terms: 'weather outdoor',
	},
	{ name: 'wifi', label: 'Wifi', component: Wifi, terms: 'internet network' },
];

const ICON_BY_NAME = ICONS.reduce( ( acc, icon ) => {
	acc[ icon.name ] = icon;
	return acc;
}, {} );

function IconPreview( { iconName, size = 24 } ) {
	const icon = ICON_BY_NAME[ iconName ] || ICONS[ 0 ];
	const Component = icon.component;

	return (
		<Component
			aria-hidden="true"
			focusable="false"
			size={ size }
			strokeWidth={ 2 }
		/>
	);
}

function IconPicker( { value, onChange, onClose } ) {
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
		<div className="goodblocks-icon-picker">
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
			<div className="goodblocks-icon-picker__grid">
				{ filteredIcons.map( ( icon ) => (
					<Button
						key={ icon.name }
						className="goodblocks-icon-picker__option"
						isPressed={ value === icon.name }
						onClick={ () => {
							onChange( icon.name );
							onClose();
						} }
						label={ sprintf(
							/* translators: %s: icon label. */
							__( 'Select %s icon', 'goodblocks' ),
							icon.label
						) }
					>
						<IconPreview iconName={ icon.name } />
						<span>{ icon.label }</span>
					</Button>
				) ) }
			</div>
			{ filteredIcons.length === 0 && (
				<p className="goodblocks-icon-picker__empty">
					{ __(
						'No matching icons in the curated set.',
						'goodblocks'
					) }
				</p>
			) }
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { iconName, layout, size, color, alignment, title, text } =
		attributes;
	const [ isPickerOpen, setIsPickerOpen ] = useState( false );
	const selectedIcon = ICON_BY_NAME[ iconName ] || ICONS[ 0 ];

	const blockProps = useBlockProps( {
		className: [
			'goodblocks-icon',
			`goodblocks-icon--${ layout }`,
			`goodblocks-icon--${ size }`,
			`goodblocks-icon--${ color }`,
			`is-aligned-${ alignment }`,
		].join( ' ' ),
	} );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ <IconPreview iconName={ selectedIcon.name } /> }
						label={ __( 'Choose icon', 'goodblocks' ) }
						onClick={ () => setIsPickerOpen( ! isPickerOpen ) }
						isPressed={ isPickerOpen }
					/>
				</ToolbarGroup>
				{ isPickerOpen && (
					<Popover
						placement="bottom-start"
						onClose={ () => setIsPickerOpen( false ) }
					>
						<IconPicker
							value={ selectedIcon.name }
							onChange={ ( nextIcon ) =>
								setAttributes( { iconName: nextIcon } )
							}
							onClose={ () => setIsPickerOpen( false ) }
						/>
					</Popover>
				) }
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Icon', 'goodblocks' ) }>
					<Button
						variant="secondary"
						className="goodblocks-icon-current"
						onClick={ () => setIsPickerOpen( true ) }
					>
						<IconPreview iconName={ selectedIcon.name } />
						<span>{ selectedIcon.label }</span>
					</Button>
					<SelectControl
						label={ __( 'Layout', 'goodblocks' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Icon only', 'goodblocks' ),
								value: 'icon-only',
							},
							{
								label: __( 'Icon + title', 'goodblocks' ),
								value: 'icon-title',
							},
							{
								label: __( 'Icon + text', 'goodblocks' ),
								value: 'icon-text',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<SelectControl
						label={ __( 'Size', 'goodblocks' ) }
						value={ size }
						options={ [
							{
								label: __( 'Small', 'goodblocks' ),
								value: 'small',
							},
							{
								label: __( 'Medium', 'goodblocks' ),
								value: 'medium',
							},
							{
								label: __( 'Large', 'goodblocks' ),
								value: 'large',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { size: value } )
						}
					/>
					<SelectControl
						label={ __( 'Color', 'goodblocks' ) }
						value={ color }
						options={ [
							{
								label: __( 'Inherit', 'goodblocks' ),
								value: 'inherit',
							},
							{
								label: __( 'Brand blue', 'goodblocks' ),
								value: 'brand-blue',
							},
							{
								label: __( 'Gold', 'goodblocks' ),
								value: 'gold',
							},
							{
								label: __( 'White', 'goodblocks' ),
								value: 'white',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { color: value } )
						}
					/>
					<SelectControl
						label={ __( 'Alignment', 'goodblocks' ) }
						value={ alignment }
						options={ [
							{
								label: __( 'Left', 'goodblocks' ),
								value: 'left',
							},
							{
								label: __( 'Center', 'goodblocks' ),
								value: 'center',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { alignment: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<span className="goodblocks-icon__glyph">
					<IconPreview iconName={ selectedIcon.name } />
				</span>
				{ layout !== 'icon-only' && (
					<div className="goodblocks-icon__content">
						<RichText
							tagName="h3"
							className="goodblocks-icon__title"
							value={ title }
							onChange={ ( value ) =>
								setAttributes( { title: value } )
							}
							placeholder={ __( 'Icon title…', 'goodblocks' ) }
							allowedFormats={ [] }
						/>
						{ layout === 'icon-text' && (
							<RichText
								tagName="p"
								className="goodblocks-icon__text"
								value={ text }
								onChange={ ( value ) =>
									setAttributes( { text: value } )
								}
								placeholder={ __(
									'Optional text…',
									'goodblocks'
								) }
							/>
						) }
					</div>
				) }
			</div>
		</>
	);
}
