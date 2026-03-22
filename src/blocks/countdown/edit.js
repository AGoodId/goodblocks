import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	BlockControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	DateTimePicker,
	ToggleControl,
	SelectControl,
	ToolbarGroup,
	ToolbarButton,
	Popover,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { calendar } from '@wordpress/icons';

import './editor.scss';

function calculateTimeLeft( targetDate ) {
	if ( ! targetDate ) {
		return null;
	}

	const difference = new Date( targetDate ) - new Date();

	if ( difference > 0 ) {
		return {
			days: Math.floor( difference / ( 1000 * 60 * 60 * 24 ) ),
			hours: Math.floor( ( difference / ( 1000 * 60 * 60 ) ) % 24 ),
			minutes: Math.floor( ( difference / 1000 / 60 ) % 60 ),
			seconds: Math.floor( ( difference / 1000 ) % 60 ),
		};
	}

	return null;
}

function CountdownDisplay( { targetDate, showSeconds, alignment } ) {
	const [ timeLeft, setTimeLeft ] = useState(
		calculateTimeLeft( targetDate )
	);
	const [ animatingUnits, setAnimatingUnits ] = useState( {} );

	useEffect( () => {
		if ( ! targetDate ) {
			return;
		}

		const timer = setInterval( () => {
			const newTimeLeft = calculateTimeLeft( targetDate );

			if ( timeLeft && newTimeLeft ) {
				const changes = {};
				if ( timeLeft.days !== newTimeLeft.days ) {
					changes.days = true;
				}
				if ( timeLeft.hours !== newTimeLeft.hours ) {
					changes.hours = true;
				}
				if ( timeLeft.minutes !== newTimeLeft.minutes ) {
					changes.minutes = true;
				}
				if ( timeLeft.seconds !== newTimeLeft.seconds ) {
					changes.seconds = true;
				}

				if ( Object.keys( changes ).length > 0 ) {
					setAnimatingUnits( changes );
					setTimeout( () => setAnimatingUnits( {} ), 600 );
				}
			}

			setTimeLeft( newTimeLeft );
		}, 1000 );

		return () => clearInterval( timer );
	}, [ targetDate, timeLeft ] );

	if ( ! targetDate ) {
		return (
			<div className="countdown-placeholder">
				<p>{ __( 'Please select a target date', 'goodblocks' ) }</p>
			</div>
		);
	}

	if ( ! timeLeft ) {
		return (
			<div className="countdown-finished">
				<p>{ __( "Time's up!", 'goodblocks' ) }</p>
			</div>
		);
	}

	const units = [
		{
			value: timeLeft.days,
			label: __( 'Days', 'goodblocks' ),
			key: 'days',
		},
		{
			value: timeLeft.hours,
			label: __( 'Hours', 'goodblocks' ),
			key: 'hours',
		},
		{
			value: timeLeft.minutes,
			label: __( 'Minutes', 'goodblocks' ),
			key: 'minutes',
		},
	];

	if ( showSeconds ) {
		units.push( {
			value: timeLeft.seconds,
			label: __( 'Seconds', 'goodblocks' ),
			key: 'seconds',
		} );
	}

	return (
		<div className={ `countdown-display countdown-align-${ alignment }` }>
			{ units.map( ( unit, index ) => (
				<div key={ index } className="countdown-unit">
					<div
						className={ `countdown-number ${
							animatingUnits[ unit.key ] ? 'spinning' : ''
						}` }
					>
						{ unit.value.toString().padStart( 2, '0' ) }
					</div>
					<div className="countdown-label">{ unit.label }</div>
				</div>
			) ) }
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { targetDate, showSeconds, alignment } = attributes;

	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const blockProps = useBlockProps();

	const formatDisplayDate = ( dateString ) => {
		if ( ! dateString ) {
			return __( 'No date selected', 'goodblocks' );
		}
		try {
			const date = new Date( dateString );
			return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
		} catch ( e ) {
			return __( 'Invalid date', 'goodblocks' );
		}
	};

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ calendar }
						label={ __( 'Pick Date', 'goodblocks' ) }
						onClick={ () =>
							setIsDatePickerOpen( ! isDatePickerOpen )
						}
						isPressed={ isDatePickerOpen }
					>
						{ __( 'Pick Date', 'goodblocks' ) }
					</ToolbarButton>
				</ToolbarGroup>
				{ isDatePickerOpen && (
					<Popover
						placement="bottom-start"
						onClose={ () => setIsDatePickerOpen( false ) }
					>
						<div style={ { padding: '16px', minWidth: '300px' } }>
							<h4
								style={ {
									margin: '0 0 12px 0',
									fontSize: '14px',
									fontWeight: '600',
								} }
							>
								{ __(
									'Select Target Date & Time',
									'goodblocks'
								) }
							</h4>
							<p
								style={ {
									margin: '0 0 12px 0',
									fontSize: '12px',
									color: '#666',
								} }
							>
								{ __( 'Current:', 'goodblocks' ) }{ ' ' }
								{ formatDisplayDate( targetDate ) }
							</p>
							<DateTimePicker
								currentDate={ targetDate }
								onChange={ ( newDate ) => {
									setAttributes( { targetDate: newDate } );
									setIsDatePickerOpen( false );
								} }
								is12Hour={ false }
							/>
						</div>
					</Popover>
				) }
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'goodblocks' ) }>
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
							{
								label: __( 'Right', 'goodblocks' ),
								value: 'right',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { alignment: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Seconds', 'goodblocks' ) }
						checked={ showSeconds }
						onChange={ ( value ) =>
							setAttributes( { showSeconds: value } )
						}
						help={ __(
							'Display seconds in addition to days, hours, and minutes',
							'goodblocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<CountdownDisplay
					targetDate={ targetDate }
					showSeconds={ showSeconds }
					alignment={ alignment }
				/>
			</div>
		</>
	);
}
