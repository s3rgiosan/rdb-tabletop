<?php
/**
 * "Powered by BoardGameGeek" block.
 *
 * @package S3S\WP\RdbTabletop
 */

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'has-text-align-right has-small-font-size',
	]
);

$image_url = S3S_RDB_TABLETOP_URL . 'assets/powered-by.webp';
$image_alt = __( 'Powered by BoardGameGeek', 'rdb-tabletop' );

?>

<p <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<a href="https://boardgamegeek.com" target="_blank" rel="noreferrer noopener">
		<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" width="120" height="auto" style="vertical-align:middle" />
	</a>
</p>
