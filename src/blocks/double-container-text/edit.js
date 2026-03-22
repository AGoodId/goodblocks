import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    MediaUpload,
    MediaUploadCheck,
    InspectorControls
} from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, SelectControl, RangeControl, ColorPalette } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

const Edit = ({ attributes, setAttributes }) => {
    const {
        leftTitle,
        leftText,
        leftMedia,
        leftPosition = 'top-left',
        leftColor = '#fff',
        leftOverlayColor = '#000',
        leftDimRatio = 0,
        leftLink,
        rightTitle,
        rightText,
        rightMedia,
        rightPosition = 'top-left',
        rightColor = '#fff',
        rightDimRatio = 0,
        rightOverlayColor = '#000',
        rightLink
    } = attributes;

    const positionOptions = [
        { label: __('Top Left', 'goodblocks'), value: 'top-left' },
        { label: __('Top Right', 'goodblocks'), value: 'top-right' },
        { label: __('Bottom Left', 'goodblocks'), value: 'bottom-left' },
        { label: __('Bottom Right', 'goodblocks'), value: 'bottom-right' },
    ];

    const colorOptions = [
        { label: __('White', 'goodblocks'), value: '#fff' },
        { label: __('Black', 'goodblocks'), value: '#000' },
    ];

    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Left Container Settings', 'goodblocks')} initialOpen={true}>
                    <TextControl
                        label={__('Left Title', 'goodblocks')}
                        value={leftTitle}
                        onChange={(value) => setAttributes({ leftTitle: value })}
                    />
                    <TextControl
                        label={__('Left Text', 'goodblocks')}
                        value={leftText}
                        onChange={(value) => setAttributes({ leftText: value })}
                    />
                    <TextControl
                        label={__('Left Link (URL)', 'goodblocks')}
                        value={leftLink}
                        onChange={(value) => setAttributes({ leftLink: value })}
                        placeholder="https://example.com"
                    />
                    <SelectControl
                        label={__('Text Position', 'goodblocks')}
                        value={leftPosition}
                        options={positionOptions}
                        onChange={(value) => setAttributes({ leftPosition: value })}
                    />
                    <SelectControl
                        label={__('Text Color', 'goodblocks')}
                        value={leftColor}
                        options={colorOptions}
                        onChange={(value) => setAttributes({ leftColor: value })}
                    />
                    <RangeControl
                        label={__('Overlay opacity', 'goodblocks')}
                        value={leftDimRatio}
                        onChange={(newDimRatio) =>
                            setAttributes({ leftDimRatio: newDimRatio })
                        }
                        min={0}
                        max={100}
                        step={10}
                        required
                    />
                    <ColorPalette
                        value={leftOverlayColor}
                        onChange={(newColor) =>
                            setAttributes({ leftOverlayColor: newColor })
                        }
                    />
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => setAttributes({ leftMedia: media?.id || 0 })}
                            allowedTypes={['image', 'video']}
                            value={leftMedia}
                            render={({ open }) => (
                                <Button onClick={open} variant='secondary'>
                                    {leftMedia ? __('Replace media', 'goodblocks') : __('Select image or video', 'goodblocks')}
                                </Button>
                            )}
                        />
                    </MediaUploadCheck>
                </PanelBody>
                <PanelBody title={__('Right Container Settings', 'goodblocks')} initialOpen={false}>
                    <TextControl
                        label={__('Right Title', 'goodblocks')}
                        value={rightTitle}
                        onChange={(value) => setAttributes({ rightTitle: value })}
                    />
                    <TextControl
                        label={__('Right Text', 'goodblocks')}
                        value={rightText}
                        onChange={(value) => setAttributes({ rightText: value })}
                    />
                    <TextControl
                        label={__('Right Link (URL)', 'goodblocks')}
                        value={rightLink}
                        onChange={(value) => setAttributes({ rightLink: value })}
                        placeholder="https://example.com"
                    />
                    <SelectControl
                        label={__('Text Position', 'goodblocks')}
                        value={rightPosition}
                        options={positionOptions}
                        onChange={(value) => setAttributes({ rightPosition: value })}
                    />
                    <SelectControl
                        label={__('Text Color', 'goodblocks')}
                        value={rightColor}
                        options={colorOptions}
                        onChange={(value) => setAttributes({ rightColor: value })}
                    />
                    <RangeControl
                        label={__('Overlay opacity', 'goodblocks')}
                        value={rightDimRatio}
                        onChange={(newDimRatio) =>
                            setAttributes({ rightDimRatio: newDimRatio })
                        }
                        min={0}
                        max={100}
                        step={10}
                        required
                    />
                    <ColorPalette
                        value={rightOverlayColor}
                        onChange={(newColor) =>
                            setAttributes({ rightOverlayColor: newColor })
                        }
                    />
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => setAttributes({ rightMedia: media?.id || 0 })}
                            allowedTypes={['image', 'video']}
                            value={rightMedia}
                            render={({ open }) => (
                                <Button onClick={open} variant='secondary'>
                                    {rightMedia ? __('Replace media', 'goodblocks') : __('Select image or video', 'goodblocks')}
                                </Button>
                            )}
                        />
                    </MediaUploadCheck>
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block="goodblocks/double-container-text"
                attributes={attributes}
            />
        </div>
    );
}

export default Edit;
