const nodeHtmlToImage = require('node-html-to-image');
const fs = require('fs');

(async () => {
  const html = process.argv[2];
  const output = process.argv[3];

  await nodeHtmlToImage({
    html,
    output,
    quality: 100,
    type: 'png',
    puppeteerArgs: {
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
  });

  process.exit(0);
})();