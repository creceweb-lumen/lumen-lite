<?php
/**
 * Education areas signal · Voltio.
 *
 * @package CreceWebLumenLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cw_lumen_lite_markup = <<<'HTML'
<!-- wp:group {"layout":{"type":"default"},"className":"cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section--compact cw-kit-signal","align":"full"} -->
<div class="wp-block-group alignfull cw-lumen-lite-pattern cw-lumen-kit-component cw-lumen-kit-profile--voltio cw-kit-section--compact cw-kit-signal">
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-shell"} -->
<div class="wp-block-group cw-kit-shell">
<!-- wp:paragraph {"className":"cw-kit-copy cw-kit-center"} -->
<p class="cw-kit-copy cw-kit-center">{{cw_lumen_text_1}}</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"},"className":"cw-kit-signal__items"} -->
<div class="wp-block-group cw-kit-signal__items">
<!-- wp:paragraph {"className":"cw-kit-signal__item"} -->
<p class="cw-kit-signal__item">{{cw_lumen_text_2}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-signal__item"} -->
<p class="cw-kit-signal__item">{{cw_lumen_text_3}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-signal__item"} -->
<p class="cw-kit-signal__item">{{cw_lumen_text_4}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-signal__item"} -->
<p class="cw-kit-signal__item">{{cw_lumen_text_5}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-signal__item"} -->
<p class="cw-kit-signal__item">{{cw_lumen_text_6}}</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"cw-kit-signal__item"} -->
<p class="cw-kit-signal__item">{{cw_lumen_text_7}}</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;
$cw_lumen_lite_markup = strtr(
	$cw_lumen_lite_markup,
	array(
		'{{cw_lumen_text_1}}' => esc_html__( 'Skills for people working in', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_2}}' => esc_html__( 'Product', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_3}}' => esc_html__( 'Design', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_4}}' => esc_html__( 'Content', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_5}}' => esc_html__( 'Development', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_6}}' => esc_html__( 'Marketing', 'creceweb-lumen-lite' ),
		'{{cw_lumen_text_7}}' => esc_html__( 'Operations', 'creceweb-lumen-lite' ),
	)
);
return array(
	'slug' => 'education-skills-signal-voltio',
	'family' => 'content',
	'title' => __( 'Education areas signal · Voltio', 'creceweb-lumen-lite' ),
	'description' => __( 'Compact editable strip for disciplines covered by an education offer.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'skills', 'creceweb-lumen-lite' ), __( 'topics', 'creceweb-lumen-lite' ), __( 'education', 'creceweb-lumen-lite' ) ),
	'preview' => 'education-skills-signal-voltio.webp',
	'content' => $cw_lumen_lite_markup,
);
