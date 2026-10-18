import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { label, mediaId, mediaUrl, mediaAlt } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Chapter visual', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Chapter label', 'goodblocks' ) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ mediaId }
							onSelect={ ( media ) =>
								setAttributes( {
									mediaId: media.id,
									mediaUrl: media.url,
									mediaAlt: media.alt || '',
								} )
							}
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ __( 'Choose image', 'goodblocks' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ mediaUrl && (
						<>
							<TextControl
								label={ __( 'Alternative text', 'goodblocks' ) }
								value={ mediaAlt }
								onChange={ ( value ) =>
									setAttributes( { mediaAlt: value } )
								}
							/>
							<Button
								variant="tertiary"
								onClick={ () =>
									setAttributes( {
										mediaId: 0,
										mediaUrl: '',
										mediaAlt: '',
									} )
								}
							>
								{ __( 'Remove image', 'goodblocks' ) }
							</Button>
						</>
					) }
				</PanelBody>
			</InspectorControls>
			<article { ...useBlockProps( { className: 'gb-story-step' } ) }>
				<div className="gb-story-step__visual">
					{ mediaUrl ? (
						<img src={ mediaUrl } alt={ mediaAlt } />
					) : (
						<>
							<span
								className="gb-story-step__orb"
								aria-hidden="true"
							/>
							<span
								className="gb-story-step__number"
								aria-hidden="true"
							>
								{ label }
							</span>
						</>
					) }
				</div>
				<div className="gb-story-step__copy">
					<InnerBlocks
						template={ [
							[
								'core/paragraph',
								{
									content: __(
										'THE NEXT CHAPTER',
										'goodblocks'
									),
								},
							],
							[
								'core/heading',
								{
									level: 3,
									content: __(
										'Make the story yours.',
										'goodblocks'
									),
								},
							],
							[
								'core/paragraph',
								{
									content: __(
										'Add your own words, images and ideas. Each chapter stays readable on every screen.',
										'goodblocks'
									),
								},
							],
						] }
					/>
				</div>
			</article>
		</>
	);
}
