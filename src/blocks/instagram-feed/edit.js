import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const {
		postsToShow,
		columns,
		gap,
		aspectRatio,
		showCaption,
		showMetadata,
		showProfileLink,
		profileLinkText,
		fallbackText,
	} = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Feed', 'goodblocks' ) }>
					<RangeControl
						label={ __( 'Number of posts', 'goodblocks' ) }
						value={ postsToShow }
						min={ 1 }
						max={ 24 }
						onChange={ ( value ) =>
							setAttributes( { postsToShow: value } )
						}
					/>
					<RangeControl
						label={ __( 'Columns', 'goodblocks' ) }
						value={ columns }
						min={ 1 }
						max={ 6 }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
					/>
					<RangeControl
						label={ __( 'Gap', 'goodblocks' ) }
						value={ gap }
						min={ 0 }
						max={ 48 }
						onChange={ ( value ) =>
							setAttributes( { gap: value } )
						}
					/>
					<SelectControl
						label={ __( 'Image format', 'goodblocks' ) }
						value={ aspectRatio }
						options={ [
							{
								label: __( 'Square', 'goodblocks' ),
								value: '1/1',
							},
							{
								label: __( 'Portrait', 'goodblocks' ),
								value: '4/5',
							},
							{
								label: __( 'Landscape', 'goodblocks' ),
								value: '4/3',
							},
							{
								label: __( 'Original', 'goodblocks' ),
								value: 'auto',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { aspectRatio: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show captions', 'goodblocks' ) }
						checked={ showCaption }
						onChange={ ( value ) =>
							setAttributes( { showCaption: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show account and date', 'goodblocks' ) }
						checked={ showMetadata }
						onChange={ ( value ) =>
							setAttributes( { showMetadata: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Footer', 'goodblocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show profile link', 'goodblocks' ) }
						checked={ showProfileLink }
						onChange={ ( value ) =>
							setAttributes( { showProfileLink: value } )
						}
					/>
					<TextControl
						label={ __( 'Profile link text', 'goodblocks' ) }
						value={ profileLinkText }
						onChange={ ( value ) =>
							setAttributes( { profileLinkText: value } )
						}
					/>
					<TextControl
						label={ __( 'Fallback text', 'goodblocks' ) }
						value={ fallbackText }
						onChange={ ( value ) =>
							setAttributes( { fallbackText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="goodblocks/instagram-feed"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
