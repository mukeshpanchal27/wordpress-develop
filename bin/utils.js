/**
 * External dependencies
 */
// @ts-ignore

const fs = require( 'fs' );
const childProcess = require( 'child_process' );
const { v4: uuid } = require( 'uuid' );
const path = require( 'path' );
const os = require( 'os' );

/**
 * Utility to run a child script
 *
 * @typedef {NodeJS.ProcessEnv} Env
 *
 * @param {string}  script Script to run.
 * @param {string=} cwd    Working directory.
 * @param {Env=}    env    Additional environment variables to pass to the script.
 */
function runShellScript( script, cwd, env = {} ) {
	return new Promise( ( resolve, reject ) => {
		childProcess.exec(
			script,
			{
				cwd,
				env: {
					NO_CHECKS: 'true',
					PATH: process.env.PATH,
					HOME: process.env.HOME,
					...env,
				},
			},
			function ( error, _, stderr ) {
				if ( error ) {
					console.log( stderr );
					reject( error );
				} else {
					resolve( true );
				}
			}
		);
	} );
}

/**
 * Small utility used to read an uncached version of a JSON file
 *
 * @param {string} fileName
 */
function readJSONFile( fileName ) {
	const data = fs.readFileSync( fileName, 'utf8' );
	return JSON.parse( data );
}

/**
 * Generates a random temporary path in the OS's tmp dir.
 *
 * @return {string} Temporary Path.
 */
function getRandomTemporaryPath() {
	return path.join( os.tmpdir(), uuid() );
}

module.exports = {
	askForConfirmation,
	runStep,
	readJSONFile,
	runShellScript,
	getRandomTemporaryPath,
};