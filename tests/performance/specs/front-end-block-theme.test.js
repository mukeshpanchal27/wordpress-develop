/**
 * External dependencies
 */
import { basename, join } from 'path';
import { writeFileSync } from 'fs';

/**
 * WordPress dependencies
 */
import { activateTheme, createURL } from '@wordpress/e2e-test-utils';

describe( 'Server Timing – Twenty Twenty Three', () => {
	const results = {
		wpBeforeTemplate: [],
		wpTemplate: [],
		wpTotal: [],
	};

	beforeAll( async () => {
		await activateTheme( 'twentytwentythree' );
	} );

	afterAll( async () => {
		const resultsFilename = basename( __filename, '.js' ) + '.results.json';
		writeFileSync(
			join( __dirname, resultsFilename ),
			JSON.stringify( results, null, 2 )
		);
	} );

	it( 'Server Timing Metrics', async () => {
		let i = 20;
		while ( i-- ) {
			await page.goto( createURL( '/' ) );
			const navigationTimingJson = await page.evaluate( () =>
				JSON.stringify( performance.getEntriesByType( 'navigation' ) )
			);

			const [ navigationTiming ] = JSON.parse( navigationTimingJson );

			results.wpBeforeTemplate.push(
				navigationTiming.serverTiming[0].duration
			);
			results.wpTemplate.push(
				navigationTiming.serverTiming[1].duration
			);
			results.wpTotal.push(
				navigationTiming.serverTiming[2].duration
			);
		}
	} );
} );
