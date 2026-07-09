<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Completing sign-in…</title>
  </head>
  <body>
    <script>
      (function () {
        // Facebook can append `#_=_` and (in some edge flows) may place params in the fragment.
        // The fragment is not sent to the server, so we normalize it into the query string.
        var hash = window.location.hash || '';
        if (!hash || hash === '#_=_') {
          // Nothing useful in hash; continue (server will show error/redirect).
          return;
        }

        var fragment = hash.replace(/^#/, '');
        var params = new URLSearchParams(fragment);
        if (![...params.keys()].length) return;

        var url = new URL(window.location.href);
        // Merge fragment params into query if missing.
        params.forEach(function (value, key) {
          if (!url.searchParams.has(key)) {
            url.searchParams.set(key, value);
          }
        });
        url.hash = '';
        window.location.replace(url.toString());
      })();
    </script>
    <noscript>JavaScript required to complete sign-in.</noscript>
  </body>
</html>

