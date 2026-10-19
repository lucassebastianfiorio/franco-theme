<?php
/**
 * WordPress Customizer Settings
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function franco_customize_register( $wp_customize ) {
	// Section: Franco Deportes Commercial Options
	$wp_customize->add_section( 'franco_commercial_options', array(
		'title'       => __( 'Opciones Franco Deportes', 'franco-theme' ),
		'priority'    => 30,
		'description' => __( 'Configuración comercial, barra superior y redes sociales.', 'franco-theme' ),
	) );

	// Top Bar Marquee Text 1
	$wp_customize->add_setting( 'franco_topbar_text_1', array(
		'default'           => 'Envíos a todo el país | GRATIS en compras > $80.000',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'franco_topbar_text_1', array(
		'label'   => __( 'Texto Barra Superior 1', 'franco-theme' ),
		'section' => 'franco_commercial_options',
		'type'    => 'text',
	) );

	// Top Bar Marquee Text 2
	$wp_customize->add_setting( 'franco_topbar_text_2', array(
		'default'           => '3 y 6 Cuotas Sin Interés',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'franco_topbar_text_2', array(
		'label'   => __( 'Texto Barra Superior 2', 'franco-theme' ),
		'section' => 'franco_commercial_options',
		'type'    => 'text',
	) );

	// Top Bar Marquee Text 3
	$wp_customize->add_setting( 'franco_topbar_text_3', array(
		'default'           => '15% OFF abonando por Transferencia / Efectivo',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'franco_topbar_text_3', array(
		'label'   => __( 'Texto Barra Superior 3', 'franco-theme' ),
		'section' => 'franco_commercial_options',
		'type'    => 'text',
	) );

	// WhatsApp Contact Number
	$wp_customize->add_setting( 'franco_whatsapp_number', array(
		'default'           => '5491112345678',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'franco_whatsapp_number', array(
		'label'       => __( 'Número de WhatsApp (con código de país)', 'franco-theme' ),
		'description' => __( 'Ejemplo: 5491112345678', 'franco-theme' ),
		'section'     => 'franco_commercial_options',
		'type'        => 'text',
	) );

	// Bank CBU / Alias Info
	$wp_customize->add_setting( 'franco_bank_cbu', array(
		'default'           => '0000003100000000001234',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'franco_bank_cbu', array(
		'label'   => __( 'CBU para Transferencias', 'franco-theme' ),
		'section' => 'franco_commercial_options',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'franco_bank_alias', array(
		'default'           => 'FRANCO.DEPORTES.MP',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'franco_bank_alias', array(
		'label'   => __( 'Alias para Transferencias', 'franco-theme' ),
		'section' => 'franco_commercial_options',
		'type'    => 'text',
	) );

	// Instagram URL
	$wp_customize->add_setting( 'franco_instagram_url', array(
		'default'           => 'https://instagram.com',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'franco_instagram_url', array(
		'label'   => __( 'Enlace de Instagram', 'franco-theme' ),
		'section' => 'franco_commercial_options',
		'type'    => 'url',
	) );
}
add_action( 'customize_register', 'franco_customize_register' );
