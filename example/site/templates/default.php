<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <?= js('assets/stats.js', ['defer']) ?>
</head>
<body>
  <h1><?= $page->title() ?></h1>
  <ul>
    <li><a href="/">Home</a></li>
    <li><a href="/test">Test</a></li>
  </ul>

  <label>
    Ajax:
    <input type="checkbox" id="ajaxCheckbox" />
  </label>

  <script type="module">
    // Super simple ajax navigation for this demo. Don't use this in production!
    const visit = async (url) => {
      history.pushState(null, '', url)
      const html = await fetch(url).then(response => response.text())
      const dom = new DOMParser().parseFromString(html, 'text/html')
      document.body.replaceWith(dom.body)
      setupAjaxToggle()
      setupAjax()
    }

    const setupAjax = () => {
      ajaxCheckbox.checked = true
      for (const link of document.querySelectorAll('a')) {
        link.addEventListener('click', (e) => {
          e.preventDefault()
          visit(link.href)
        })
      }
    }

    const setupAjaxToggle = () => {
      ajaxCheckbox.addEventListener('change', () => {
        if (ajaxCheckbox.checked) setupAjax()
        else window.location.reload()
      })
    }

    setupAjaxToggle()
  </script>
</body>
</html>