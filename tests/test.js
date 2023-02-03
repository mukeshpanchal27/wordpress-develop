const request = require('request');

const options = {
  url: 'https://stereotypedcover.s4-tastewp.com/',
  headers: {
    'User-Agent': 'request'
  }
};

function callback(error, response, body) {
  if (!error && response.statusCode == 200) {
    console.log(response.headers.link);
  }
}

request(options, callback);