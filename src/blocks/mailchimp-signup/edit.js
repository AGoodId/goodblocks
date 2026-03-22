import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { title, text, listLink } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'goodblocks' ) }>
					<TextControl
						label={ __(
							'Mailchimp Form Action URL',
							'goodblocks'
						) }
						value={ listLink }
						onChange={ ( value ) =>
							setAttributes( { listLink: value } )
						}
						placeholder="https://example.com/list-link"
					/>
				</PanelBody>
			</InspectorControls>
			<div className="content-wrapper">
				<RichText
					tagName="h2"
					value={ title }
					onChange={ ( value ) => setAttributes( { title: value } ) }
					placeholder={ __( 'Title…', 'goodblocks' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					placeholder={ __( 'Description…', 'goodblocks' ) }
					allowedFormats={ [] }
				/>
				<form
					className="mailchimp-signup-form"
					onSubmit={ ( e ) => e.preventDefault() }
				>
					<input
						type="email"
						name="EMAIL"
						placeholder={ __( 'Email', 'goodblocks' ) }
						required
						disabled
					/>
					<button
						type="submit"
						className="wp-block-button__link"
						disabled
					>
						{ __( 'Subscribe', 'goodblocks' ) }
					</button>
				</form>
			</div>
		</div>
	);
}
