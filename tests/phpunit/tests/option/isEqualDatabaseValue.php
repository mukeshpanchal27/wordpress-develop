<?php
/**
 * Test is_equal_database_value().
 *
 * @covers ::is_equal_database_value
 */
class Tests_Is_Equal_Database_Value extends WP_UnitTestCase {

	/**
	 * @ticket 22192
	 *
	 * @dataProvider data_is_equal_database_value
	 *
	 * @param mixed $old_value The old value to compare.
	 * @param mixed $new_value The new value to compare.
	 * @param int   $expected  The expected result.
	 */
	public function test_is_equal_database_value( $old_value, $new_value, $expected ) {
		$this->assertEquals( $expected, is_equal_database_value( $old_value, $new_value ) );
	}

	public function data_is_equal_database_value() {
		return array(
			// Equal values.
			array( '123', '123', true ),

			// Not equal values.
			array( '123', '456', false ),

			// False-ish values and empty strings.
			array( false, '0', true ),
			array( '', '0', true ),

			// Serialized values.
			array( array( 'foo' => 'bar' ), serialize( array( 'foo' => 'bar' ) ), true ),
			array( array( 'foo' => 'bar' ), serialize( array( 'foo' => 'baz' ) ), false ),
		);
	}
}
