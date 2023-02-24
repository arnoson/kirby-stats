# Kirby Stats

> :warning: This plugin is still in alpha, a lot of features are still missing or not completed yet.

Kirby stats is a simple, privacy friendly Kirby 3 plugin for tracking page views and visits.

<img alt="Kirby Stats Panel Screenshot" src="assets/kirby-stats-screenshot.png" width="700">

## Note

I'm no lawyer and can't give you any legal advice regarding GDPR. I can only tell you what data the plugin is collecting.
For each page view various counters are increased for a specified time interval. For example, after the first user has visited your website the data would be:

| path | time          | views | visits | Firefox | Windows |
| :--- | :------------ | :---- | :----- | :------ | :------ |
| /    | unixtimestamp | 1     | 1      | 1       | 1       |

- `path` is the url path
- `unixtimestamp` is the time of the interval you choose to track. For example if you track hourly, the `unixtimestamp` would be the beginning of the current hour.
- `views` will increase for each page view, except reloads
- `visit` will only increase if a user visits your website either from a blank tab or via a link from another website. To determine if a request counts as a visit, the user's referrer is checked (but not saved). This also means that these aren't unique visits like in many other tracking plugins. If the same person visits your website two times in a day you will see two visits.
- `Firefox`, `Opera`, `MicrosoftEdge`, `InternetExplorer`, `Safari` or `Chrome` will be increased for each visit depending on the user's browser (the user agent is used to determine this).
- `Windows`, `Mac`, `Linux`, `Android`, `iOS` will be increased for each visit depending on the users's operating system (the user agent is used to determine this).

That's it. No IP address is stored and no cookie is set by this plugin. And also no unique requests, all data is grouped into time intervals. If a second user visits in the same hour, the counters are increased accordingly. If a user visits in the following hour or later a new row of counters is created in the table.

Please checkout the source code and decide for yourself what this means for your privacy statement and GDPR.

## Installation

```sh
composer require arnoson/kirby-stats
```

## Usage

See the `/example` folder, documentation coming soon.

# Credits

- https://github.com/cbschuld/Browser.php
- https://github.com/FabianSperrle/kirby-stats
- https://github.com/Daandelange/kirby3-simplestats
