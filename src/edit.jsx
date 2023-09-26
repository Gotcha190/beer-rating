/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
	const blockProps = useBlockProps();
	const posts = useSelect((select) => {
		return select('core').getEntityRecords('postType', 'post');
	}, []);

	return (
		<div {...blockProps}>
			{!posts && 'Loading'}
			{posts && posts.length === 0 && 'No Posts'}
			{posts && posts.length > 0 && (
				<a href={posts[0].link}>
					{posts[0].title.rendered}
				</a>
			)}
		</div>
	);
}
