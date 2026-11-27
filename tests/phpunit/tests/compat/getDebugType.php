<?php

/**
 * @group compat
 *
 * @covers ::get_debug_type
 */
class Tests_Compat_getDebugType extends WP_UnitTestCase {

	/**
	 * Tests that get_debug_type() is always available (either from PHP or WP).
	 */
	public function test_get_debug_type_availability(): void {
		$this->assertTrue( function_exists( 'get_debug_type' ) );
	}

	/**
	 * Tests the type names returned by get_debug_type().
	 *
	 * @dataProvider data_get_debug_type
	 *
	 * @param string $expected The expected type name.
	 * @param mixed  $value    The value to get the type name of.
	 */
	public function test_get_debug_type( string $expected, $value ): void {
		$this->assertSame( $expected, get_debug_type( $value ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array<string, array{ 0: string, 1: mixed }>
	 */
	public static function data_get_debug_type(): array {
		return array(
			'null'                  => array( 'null', null ),
			'true'                  => array( 'bool', true ),
			'false'                 => array( 'bool', false ),
			'integer'               => array( 'int', 42 ),
			'float'                 => array( 'float', 1.5 ),
			'string'                => array( 'string', 'value' ),
			'empty string'          => array( 'string', '' ),
			'array'                 => array( 'array', array( 1, 2 ) ),
			'stdClass'              => array( 'stdClass', new stdClass() ),
			'named class'           => array( 'ArrayObject', new ArrayObject() ),
			'closure'               => array( 'Closure', static function () {} ),
			'anonymous class'       => array( 'class@anonymous', new class() {} ),
			'anonymous subclass'    => array( 'ArrayObject@anonymous', new class() extends ArrayObject {} ),
			'anonymous implementer' => array(
				'Countable@anonymous',
				new class() implements Countable {
					public function count(): int {
						return 0;
					}
				},
			),
		);
	}

	/**
	 * Tests the type names returned by get_debug_type() for open and closed resources.
	 */
	public function test_get_debug_type_with_resources(): void {
		$resource = fopen( 'php://memory', 'r' );

		$this->assertSame( 'resource (stream)', get_debug_type( $resource ), 'An open stream should be reported as a stream resource.' );

		fclose( $resource );

		$this->assertSame( 'resource (closed)', get_debug_type( $resource ), 'A closed stream should be reported as a closed resource.' );
	}
}
