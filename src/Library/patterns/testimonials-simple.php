<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$cw_lumen_lite_testimonial_card = static function ( string $quote, string $cite ): string {
	return implode( "\n", array(
		'<!-- wp:group {"style":{"color":{"background":"#ffffff"},"border":{"left":{"color":"#10b981","style":"solid","width":"4px"},"radius":"18px"},"spacing":{"padding":{"top":"1.5rem","right":"1.5rem","bottom":"1.5rem","left":"1.5rem"}}},"className":"cw-lumen-section-card cw-lumen-lite-testimonial cw-lumen-lite-testimonial--native-border","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-section-card cw-lumen-lite-testimonial cw-lumen-lite-testimonial--native-border has-background has-border-color" style="border-left-color:#10b981;border-left-style:solid;border-left-width:4px;border-radius:18px;background-color:#ffffff;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">',
		'<!-- wp:quote {"className":"cw-lumen-lite-testimonial__quote"} -->',
		'<blockquote class="wp-block-quote cw-lumen-lite-testimonial__quote"><p>' . esc_html( $quote ) . '</p><cite>' . esc_html( $cite ) . '</cite></blockquote>',
		'<!-- /wp:quote -->',
		'</div>',
		'<!-- /wp:group -->',
	) );
};

return array(
	'slug' => 'testimonials-simple', 'family' => 'credibility',
	'title' => __( 'Simple testimonials', 'creceweb-lumen-lite' ),
	'description' => __( 'Three concise testimonials with names and roles.', 'creceweb-lumen-lite' ),
	'keywords' => array( __( 'testimonials', 'creceweb-lumen-lite' ), __( 'quotes', 'creceweb-lumen-lite' ), __( 'trust', 'creceweb-lumen-lite' ) ),
	'content' => implode( "\n", array(
		'<!-- wp:group {"align":"full","className":"cw-lumen-section cw-lumen-lite-pattern cw-lumen-lite-pattern--testimonials","layout":{"type":"default"}} -->',
		'<div class="wp-block-group alignfull cw-lumen-section cw-lumen-lite-pattern cw-lumen-lite-pattern--testimonials">',
		'<!-- wp:group {"className":"cw-lumen-section__inner cw-lumen-lite-pattern__inner","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-section__inner cw-lumen-lite-pattern__inner">',
		'<!-- wp:group {"className":"cw-lumen-section-heading cw-lumen-lite-section-heading cw-lumen-lite-testimonials__heading","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-section-heading cw-lumen-lite-section-heading cw-lumen-lite-testimonials__heading">',
		'<!-- wp:paragraph {"align":"center","className":"cw-lumen-lite-pattern__eyebrow cw-lumen-lite-testimonials__eyebrow"} -->',
		'<p class="has-text-align-center cw-lumen-lite-pattern__eyebrow cw-lumen-lite-testimonials__eyebrow">' . esc_html__( 'What clients say', 'creceweb-lumen-lite' ) . '</p>',
		'<!-- /wp:paragraph -->',
		'<!-- wp:heading {"textAlign":"center","className":"cw-lumen-lite-testimonials__title"} -->',
		'<h2 class="wp-block-heading has-text-align-center cw-lumen-lite-testimonials__title">' . esc_html__( 'Clear work earns confident recommendations.', 'creceweb-lumen-lite' ) . '</h2>',
		'<!-- /wp:heading -->',
		'</div>',
		'<!-- /wp:group -->',
		'<!-- wp:group {"className":"cw-lumen-section-grid cw-lumen-section-grid--equal-height cw-lumen-lite-card-grid cw-lumen-lite-testimonials__grid","layout":{"type":"default"}} -->',
		'<div class="wp-block-group cw-lumen-section-grid cw-lumen-section-grid--equal-height cw-lumen-lite-card-grid cw-lumen-lite-testimonials__grid">',
		$cw_lumen_lite_testimonial_card(
			__( 'The process was easy to understand, and the final website feels completely aligned with our work.', 'creceweb-lumen-lite' ),
			__( 'Marina · Studio Director', 'creceweb-lumen-lite' )
		),
		$cw_lumen_lite_testimonial_card(
			__( 'We finally have a page that explains the service without overwhelming potential clients.', 'creceweb-lumen-lite' ),
			__( 'Lucas · Consultant', 'creceweb-lumen-lite' )
		),
		$cw_lumen_lite_testimonial_card(
			__( 'Editing the content is simple, so the team can keep the website up to date without assistance.', 'creceweb-lumen-lite' ),
			__( 'Carla · Operations Manager', 'creceweb-lumen-lite' )
		),
		'</div>',
		'<!-- /wp:group -->',
		'</div>',
		'<!-- /wp:group -->',
		'</div>',
		'<!-- /wp:group -->',
	) ),
);
