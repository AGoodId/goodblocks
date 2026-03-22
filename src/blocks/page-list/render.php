<?php
$parent = ! empty( $attributes['parentPostId'] ) ? $attributes['parentPostId'] : get_the_ID();

$highest_parent = $parent;
while ( $highest_parent ) {
	$parent_id = wp_get_post_parent_id( $highest_parent );
	if ( ! $parent_id ) {
		break;
	}
	$highest_parent = $parent_id;
}

$args = array(
	'title_li'    => '',
	'child_of'    => $highest_parent,
	'sort_column' => 'menu_order,post_title',
	'echo'        => false,
);

$pages = wp_list_pages( $args );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php if ( $attributes['showParent'] ) : ?>
		<h3 class="page-list-title"><a
				href="<?php the_permalink( $highest_parent ); ?>"><?php echo get_the_title( $highest_parent ); ?></a></h3>
	<?php endif; ?>
	<ul class="page-list">
		<?php echo $pages; ?>
	</ul>
</div>
