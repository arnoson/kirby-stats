<p align="center">
  <picture>
      <source media="(prefers-color-scheme: dark)" srcset="./.github/logo-dark.svg">
      <img src="./.github/logo-light.svg" alt="" />
  </picture>
</p>

<h1 align="center">Kirby Stats</h1>

Simple and privacy-friendly Kirby plugin for tracking website activity.

## Installation

```sh
composer require arnoson/kirby-stats
```

## Usage

Copy the [stats.js](https://github.com/arnoson/kirby-stats/tree/main/stats.js) file from the plugin folder into Kirby's asset folder. Then include the file in your templates:

```php
<?= js('assets/stats.js', ['defer']) ?>
```

Also see the [example folder](https://github.com/arnoson/kirby-stats/tree/main/example) for a full example.

## Options

```php
// site/config.php
[
  'arnoson.kirby-stats' => [
    // Enable or disable tracking. This is useful to disable tracking in local
    // development by using Kirby's multi environment config setup:
    // https://getkirby.com/docs/guide/configuration#multi-environment-setup
    'enabled' => true, // default

    // The time interval in which to group the collected data. The default is
    // 'day', which should be sufficient for most use cases. Use 'hour' to
    // collect data more granularly, or 'week' or 'month' if you only need a
    // broader overview.
    'interval' => 'day', // default

    // Where to create the database.
    'database' => kirby()->root('logs') . '/kirby-stats/stats.sqlite', // default
  ],
];
```

## Advanced Usage

If you want more control, you can also call the tracking endpoints manually instead of including the provided script, for example if you are using an SPA-setup. It is import that you always call both `page` and `site` endpoints for each page visit. Have a look at the [provided script](https://github.com/arnoson/kirby-stats/tree/main/src/stats.ts) for more details.

```js
const path = location.pathname.replace(/^\//, '') // remove leading slash
fetch(`/kirby-stats/page/${path}`, { keepalive: true }) // increase page counters
fetch(`/kirby-stats/site`, { keepalive: true }) // increase total page counters and unique visitors
```

## Privacy

I'm no lawyer and can't give you any legal advice regarding GDPR. But I can tell you what data the plugin is collecting. For each page view various counters are increased for a specified time interval. For example, after the first user has visited your about page the data would be:

### Traffic

| uuid         | time       | interval | views | visits | visitors |
| :----------- | :--------- | :------- | :---- | :----- | :------- |
| site://      | <this day> | day      | 1     | 1      | 1        |
| page://about | <this day> | day      | 1     | 1      | 0        |

Visitors are tracked on a site-wide level using a technique based on request caching headers. This method, pioneered by [Cabin](https://withcabin.com/), allows for the accurate measurement of unique visitors without the need for cookies. You can learn more about this approach in their [blog post](https://withcabin.com/blog/how-cabin-measures-unique-visitors-without-cookies).

### Meta

| uuid         | time        | interval | category | key     | value |
| :----------- | :---------- | :------- | :------- | :------ | :---- |
| site://      | <this week> | week     | Browser  | Firefox | 1     |
| page://about | <this week> | week     | OS       | Windows | 1     |

Metadata such as browser and OS are also grouped per time interval but stored less granularly. That's it. No IP addresses are stored, no cookies are set by this plugin, and no unique requests are tracked. All data is grouped into time intervals.

## Support

This plugin is free and open source. While it's provided at no cost, maintaining and improving it requires ongoing time and effort. If you're using it in a commercial setting or simply wish to support its development, please [make a donation of your choice](https://www.paypal.com/paypalme/arnoson).

## Thanks

- Matomo for making their [device detector](https://github.com/matomo-org/device-detector) a standalone package
- Cabin Analytics for [sharing](https://withcabin.com/blog/how-cabin-measures-unique-visitors-without-cookies) their method on how to measure unique visitors without cookies
