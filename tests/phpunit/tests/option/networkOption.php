<?php

/**
 * Tests specific to managing network options in multisite.
 *
 * Some tests will run in single site as the `_network_option()` functions
 * are available and internally use `_option()` functions as fallbacks.
 *
 * @group option
 * @group ms-option
 * @group multisite
 */
class Tests_Option_NetworkOption extends WP_UnitTestCase {

	/**
	 * @group ms-required
	 *
	 * @covers ::add_site_option
	 */
	public function test_add_network_option_not_available_on_other_network() {
		$id     = self::factory()->network->create();
		$option = __FUNCTION__;
		$value  = __FUNCTION__;

		add_site_option( $option, $value );
		$this->assertFalse( get_network_option( $id, $option, false ) );
	}

	/**
	 * @group ms-required
	 *
	 * @covers ::add_network_option
	 */
	public function test_add_network_option_available_on_same_network() {
		$id     = self::factory()->network->create();
		$option = __FUNCTION__;
		$value  = __FUNCTION__;

		add_network_option( $id, $option, $value );
		$this->assertSame( $value, get_network_option( $id, $option, false ) );
	}

	/**
	 * @group ms-required
	 *
	 * @covers ::delete_site_option
	 */
	public function test_delete_network_option_on_only_one_network() {
		$id     = self::factory()->network->create();
		$option = __FUNCTION__;
		$value  = __FUNCTION__;

		add_site_option( $option, $value );
		add_network_option( $id, $option, $value );
		delete_site_option( $option );
		$this->assertSame( $value, get_network_option( $id, $option, false ) );
	}

	/**
	 * @ticket 22846
	 * @group ms-excluded
	 *
	 * @covers ::add_network_option
	 */
	public function test_add_network_option_is_not_stored_as_autoload_option() {
		$key = __FUNCTION__;

		add_network_option( null, $key, 'Not an autoload option' );

		$options = wp_load_alloptions();

		$this->assertArrayNotHasKey( $key, $options );
	}

	/**
	 * @ticket 22846
	 * @group ms-excluded
	 *
	 * @covers ::update_network_option
	 */
	public function test_update_network_option_is_not_stored_as_autoload_option() {
		$key = __FUNCTION__;

		update_network_option( null, $key, 'Not an autoload option' );

		$options = wp_load_alloptions();

		$this->assertArrayNotHasKey( $key, $options );
	}

	/**
	 * @dataProvider data_network_id_parameter
	 *
	 * @param $network_id
	 * @param $expected_response
	 *
	 * @covers ::add_network_option
	 */
	public function test_add_network_option_network_id_parameter( $network_id, $expected_response ) {
		$option = rand_str();
		$value  = rand_str();

		$this->assertSame( $expected_response, add_network_option( $network_id, $option, $value ) );
	}

	/**
	 * @dataProvider data_network_id_parameter
	 *
	 * @param $network_id
	 * @param $expected_response
	 *
	 * @covers ::get_network_option
	 */
	public function test_get_network_option_network_id_parameter( $network_id, $expected_response ) {
		$option = rand_str();

		$this->assertSame( $expected_response, get_network_option( $network_id, $option, true ) );
	}

	public function data_network_id_parameter() {
		return array(
			// Numeric values should always be accepted.
			array( 1, true ),
			array( '1', true ),
			array( 2, true ),

			// Null, false, and zero will be treated as the current network.
			array( null, true ),
			array( false, true ),
			array( 0, true ),
			array( '0', true ),

			// Other truthy or string values should be rejected.
			array( true, false ),
			array( 'string', false ),
		);
	}

	/**
	 * @ticket 43506
	 * @group ms-required
	 *
	 * @covers ::get_network_option
	 * @covers ::wp_cache_get
	 * @covers ::wp_cache_delete
	 */
	public function test_get_network_option_sets_notoptions_if_option_found() {
		$network_id     = get_current_network_id();
		$notoptions_key = "$network_id:notoptions";

		$original_cache = wp_cache_get( $notoptions_key, 'site-options' );
		if ( false !== $original_cache ) {
			wp_cache_delete( $notoptions_key, 'site-options' );
		}

		// Retrieve any existing option.
		get_network_option( $network_id, 'site_name' );

		$cache = wp_cache_get( $notoptions_key, 'site-options' );
		if ( false !== $original_cache ) {
			wp_cache_set( $notoptions_key, $original_cache, 'site-options' );
		}

		$this->assertSame( array(), $cache );
	}

	/**
	 * @ticket 43506
	 * @group ms-required
	 *
	 * @covers ::get_network_option
	 * @covers ::wp_cache_get
	 */
	public function test_get_network_option_sets_notoptions_if_option_not_found() {
		$network_id     = get_current_network_id();
		$notoptions_key = "$network_id:notoptions";

		$original_cache = wp_cache_get( $notoptions_key, 'site-options' );
		if ( false !== $original_cache ) {
			wp_cache_delete( $notoptions_key, 'site-options' );
		}

		// Retrieve any non-existing option.
		get_network_option( $network_id, 'this_does_not_exist' );

		$cache = wp_cache_get( $notoptions_key, 'site-options' );
		if ( false !== $original_cache ) {
			wp_cache_set( $notoptions_key, $original_cache, 'site-options' );
		}

		$this->assertSame( array( 'this_does_not_exist' => true ), $cache );
	}

	/**
	 * Ensure updating network options containing an object do not result in unneeded database calls.
	 *
	 * @ticket 44956
	 *
	 * @covers ::update_network_option
	 */
	public function test_update_network_option_array_with_object() {
		$array_w_object = array(
			'url'       => 'http://src.wordpress-develop.dev/wp-content/uploads/2016/10/cropped-Blurry-Lights.jpg',
			'meta_data' => (object) array(
				'attachment_id' => 292,
				'height'        => 708,
				'width'         => 1260,
			),
		);

		$array_w_object_2 = array(
			'url'       => 'http://src.wordpress-develop.dev/wp-content/uploads/2016/10/cropped-Blurry-Lights.jpg',
			'meta_data' => (object) array(
				'attachment_id' => 292,
				'height'        => 708,
				'width'         => 1260,
			),
		);

		// Add the option, it did not exist before this.
		add_network_option( null, 'array_w_object', $array_w_object );

		$num_queries_pre_update = get_num_queries();

		// Update the option using the same array with an object for the value.
		$this->assertFalse( update_network_option( null, 'array_w_object', $array_w_object_2 ) );

		// Check that no new database queries were performed.
		$this->assertSame( $num_queries_pre_update, get_num_queries() );
	}

	/**
	 * Ensure the database is getting updated when type changes, but not otherwise.
	 *
	 * @ticket 22192
	 *
	 * @covers ::update_network_option
	 *
	 * @dataProvider data_update_network_option_type_juggling
	 */
	public function test_update_loosey_options( $old_value, $new_value, $update = false ) {
		add_network_option( null, 'foo', $old_value );

		// Comparison will happen against value cached during add_option() above.
		$updated = update_network_option( null, 'foo', $new_value );

		if ( $update ) {
			$this->assertTrue( $updated, 'This loosely equal option should trigger an update.' );
		} else {
			$this->assertFalse( $updated, 'Loosely equal option should not trigger an update.' );
		}
	}

	/**
	 * Ensure the database is getting updated when type changes, but not otherwise.
	 *
	 * @ticket 22192
	 *
	 * @covers ::update_network_option
	 *
	 * @dataProvider data_update_network_option_type_juggling
	 */
	public function test_update_loosey_options_from_db( $old_value, $new_value, $update = false ) {
		add_network_option( null, 'foo', $old_value );

		// Delete cache.
		wp_cache_delete( 'alloptions', 'options' );
		$updated = update_network_option( null, 'foo', $new_value );

		if ( $update ) {
			$this->assertTrue( $updated, 'This loosely equal option should trigger an update.' );
		} else {
			$this->assertFalse( $updated, 'Loosely equal option should not trigger an update.' );
		}
	}

	/**
	 * Ensure the database is getting updated when type changes, but not otherwise.
	 *
	 * @ticket 22192
	 *
	 * @covers ::update_network_option
	 *
	 * @dataProvider data_update_network_option_type_juggling
	 */
	public function test_update_loosey_options_from_refreshed_cache( $old_value, $new_value, $update = false ) {
		add_network_option( null, 'foo', $old_value );

		// Delete and refresh cache from DB.
		wp_cache_delete( 'alloptions', 'options' );
		wp_load_alloptions();

		$updated = update_network_option( null, 'foo', $new_value );

		if ( $update ) {
			$this->assertTrue( $updated, 'This loosely equal option should trigger an update.' );
		} else {
			$this->assertFalse( $updated, 'Loosely equal option should not trigger an update.' );
		}
	}


	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_update_network_option_type_juggling() {
		return array(
			/*
			 * Truthy values.
			 * Loosely equal truthy scalar values should never result in a DB update.
			 */
			array( '1', '1' ),
			array( '1', 1 ),
			array( '1', 1.0 ),
			array( '1', true ),
			array( 1, '1' ),
			array( 1, 1 ),
			array( 1, 1.0 ),
			array( 1, true ),
			array( 1.0, '1' ),
			array( 1.0, 1 ),
			array( 1.0, 1.0 ),
			array( 1.0, true ),
			array( true, '1' ),
			array( true, 1 ),
			array( true, 1.0 ),
			array( true, true ),

			/*
			 * Falsey values.
			 * Loosely equal falsey scalar values only sometimes result in a DB update.
			 */
			array( '0', '0' ),
			array( '0', 0 ),
			array( '0', 0.0 ),
			array( '0', false, true ), // Should update.
			array( '', '' ),
			array( '', 0, true ), // Should update.
			array( '', 0.0, true ), // Should update.
			array( '', false ),
			array( 0, '0' ),
			array( 0, '', true ), // Should update.
			array( 0, 0 ),
			array( 0, 0.0 ),
			array( 0, false, true ), // Should update.
			array( 0.0, '0' ),
			array( 0.0, '', true ), // Should update.
			array( 0.0, 0 ),
			array( 0.0, 0.0 ),
			array( 0.0, false, true ), // Should update.
			array( false, '0', true ), // Should update.
			array( false, '' ),
			array( false, 0, true ), // Should update.
			array( false, 0.0, true ), // Should update.
			array( false, false ),

			/*
			 * Non scalar values.
			 * Loosely equal non-scalar values should almost always result in an update.
			 */
			array( false, array(), true ),
			array( 'false', array(), true ),
			array( '', array(), true ),
			array( 0, array(), true ),
			array( '0', array(), true ),
			array( false, null, true ),
			array( 'false', null, true ),
			array( '', null, false ),
			array( 0, null, true ),
			array( '0', null, true ),
			array( array(), false, true ),
			array( array(), 'false', true ),
			array( array(), '', true ),
			array( array(), 0, true ),
			array( array(), '0', true ),
			array( array(), null, true ),
			array( null, false ), // Does not update.
			array( null, 'false' ), // Does not update.
			array( null, '', true ),
			array( null, 0, true ),
			array( null, '0', true ),
			array( null, array(), true ),
		);
	}
}
