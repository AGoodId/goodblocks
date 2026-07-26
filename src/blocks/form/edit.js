import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	CheckboxControl,
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import './editor.scss';

const fieldTypes = [
	{ label: __( 'Text', 'goodblocks' ), value: 'text' },
	{ label: __( 'Email', 'goodblocks' ), value: 'email' },
	{ label: __( 'Phone', 'goodblocks' ), value: 'tel' },
	{ label: __( 'Message', 'goodblocks' ), value: 'textarea' },
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		title,
		description,
		recipientEmail,
		storeSubmissions,
		emailSubject,
		submitLabel,
		successMessage,
		fields,
	} = attributes;
	useEffect( () => {
		if ( ! attributes.formId ) {
			setAttributes( { formId: clientId } );
		}
	}, [ attributes.formId, clientId, setAttributes ] );
	const updateField = ( index, change ) =>
		setAttributes( {
			fields: fields.map( ( field, i ) =>
				i === index ? { ...field, ...change } : field
			),
		} );
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Delivery', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Recipient email', 'goodblocks' ) }
						help={ __(
							'Leave empty to use the site administrator email.',
							'goodblocks'
						) }
						type="email"
						value={ recipientEmail }
						onChange={ ( value ) =>
							setAttributes( { recipientEmail: value } )
						}
					/>
					<CheckboxControl
						label={ __(
							'Save submissions in WordPress',
							'goodblocks'
						) }
						help={ __(
							'For confidential reports, turn this off so only the recipient email receives the contents.',
							'goodblocks'
						) }
						checked={ storeSubmissions }
						onChange={ ( value ) =>
							setAttributes( { storeSubmissions: value } )
						}
					/>
					<TextControl
						label={ __( 'Email subject', 'goodblocks' ) }
						value={ emailSubject }
						onChange={ ( value ) =>
							setAttributes( { emailSubject: value } )
						}
					/>
					<TextControl
						label={ __( 'Button label', 'goodblocks' ) }
						value={ submitLabel }
						onChange={ ( value ) =>
							setAttributes( { submitLabel: value } )
						}
					/>
					<TextControl
						label={ __( 'Success message', 'goodblocks' ) }
						value={ successMessage }
						onChange={ ( value ) =>
							setAttributes( { successMessage: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Fields', 'goodblocks' ) }
					initialOpen={ false }
				>
					{ fields.map( ( field, index ) => (
						<div
							className="goodblocks-form-field-settings"
							key={ `${ field.name }-${ index }` }
						>
							<TextControl
								label={ __( 'Label', 'goodblocks' ) }
								value={ field.label }
								onChange={ ( value ) =>
									updateField( index, { label: value } )
								}
							/>
							<TextControl
								label={ __( 'Field key', 'goodblocks' ) }
								help={ __(
									'Letters, numbers, and underscores only.',
									'goodblocks'
								) }
								value={ field.name }
								onChange={ ( value ) =>
									updateField( index, {
										name: value
											.replace( /[^a-z0-9_]/gi, '' )
											.toLowerCase(),
									} )
								}
							/>
							<SelectControl
								label={ __( 'Type', 'goodblocks' ) }
								value={ field.type }
								options={ fieldTypes }
								onChange={ ( value ) =>
									updateField( index, { type: value } )
								}
							/>
							<CheckboxControl
								label={ __( 'Required', 'goodblocks' ) }
								checked={ field.required }
								onChange={ ( value ) =>
									updateField( index, { required: value } )
								}
							/>
							<Button
								isDestructive
								variant="tertiary"
								disabled={ fields.length === 1 }
								onClick={ () =>
									setAttributes( {
										fields: fields.filter(
											( _, i ) => i !== index
										),
									} )
								}
							>
								{ __( 'Remove field', 'goodblocks' ) }
							</Button>
						</div>
					) ) }
					<Button
						variant="secondary"
						onClick={ () =>
							setAttributes( {
								fields: [
									...fields,
									{
										name: `field_${ fields.length + 1 }`,
										label: __( 'New field', 'goodblocks' ),
										type: 'text',
										required: false,
									},
								],
							} )
						}
					>
						{ __( 'Add field', 'goodblocks' ) }
					</Button>
				</PanelBody>
			</InspectorControls>
			<RichText
				tagName="h2"
				value={ title }
				onChange={ ( value ) => setAttributes( { title: value } ) }
				placeholder={ __( 'Form title…', 'goodblocks' ) }
			/>
			<RichText
				tagName="p"
				value={ description }
				onChange={ ( value ) =>
					setAttributes( { description: value } )
				}
				placeholder={ __( 'Description…', 'goodblocks' ) }
			/>
			<form
				className="goodblocks-form-preview"
				onSubmit={ ( event ) => event.preventDefault() }
			>
				{ fields.map( ( field, index ) => {
					const previewId = `goodblocks-form-preview-${ clientId }-${ index }`;
					return (
						<label
							htmlFor={ previewId }
							key={ `${ field.name }-${ index }` }
						>
							{ field.label }
							{ field.required && ' *' }
							{ field.type === 'textarea' ? (
								<textarea disabled id={ previewId } />
							) : (
								<input
									disabled
									id={ previewId }
									type={ field.type }
								/>
							) }
						</label>
					);
				} ) }
				<button type="submit" disabled>
					{ submitLabel }
				</button>
			</form>
		</div>
	);
}
