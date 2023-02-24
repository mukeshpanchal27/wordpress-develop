/**
 * External dependencies
 */
import { basename, join } from 'path';
import { writeFileSync } from 'fs';
import { exec } from 'child_process';

/**
 * WordPress dependencies
 */
import { activateTheme, createURL } from '@wordpress/e2e-test-utils';

describe( 'Server Timing – Twenty Twenty One', () => {
	const results = {
		wpBeforeTemplate: [],
		wpTemplate: [],
		wpTotal: [],
	};

	beforeAll( async () => {
		await activateTheme( 'twentytwentyone' );
		await exec( 'npm run env:cli -- menu location assign all-pages primary' );
	} );

	afterAll( async () => {
		const prefixArg = process.argv.find((arg) => arg.startsWith('--prefix'));
		const fileNamePrefix = prefixArg ? `${prefixArg.split('=')[1]}-` : '';
		const resultsFilename = fileNamePrefix + basename( __filename, '.js' ) + '.results.json';
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
