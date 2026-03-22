import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, RadioControl, Button } from '@wordpress/components';

const Edit = ({ attributes, setAttributes }) => {
	const { question, answers, correctIndex, backgroundMedia } = attributes;

	const updateQuestion = (newQuestion) => {
		setAttributes({ question: newQuestion });
	};

	const updateAnswer = (index, newAnswer) => {
		const updatedAnswers = [...answers];
		updatedAnswers[index] = newAnswer;
		setAttributes({ answers: updatedAnswers });
	};

	const updateCorrectIndex = (newIndex) => {
		setAttributes({ correctIndex: parseInt(newIndex, 10) });
	};

	const ensureThreeAnswers = () => {
		const updatedAnswers = [...answers];
		while (updatedAnswers.length < 3) {
			updatedAnswers.push('');
		}
		setAttributes({ answers: updatedAnswers });
	};

	if (!answers || answers.length < 3) {
		ensureThreeAnswers();
	}

	const backgroundStyle = {};
	if (backgroundMedia?.type === 'image' && backgroundMedia.url) {
		backgroundStyle.backgroundImage = `url(${backgroundMedia.url})`;
	}

	const blockProps = useBlockProps({
		style: backgroundStyle,
	});

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Settings', 'goodblocks')} initialOpen={true}>
					<RadioControl
						label={__('Correct answer', 'goodblocks')}
						help={__('Select correct answer', 'goodblocks')}
						selected={correctIndex}
						options={[
							{ label: answers[0], value: 0 },
							{ label: answers[1], value: 1 },
							{ label: answers[2], value: 2 },
						]}
						onChange={updateCorrectIndex}
					/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={(media) => setAttributes({ backgroundMedia: media })}
								allowedTypes={['image']}
								render={({ open }) => (
									<div>
										{!!backgroundMedia && backgroundMedia.type === 'image' && (
											<>
												<img src={backgroundMedia.url} alt={backgroundMedia.alt} />
												<Button
													onClick={() => setAttributes({ backgroundMedia: null })}
													isDestructive
													isSecondary
												>
													{__('Remove image', 'goodblocks')}
												</Button>
											</>
										)}
										<Button onClick={open} isSecondary>
											{!!backgroundMedia ? __('Change background image', 'goodblocks') : __('Add background image', 'goodblocks')}
										</Button>
									</div>
								)}
							/>
						</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<div class="question-quiz-container">
				<TextControl
					label={__('Question', 'goodblocks')}
					value={question}
					onChange={updateQuestion}
					placeholder={__('Enter your question here', 'goodblocks')}
				/>

				{answers.map((answer, index) => (
					<TextControl
						key={index}
						label={sprintf(__('Answer %d', 'goodblocks'), index + 1)}
						value={answer}
						onChange={(newAnswer) => updateAnswer(index, newAnswer)}
						placeholder={sprintf(__('Answer %d here...', 'goodblocks'), index + 1)}
					/>
				))}
			</div>
		</div>
	);
};

export default Edit;
