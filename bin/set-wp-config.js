#!/usr/bin/env node

const {
	runShellScript,
	readJSONFile,
	askForConfirmation,
	getRandomTemporaryPath,
} = require( 'utils' );
const path = require( 'path' );
const fs = require( 'fs' );

const baseDirectory = getRandomTemporaryPath();
fs.mkdirSync( baseDirectory, { recursive: true } );

const rootDirectory = getRandomTemporaryPath();
const performanceTestDirectory = rootDirectory + '/tests';
await runShellScript( 'mkdir -p ' + rootDirectory );
await runShellScript(
    'cp -R ' + baseDirectory + ' ' + performanceTestDirectory
);

log( '    >> Installing dependencies and building packages' );
await runShellScript(
    'npm ci && node ./bin/packages/build.js',
    performanceTestDirectory
);
log( '    >> Creating the environment folders' );
await runShellScript( 'mkdir -p ' + rootDirectory + '/envs' );