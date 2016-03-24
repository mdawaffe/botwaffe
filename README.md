botwaffe
========

botwaffe receives PubSubHubbub pings and likes all posts on a site via the WordPress.com REST API.

Requirements
------------

* [`jshon`](http://kmkeen.com/jshon/)

Install
-------
1. `make install`
2. Make sure `./log` is writeable by the webserver.

Configure
---------

You'll need:

1. A WordPress.com user, which will do the liking and
2. A [WordPress.com app](https://developer.wordpress.com/apps/) registered under any WordPress.com user.

`make token` will walk you through the app and token generation process.

Follow a Site
-------------

`./subscribe [SITE_FEED_URL]`

For example, `./subscribe 'https://developer.wordpress.com/blog/feed/'`
