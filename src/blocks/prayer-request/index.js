import './editor.css';
import './style.css';

import { registerBlockType } from '@wordpress/blocks';
import { RichText } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';

registerBlockType( 'intercessor/prayer-request', {
	title: 'Prayer Request',
	category: 'widgets',
	icon: 'welcome-write-blog',
	description: 'A block to submit/display a single prayer request (title + details).',
	attributes: {
		title: {
			type: 'string',
			source: 'html',
			selector: 'h3',
		},
		content: {
			type: 'string',
			source: 'html',
			selector: 'p',
		},
	},

	edit: ( props ) => {
		const { attributes: { title, content }, setAttributes, className } = props;

		return (
			<Fragment>
				<div className={ `${ className } intercessor-prayer-request-editor` }>
					<RichText
						tagName="h3"
						placeholder="Prayer title…"
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
					/>
					<RichText
						tagName="p"
						placeholder="Prayer details…"
						value={ content }
						onChange={ ( value ) => setAttributes( { content: value } ) }
					/>
				</div>
			</Fragment>
		);
	},

	save: ( props ) => {
		const { attributes: { title, content }, className } = props;
		return (
			<div className={ `${ className } intercessor-prayer-request` }>
				<RichText.Content tagName="h3" value={ title } />
				<RichText.Content tagName="p" value={ content } />
			</div>
		);
	},
} );
