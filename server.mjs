import { createBrowser } from './index.mjs';
import express from 'express';
import cookieParser from 'cookie-parser';
import bodyParser from 'body-parser';

import * as url from 'url';
const __filename = url.fileURLToPath(import.meta.url);
const __dirname = url.fileURLToPath(new URL('.', import.meta.url));

const app = express()
app.use(cookieParser());
app.use(bodyParser.urlencoded({ extended: true }));
const port = 8777

const browser = createBrowser();
app.all('*', async (req, res) => {
  if (req.path.endsWith(".php") || req.path.endsWith("/")) {
    const response = await (await browser).request(
      req.url,
      req.method,
      req.body || {},
      req.headers || {},
      req.cookies || {}
    );
    for (const [key, value] of Object.entries(response.headers)) {
      res.setHeader(key, value);
    }
    if ('location' in response.headers) {
      res.status(302);
      res.end();
    } else {
      res.send(response.body);
    }
  } else {
    res.sendFile(`${__dirname}/wordpress${req.path}`);
  }
});

app.listen(port, async () => {
  console.log(`Example app listening on port ${port}`);
});
