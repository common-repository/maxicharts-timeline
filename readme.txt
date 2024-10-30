=== MaxiCharts Timeline ===
Contributors: maxicharts,munger41
Tags: maxicharts, timeline, cpt, gravity, flow, post, poge, csv
Requires at least: 4.0
Requires PHP: 7
Tested up to: 5.6

Create beautiful timelines based on posts, pages Custom Post Types, free csv datasheets, or even Gravity Flow events. 

== Description ==

Create beautiful timelines based on posts, pages or free csv datasheets. Uses the wonderfull js library [vis.js](http://visjs.org/docs/timeline/index.html "vis.js").
Integrates with [Gravity Flow](https://gravityflow.io/ "Gravity Flow")

### Shortcodes ###

Create a timeline for blog's posts or other post type (even CPT):

`[maxicharts_timeline_post type="[post|page|cpt1|cpt2|...] groups="[draft|pending|publish...]"]"`

Create a timeline from a csv:

`[maxicharts_timeline_csv data_path="[url_of_your_file.csv]" separator="[,|;]"]`

where:

* `type` : is the post type you want to create timeline for
* `data_path` : is the URL of your `.csv` file
* `separator` : specifies the csv separator, usually `,` or `;`
* `groups` : only show post with [these statuses](https://codex.wordpress.org/Post_Status "posts statuses")

Inside your CSV file (url set in `data_path`), you need to use field defined by the [visjs timeline documentation](http://visjs.org/docs/timeline/#Data_Format "TimeLine Doc").
Required fields are `content` and `start`, all others are optional.

Create a timeline for [Gravity Flow](https://gravityflow.io/ "Gravity Flow"):

`[maxicharts_timeline_gravity_flow form_id=[form_id] entry_id=[entry_id] groups=[step|assignee|workflow]]`

where:

* `form_id` : is the gravity forms form ID you want to create timeline for
* `entry_id` : is the GF entry ID you want to create timeline for
* `groups` : coma separated list of status types you want to make appear on the timeline

== Installation ==

### Easy ###
1. Search via plugins > add new.
2. Find the plugin listed and click activate.
3. Use the Shortcodes


== Screenshots ==


== Changelog ==

1.2.0 - gravity flow integration

1.1.1 - maxentries added

1.1.0 - locales added

1.0.6 - bug on start date for timeline events

1.0.5 - deploying first features

1.0 - First stable release.