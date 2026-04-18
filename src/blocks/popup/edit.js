import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	TextControl,
} from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { trigger, delay, scrollPercent, cookieName, cookieDays } = attributes;

	const blockProps = useBlockProps( { className: 'goodblocks-popup-editor' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'popup-content-preview' },
		{ renderAppender: 'inserter' }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Trigger', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Show popup when', 'goodblocks' ) }
						value={ trigger }
						options={ [
							{ label: __( 'After delay (seconds)', 'goodblocks' ), value: 'time' },
							{ label: __( 'After scroll (%)', 'goodblocks' ), value: 'scroll' },
							{ label: __( 'Exit intent (desktop)', 'goodblocks' ), value: 'exit' },
						] }
						onChange={ ( val ) => setAttributes( { trigger: val } ) }
					/>
					{ trigger === 'time' && (
						<RangeControl
							label={ __( 'Delay (seconds)', 'goodblocks' ) }
							value={ delay }
							min={ 0 }
							max={ 60 }
							onChange={ ( val ) => setAttributes( { delay: val } ) }
						/>
					) }
					{ trigger === 'scroll' && (
						<RangeControl
							label={ __( 'Scroll percentage', 'goodblocks' ) }
							value={ scrollPercent }
							min={ 5 }
							max={ 95 }
							onChange={ ( val ) => setAttributes( { scrollPercent: val } ) }
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Cookie', 'goodblocks' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Cookie name', 'goodblocks' ) }
						value={ cookieName }
						onChange={ ( val ) => setAttributes( { cookieName: val } ) }
						help={ __( 'Must be unique per popup on this site. E.g. gb_popup_1, gb_popup_2…', 'goodblocks' ) }
					/>
					<RangeControl
						label={ __( 'Hide for (days)', 'goodblocks' ) }
						value={ cookieDays }
						min={ 1 }
						max={ 365 }
						onChange={ ( val ) => setAttributes( { cookieDays: val } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="popup-editor-label">
					{ __( 'Popup', 'goodblocks' ) }
					{ trigger === 'time' && ` — ${ __( 'shows after', 'goodblocks' ) } ${ delay }s` }
					{ trigger === 'scroll' && ` — ${ __( 'shows at', 'goodblocks' ) } ${ scrollPercent }% ${ __( 'scroll', 'goodblocks' ) }` }
					{ trigger === 'exit' && ` — ${ __( 'exit intent', 'goodblocks' ) }` }
				</div>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
