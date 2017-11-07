Garnish - image caching proxy
=============================

## Rationale

We needed to migrate one of our apps to HTTPS. Unfortunately it uses a lot of 3rd party images through partners.
Most of them do not provide SSL, thus to avoid insecure content warnings we had to proxy them. 

For optimal performance the images are cached, but stored for no longer than 24h since last access (configurable).

Apart from the image proxying the server is able to resize/crop them to selected dimensions on the fly.
It's also possible to easily add your own middleware for any transformation.

## Performance considerations

Once the image is cached on disk, subsequent requests serve it directly using symfony's excellent
`BinaryFileResponse` which internally uses `stream_copy_to_stream` and should be quite performant.

Remember then putting a CDN in front of the server should nullify most of the performance concerns.

### X-Send-File

For even more performance please consider installing XSendFile apache module and enable it in garnish config (`enable_x_sendfile` option.

### Skip PHP for cached images and serve them from HTTP server directly

The ultimate solution is to skip PHP entirely once the image is cached. For this purpose you have to use
URL rewriting.

An example rewriting setup for apache is provided in `web/.htaccess` file.

Because URL is not safe to use as a filename a hash is used instead. The hash is constructed by concatenating
the source image URL and any parameters used by middleware (usually rest of query params) sorted by name:

```php
    private function getFilename($uri, array $parameters = [])
    {
        ksort($parameters);

        $id = md5('[' . $uri . ']' . http_build_query($parameters));

        return $this->storageDirectory . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id;
    }
```

Example code to build the URL using resize middleware parameters and default picture:

```php
    private function buildProxiedUrl($targetUrl, $defaultUrl = null, $width = null, $height = null)
    {

        $parameters = [
            'w' => $width,
            'h' => $height,
        ];

        if ($width && $height) {
            $parameters['s'] = 'contain';
        }

        ksort($parameters);

        $hash = md5('[' . $targetUrl . ']' . http_build_query($parameters));

        return $this->proxyUrl . $hash . '/?' . http_build_query(array_merge([
            'u' => $targetUrl,
            'd' => $defaultUrl,
        ], $parameters));
    }
```

The downside is that for this mechanism to work you have to construct the hash (id) in your app. Then instead
of linking directly to the endpoint you need to add the hash while still keeping the query params:

```
# Before
http://garnish.host/?u=http://catserver.com/catpicture.jpg
# After 
http://garnish.host/a9edab2987635559bcf015bea8d4ad7b/?u=http://catserver.com/catpicture.jpg
```

## Installation

Clone, `composer install` and point your HTTP server to `/web` as root while rewriting requests for
non-existent files to `web/app.php`. 

Remember that `var` and `web/images` (storage directory) have to be writable both by webserver and the user
which is running the cleaning cronjob.

## Configuration

Create `config.json` file in the main directory. The default settings are:
```json
{
    "max_age": 86400,                               
    "max_lifetime": "1 day",
    "storage_plugin": "filesystem",
    "user_agent": "Garnish/1.0",
    "enable_x_sendfile": false,
    "storage_directory": "%root_dir%web/images/",
    "fetch_timeout": 5,
    "restrict_referers": false,
    "log_fetch_errors": false,
    "log_level": "warning",
    "url_parameter_name": "u",
    "default_parameter_name": "d",
    "middleware": [
        "resize"
    ]
}
```

- `max_age` - 24h by default - the max-age value set on responses
- `max_lifetime` - the time before cached file is removed (as a `DateTime::modify` string)
- `storage_plugin` - currently only `filesystem` is available
- `user_agent` - user-agent sent when fetching images from remote
- `enable_x_sendfile` - whether to support XSendFile mechanism
- `storage_directory` - directory for storing cached images
- `fetch_timeout` - timeout when fetching files from remote
- `restrict_referers` - list of regexps which will be used agains `Referer` header, only matching will be allowed;
  if `false` all are allowed
- `log_fetch_errors` - whether fetch errors should be logged
- `log_level` - see `Psr\Log\LogLevel` consts
- `url_parameter_name` - name of the query parameter for passing the target image url
- `default_parameter_name` - name of the query parameter for setting the default image
- `middleware` - enabled middleware list - currently only `resize` available


## Resize middleware

When enabled, additional query parameters are available:

- `w` - desired width
- `h` - desired height
- `s` - mode of operation, one of: cover, contain or exact
 
### Modes of operation

- `cover` - the WxH viewport is covered by the picture whole & cropped
- `contain` - the picture is fit whole into the WxH viewport
- default (no value) - the output picture will be exactly WxH without preserving aspect ratio

If only one W or H is given then the picture is resized to fit preserving the aspect ratio. With the mode ignored.

### Recommended image transformation library

`gmagick` php module is highly recommended for the `resize` middleware.

## TODO

- Amazon S3 storage plugin
- Rackspace cloudfiles storage plugin
- X-Sendfile support instructions
- Sample NGINX configuration
- Apache ETag format should be compatible with symfony's
- Images for 4xx ?

