<?php

require_once __DIR__ . '/base.php';

/**
 * Test autoload after the switch_theme.
 *
 * @package WordPress
 * @subpackage Theme
 *
 * @group themes
 */
class Tests_Autoload_Theme_Mods extends WP_Theme_UnitTestCase {

	/**
	 * Tests that theme mods should not autoloaded after switch_theme.
	 *
	 * @ticket 39537
	 */
	public function test_that_on_switch_theme_previous_theme_mods_should_not_be_autoload() {
		global $wpdb;

		$current_theme_stylesheet = get_stylesheet();

		// Set a theme mod for the current theme.
		$new_theme_stylesheet = 'block-theme';
		set_theme_mod( 'foo-bar-option', 'a-value' );

		switch_theme( $new_theme_stylesheet );

		$this->assertSame( 'no', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", "theme_mods_$current_theme_stylesheet" ) ), 'Theme mods autoload value not set to no in database' );
		$this->assertSame( 'yes', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", "theme_mods_$new_theme_stylesheet" ) ), 'Theme mods autoload value not set to yes in database' );

		// Make sure that autoloaded options are cached properly.
		$autoloaded_options = wp_cache_get( 'alloptions', 'options' );
		$this->assertArrayHasKey( "theme_mods_$new_theme_stylesheet", $autoloaded_options, "Option theme_mods_$current_theme_stylesheet unexpectedly deleted from alloptions cache" );
		$this->assertArrayNotHasKey( "theme_mods_$current_theme_stylesheet", $autoloaded_options, "Option theme_mods_$new_theme_stylesheet not deleted from alloptions cache" );

		switch_theme( $current_theme_stylesheet );

		$this->assertSame( 'yes', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", "theme_mods_$current_theme_stylesheet" ) ), 'Theme mods autoload value not set to yes in database' );
		$this->assertSame( 'no', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", "theme_mods_$new_theme_stylesheet" ) ), 'Theme mods autoload value not set to no in database' );

		// Make sure that autoloaded options are cached properly.
		$autoloaded_options = wp_cache_get( 'alloptions', 'options' );
		$this->assertArrayNotHasKey( "theme_mods_$new_theme_stylesheet", $autoloaded_options, "Option theme_mods_$new_theme_stylesheet not deleted from alloptions cache" );
		$this->assertArrayHasKey( "theme_mods_$current_theme_stylesheet", $autoloaded_options, "Option theme_mods_$current_theme_stylesheet unexpectedly deleted from alloptions cache" );

		// And that we haven't lost the mods.
		$this->assertSame( 'a-value', get_theme_mod( 'foo-bar-option' ) );
	}
}
