/**
 * Story Card Block — Save
 *
 * NEW PATTERN i goodblocks-kodbasen: dynamic block + InnerBlocks.
 *
 * Story-card är server-renderat (`render.php`), MEN har InnerBlocks. Save måste
 * returnera <InnerBlocks.Content /> så att inner blocks persistas till
 * post_content. render.php tar sedan emot dem som $content-parameter.
 *
 * Andra dynamic blocks (hero, section-header, kpi-grid) har `save: () => null`
 * eftersom de inte använder InnerBlocks. Story-card pionjärar mönstret för
 * goodblocks/story-card.
 */

import { InnerBlocks } from '@wordpress/block-editor';

const Save = () => <InnerBlocks.Content />;

export default Save;
