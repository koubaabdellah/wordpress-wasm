<?php
 header( 'Content-Type: ' . feed_content_type( 'atom' ) . '; charset=' . get_option( 'blog_charset' ), true ); echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '" ?' . '>'; do_action( 'rss_tag_pre', 'atom-comments' ); ?>
<feed
	xmlns="http://www.w3.org/2005/Atom"
	xml:lang="<?php bloginfo_rss( 'language' ); ?>"
	xmlns:thr="http://purl.org/syndication/thread/1.0"
	<?php
 do_action( 'atom_ns' ); do_action( 'atom_comments_ns' ); ?>
>
	<title type="text">
	<?php
 if ( is_singular() ) { printf( ent2ncr( __( 'Comments on %s' ) ), get_the_title_rss() ); } elseif ( is_search() ) { printf( ent2ncr( __( 'Comments for %1$s searching on %2$s' ) ), get_bloginfo_rss( 'name' ), get_search_query() ); } else { printf( ent2ncr( __( 'Comments for %s' ) ), get_wp_title_rss() ); } ?>
	</title>
	<subtitle type="text"><?php bloginfo_rss( 'description' ); ?></subtitle>

	<updated><?php echo get_feed_build_date( 'Y-m-d\TH:i:s\Z' ); ?></updated>

<?php if ( is_singular() ) : ?>
	<link rel="alternate" type="<?php bloginfo_rss( 'html_type' ); ?>" href="<?php comments_link_feed(); ?>" />
	<link rel="self" type="application/atom+xml" href="<?php echo esc_url( get_post_comments_feed_link( '', 'atom' ) ); ?>" />
	<id><?php echo esc_url( get_post_comments_feed_link( '', 'atom' ) ); ?></id>
<?php elseif ( is_search() ) : ?>
	<link rel="alternate" type="<?php bloginfo_rss( 'html_type' ); ?>" href="<?php echo home_url() . '?s=' . get_search_query(); ?>" />
	<link rel="self" type="application/atom+xml" href="<?php echo get_search_comments_feed_link( '', 'atom' ); ?>" />
	<id><?php echo get_search_comments_feed_link( '', 'atom' ); ?></id>
<?php else : ?>
	<link rel="alternate" type="<?php bloginfo_rss( 'html_type' ); ?>" href="<?php bloginfo_rss( 'url' ); ?>" />
	<link rel="self" type="application/atom+xml" href="<?php bloginfo_rss( 'comments_atom_url' ); ?>" />
	<id><?php bloginfo_rss( 'comments_atom_url' ); ?></id>
<?php endif; ?>
<?php
 do_action( 'comments_atom_head' ); ?>
<?php
while ( have_comments() ) : the_comment(); $comment_post = get_post( $comment->comment_post_ID ); $GLOBALS['post'] = $comment_post; ?>
	<entry>
		<title>
		<?php
 if ( ! is_singular() ) { $title = get_the_title( $comment_post->ID ); $title = apply_filters( 'the_title_rss', $title ); printf( ent2ncr( __( 'Comment on %1$s by %2$s' ) ), $title, get_comment_author_rss() ); } else { printf( ent2ncr( __( 'By: %s' ) ), get_comment_author_rss() ); } ?>
		</title>
		<link rel="alternate" href="<?php comment_link(); ?>" type="<?php bloginfo_rss( 'html_type' ); ?>" />

		<author>
			<name><?php comment_author_rss(); ?></name>
			<?php
 if ( get_comment_author_url() ) { echo '<uri>' . get_comment_author_url() . '</uri>';} ?>

		</author>

		<id><?php comment_guid(); ?></id>
		<updated><?php echo mysql2date( 'Y-m-d\TH:i:s\Z', get_comment_time( 'Y-m-d H:i:s', true, false ), false ); ?></updated>
		<published><?php echo mysql2date( 'Y-m-d\TH:i:s\Z', get_comment_time( 'Y-m-d H:i:s', true, false ), false ); ?></published>

		<?php if ( post_password_required( $comment_post ) ) : ?>
			<content type="html" xml:base="<?php comment_link(); ?>"><![CDATA[<?php echo get_the_password_form(); ?>]]></content>
		<?php else : ?>
			<content type="html" xml:base="<?php comment_link(); ?>"><![CDATA[<?php comment_text(); ?>]]></content>
		<?php endif; ?>

		<?php
 if ( 0 == $comment->comment_parent ) : ?>
			<thr:in-reply-to ref="<?php the_guid(); ?>" href="<?php the_permalink_rss(); ?>" type="<?php bloginfo_rss( 'html_type' ); ?>" />
			<?php
 else : $parent_comment = get_comment( $comment->comment_parent ); ?>
			<thr:in-reply-to ref="<?php comment_guid( $parent_comment ); ?>" href="<?php echo get_comment_link( $parent_comment ); ?>" type="<?php bloginfo_rss( 'html_type' ); ?>" />
			<?php
 endif; do_action( 'comment_atom_entry', $comment->comment_ID, $comment_post->ID ); ?>
	</entry>
	<?php
endwhile; ?>
</feed>
