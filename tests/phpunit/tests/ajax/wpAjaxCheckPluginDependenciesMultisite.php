<?php

/**
 * Admin Ajax functions to be tested.
 */
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

/**
 * Tests the activation URL returned by the `check_plugin_dependencies` Ajax action on multisite.
 *
 * @package WordPress
 * @subpackage UnitTests
 *
 * @group ajax
 * @group plugins
 * @group ms-required
 *
 * @covers WP_Plugin_Dependencies::check_plugin_dependencies_during_ajax
 */
class Tests_Ajax_wpAjaxCheckPluginDependenciesMultisite extends WP_Ajax_UnitTestCase {

	/**
	 * The plugin file of the fixture plugin.
	 */
	const PLUGIN_FILE = 'dependent/dependent.php';

	/**
	 * A user with super admin privileges.
	 *
	 * @var int
	 */
	protected static $super_admin_id;

	/**
	 * The static properties of WP_Plugin_Dependencies and their default values.
	 *
	 * @var array
	 */
	protected static $static_properties = array(
		'plugins'                     => null,
		'plugin_dirnames'             => null,
		'dependencies'                => null,
		'dependency_slugs'            => null,
		'dependent_slugs'             => null,
		'dependency_api_data'         => null,
		'dependency_filepaths'        => null,
		'circular_dependencies_pairs' => null,
		'circular_dependencies_slugs' => null,
		'initialized'                 => false,
	);

	/**
	 * Creates the shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$super_admin_id = $factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( self::$super_admin_id );
	}

	/**
	 * Revokes super admin privileges from the shared fixture user.
	 */
	public static function wpTearDownAfterClass() {
		revoke_super_admin( self::$super_admin_id );
	}

	public function set_up() {
		parent::set_up();

		$this->reset_static_properties();

		/*
		 * Use one installed plugin without dependencies. The plugin list is set
		 * directly, so no plugin file needs to exist on disk.
		 */
		$this->set_static_property(
			'plugins',
			array(
				self::PLUGIN_FILE => array(
					'Name'            => 'Dependent',
					'RequiresPlugins' => '',
				),
			)
		);

		// The action is registered in admin-ajax.php, which the tests do not load.
		add_action( 'wp_ajax_check_plugin_dependencies', array( 'WP_Plugin_Dependencies', 'check_plugin_dependencies_during_ajax' ) );

		wp_set_current_user( self::$super_admin_id );

		$_POST['slug']        = 'dependent';
		$_POST['_ajax_nonce'] = wp_create_nonce( 'updates' );
	}

	public function tear_down() {
		$this->reset_static_properties();

		parent::tear_down();
	}

	/**
	 * Tests that an inactive plugin gets a network activation URL.
	 */
	public function test_should_return_network_activate_url_for_inactive_plugin() {
		$response = $this->make_request();

		$this->assertTrue( $response['success'], 'The request should succeed.' );
		$this->assertArrayHasKey( 'activateUrl', $response['data'], 'An inactive plugin should have an activation URL.' );
		$this->assertStringStartsWith( network_admin_url( 'plugins.php?' ), $response['data']['activateUrl'], 'The URL should point to the network plugins screen.' );
		$this->assertStringContainsString( 'action=activate', $response['data']['activateUrl'], 'The URL should activate the plugin.' );
		$this->assertStringContainsString( 'networkwide=1', $response['data']['activateUrl'], 'The URL should activate the plugin network-wide.' );
	}

	/**
	 * Tests that a network-active plugin does not get an activation URL.
	 */
	public function test_should_not_return_activate_url_for_network_active_plugin() {
		update_site_option( 'active_sitewide_plugins', array( self::PLUGIN_FILE => time() ) );

		$response = $this->make_request();

		$this->assertTrue( $response['success'], 'The request should succeed.' );
		$this->assertArrayNotHasKey( 'activateUrl', $response['data'], 'A network-active plugin should not have an activation URL.' );
	}

	/**
	 * Tests that a plugin active on the current site does not get an activation URL.
	 */
	public function test_should_not_return_activate_url_for_plugin_active_on_site() {
		update_option( 'active_plugins', array( self::PLUGIN_FILE ) );

		$response = $this->make_request();

		$this->assertTrue( $response['success'], 'The request should succeed.' );
		$this->assertArrayNotHasKey( 'activateUrl', $response['data'], 'A plugin active on the site should not have an activation URL.' );
	}

	/**
	 * Runs the Ajax request and returns the decoded response.
	 *
	 * @return array The decoded JSON response.
	 */
	private function make_request() {
		try {
			$this->_handleAjax( 'check_plugin_dependencies' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Resets the static properties of WP_Plugin_Dependencies to their default values.
	 */
	private function reset_static_properties() {
		foreach ( self::$static_properties as $property => $value ) {
			$this->set_static_property( $property, $value );
		}
	}

	/**
	 * Sets a static property of WP_Plugin_Dependencies.
	 *
	 * @param string $property The property name.
	 * @param mixed  $value    The new value.
	 */
	private function set_static_property( $property, $value ) {
		$reflection = new ReflectionProperty( 'WP_Plugin_Dependencies', $property );

		if ( PHP_VERSION_ID < 80100 ) {
			$reflection->setAccessible( true );
		}

		$reflection->setValue( null, $value );
	}
}
