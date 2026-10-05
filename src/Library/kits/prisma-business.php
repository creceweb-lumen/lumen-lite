<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

return array(
	'id'          => 'prisma-business',
	'title'       => __( 'Prisma Business Kit', 'creceweb-lumen-lite' ),
	'description' => __( 'Seven-page Business Kit for professional services, with a warm editorial system, service and case studies, process, testimonials, FAQ, and contact.', 'creceweb-lumen-lite' ),
	'use'         => __( 'Use Prisma for service businesses that need to explain capabilities, present project stories, and turn qualified questions into conversations. Complete pages recommend Lumen: Full width.', 'creceweb-lumen-lite' ),
	'preview_url' => CRECEWEB_LUMEN_LITE_URL . 'assets/images/library-previews/prisma-business-kit-v2.webp',
	'config_preset' => 'prisma-theme.json',
	'site_setup' => array(
		'pages' => array(
			array( 'key' => 'home', 'item' => 'page-home-business-prisma', 'title' => __( 'Home', 'creceweb-lumen-lite' ) ),
			array( 'key' => 'services', 'item' => 'page-business-services-prisma', 'title' => __( 'Services', 'creceweb-lumen-lite' ) ),
			array( 'key' => 'cases', 'item' => 'page-business-cases-prisma', 'title' => __( 'Cases', 'creceweb-lumen-lite' ) ),
			array( 'key' => 'about', 'item' => 'page-business-about-prisma', 'title' => __( 'About', 'creceweb-lumen-lite' ) ),
			array( 'key' => 'contact', 'item' => 'page-business-contact-prisma', 'title' => __( 'Contact', 'creceweb-lumen-lite' ) ),
			array( 'key' => 'service-detail', 'item' => 'page-business-service-detail-prisma', 'title' => __( 'End-to-end Project Management', 'creceweb-lumen-lite' ) ),
			array( 'key' => 'case-detail', 'item' => 'page-business-case-detail-prisma', 'title' => __( 'Altamar Corporate Center', 'creceweb-lumen-lite' ) ),
		),
		'front_page' => 'home',
		'menu_name' => __( 'Prisma Business', 'creceweb-lumen-lite' ),
		'menu_location' => 'primary',
		'navigation' => array(
			array( 'page' => 'home', 'indicator' => '01', 'indicator_devices' => array( 'mobile' ) ),
			array( 'page' => 'services', 'indicator' => '02', 'indicator_devices' => array( 'mobile' ) ),
			array( 'page' => 'cases', 'indicator' => '03', 'indicator_devices' => array( 'mobile' ) ),
			array( 'page' => 'about', 'indicator' => '04', 'indicator_devices' => array( 'mobile' ) ),
			array( 'page' => 'contact', 'indicator' => '05', 'indicator_devices' => array( 'mobile' ) ),
			array( 'label' => __( 'Let\'s talk', 'creceweb-lumen-lite' ), 'target_page' => 'contact', 'highlight' => true ),
		),
	),
	'items' => array(
		'page-home-business-prisma',
		'page-business-services-prisma',
		'page-business-cases-prisma',
		'page-business-about-prisma',
		'page-business-contact-prisma',
		'page-business-service-detail-prisma',
		'page-business-case-detail-prisma',
	),
	'keywords' => array( __( 'business', 'creceweb-lumen-lite' ), __( 'services', 'creceweb-lumen-lite' ), __( 'cases', 'creceweb-lumen-lite' ), __( 'Prisma', 'creceweb-lumen-lite' ) ),
);
