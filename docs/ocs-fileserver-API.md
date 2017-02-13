# ocs-fileserver-API


## Client registration

To use the ocs-fileserver-API,
client application should be register into ocs-fileserver.
And get Client ID and Secret Key for authentication.

However, ocs-fileserver does not serves a register page.
So registration process is manually as a part of ocs-fileserver development.

Please see: {project}/api_application/configs/clients.ini


## Data relationship

To store a data into ocs-fileserver and make a data relationship,
some metadata like User ID in client service side has been required.

If need keep data relationship,
client application should be update the metadata stored in ocs-fileserver
when the metadata in client service side changed.


----


## API base URI

API base URI: https://dl.opendesktop.org/api/


## General request parameters

These request parameters are possible to use on all API call.

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
format | - | xml/json | xml | Response data format.
callback | - | callback_name | - | If format=json, will make response data into JSONP.
ignore_status_code | - | 0/1 | 0 | Ignore HTTP status code and send back 200 code always.


## HTTP method override

The ocs-fileserver-API has supported GET, PUT, DELETE and POST methods,
and those methods are possible to override by
POST with "X-HTTP-Method-Override" header or "method" parameter.

HTTP method | HTTP header | Required | Value | Default | Description
------------|-------------|----------|-------|---------|------------
POST | X-HTTP-Method-Override | - | GET/PUT/DELETE | - | HTTP method override.

HTTP method | Parameter | Required | Value | Default | Description
------------|-----------|----------|-------|---------|------------
POST | method | - | GET/PUT/DELETE | - | HTTP method override.


## Response data

HTTP status code will present response status,
and response data contain "status" field.

Available data format: XML/JSON/JSONP

Available/Reserved status value: success/error/failure/unknown

Example of XML data (application/xml):

    <?xml version="1.0" encoding="UTF-8"?>
    <response>
        <status>success</status>
    </response>

Example of JSON data (application/json):

    {"status":"success"}

Example of JSONP data (text/javascript):

    callback_name({"status":"success"})


## Error exception

HTTP status code will present response status,
and response data contain "message" field and "errors" field, if possible.

Example of error:

    <?xml version="1.0" encoding="UTF-8"?>
    <response>
        <status>error</status>
        <message>Validation error</message>
        <errors>
            <owner_id>Required</owner_id>
        </errors>
    </response>


----


## Profiles

### Get index of profiles

Request: GET {baseURI}/profiles/index

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
search | - | Search term | - | 3 or more strings.
ids | - | ID,ID,ID | - | Profile ID list as comma-separated list.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
sort | - | name/newest | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get profile

Request: GET {baseURI}/profiles/profile

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | Profile ID | - | Profile ID.

### Add/Update profile

Request: POST {baseURI}/profiles/profile

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
owner_id | Yes | User ID | - | User ID.
name | Yes | Name | - | User screen name.
email | - | Email address | - | Additional information.
homepage | - | Homepage address | - | Additional information.
image | - | Image address | - | Additional information.
description | - | Description | - | Additional information.

### Update profile

Request: PUT {baseURI}/profiles/profile

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Profile ID | - | Profile ID.
name | - | Name | - | User screen name.
email | - | Email address | - | Additional information.
homepage | - | Homepage address | - | Additional information.
image | - | Image address | - | Additional information.
description | - | Description | - | Additional information.

### Delete profile

Request: DELETE {baseURI}/profiles/profile

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Profile ID | - | Profile ID.


## Collections

Collection has represent a directory.

### Get index of collections

Request: GET {baseURI}/collections/index

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
category | - | Category | - | Category.
tags | - | Tag,Tag,Tag | - | Tag list as comma-separated list.
content_id | - | Content ID | - | Content ID.
search | - | Search term | - | 3 or more strings.
ids | - | ID,ID,ID | - | Collection ID list as comma-separated list.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
downloaded_timeperiod_begin | - | Datetime | 1970-01-01 00:00:00 | Begin of the downloaded time period.
downloaded_timeperiod_end | - | Datetime | Current datetime | End of the downloaded time period.
sort | - | name/newest/recent/frequent | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get collection

Request: GET {baseURI}/collections/collection

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | Collection ID | - | Collection ID.

### Add collection

Request: POST {baseURI}/collections/collection

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
owner_id | Yes | User ID | - | User ID.
title | - | Title | Auto generated name | Additional information.
description | - | Description | - | Additional information.
category | - | Category | - | Additional information.
tags | - | Tag,Tag,Tag | - | Additional information. Tag list as comma-separated list.
version | - | Version number | - | Additional information.
content_id | - | Content ID | - | Additional information.
content_page | - | Content page address | - | Additional information.

### Update collection

Request: PUT {baseURI}/collections/collection

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Collection ID | - | Collection ID.
title | - | Title | - | Additional information.
description | - | Description | - | Additional information.
category | - | Category | - | Additional information.
tags | - | Tag,Tag,Tag | - | Additional information. Tag list as comma-separated list.
version | - | Version number | - | Additional information.
content_id | - | Content ID | - | Additional information.
content_page | - | Content page address | - | Additional information.

### Delete collection

When a collection has been deleted,
related data of the collection
and all files in the collection also deleted.

Request: DELETE {baseURI}/collections/collection

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Collection ID | - | Collection ID.

### Download collection

Request: GET {baseURI}/collections/download

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | Collection ID | - | Collection ID.

Response: Torrent data (application/x-bittorrent)


## Files

### Get index of files

Request: GET {baseURI}/files/index

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
status | - | active/inactive/all | active | File status.
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
collection_id | - | Collection ID | - | Collection ID.
collection_category | - | Collection category | - | Collection category.
collection_tags | - | Tag,Tag,Tag | - | Collection tag list as comma-separated list.
collection_content_id | - | Collection content ID | - | Collection content ID.
types | - | audio/mpeg,video/mpeg,video/mp4 | - | File type list as comma-separated list.
category | - | Category | - | Category.
tags | - | Tag,Tag,Tag | - | Tag list as comma-separated list.
content_id | - | Content ID | - | Content ID.
search | - | Search term | - | 3 or more strings.
ids | - | ID,ID,ID | - | File ID list as comma-separated list.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
downloaded_timeperiod_begin | - | Datetime | 1970-01-01 00:00:00 | Begin of the downloaded time period.
downloaded_timeperiod_end | - | Datetime | Current datetime | End of the downloaded time period.
sort | - | name/newest/recent/frequent | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get file

Request: GET {baseURI}/files/file

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | File ID | - | File ID.

### Add file

When a file has been added without specific collection_id,
a new collection created automatically
and append the file to the new collection.

Request: POST {baseURI}/files/file

Enctype: multipart/form-data

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
owner_id | Yes | User ID | - | User ID.
file | Yes | FILE | - | Upload file.
collection_id | - | Collection ID | Auto created collection's ID | Append to specific collection.
title | - | Title | Upload file name | Additional information.
description | - | Description | - | Additional information.
category | - | Category | - | Additional information.
tags | - | Tag,Tag,Tag | - | Additional information. Tag list as comma-separated list.
version | - | Version number | - | Additional information.
content_id | - | Content ID | - | Additional information.
content_page | - | Content page address | - | Additional information.

Special parameters for hive files importing (Deprecated):

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
local_file_path | - | Path/to/local/file | - | File path of local file.
local_file_name | - | File name | - | File name.
downloaded_count | - | 0-n | - | Download counter.

### Update file

Request: PUT {baseURI}/files/file

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | File ID | - | File ID.
title | - | Title | - | Additional information.
description | - | Description | - | Additional information.
category | - | Category | - | Additional information.
tags | - | Tag,Tag,Tag | - | Additional information. Tag list as comma-separated list.
version | - | Version number | - | Additional information.
content_id | - | Content ID | - | Additional information.
content_page | - | Content page address | - | Additional information.

### Delete file

When a file has been deleted,
deleted file has been changed into inactive state,
and real file has moved to the trash directory in a collection directory.

And related data of the file has been deleted.

Request: DELETE {baseURI}/files/file

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | File ID | - | File ID.

### Download file

Request: GET {baseURI}/files/download

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | File ID | - | File ID.

Response: Uploaded data or redirect to external URI


## Favorites

### Get index of favorites

Request: GET {baseURI}/favorites/index

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
user_id | - | User ID | - | User ID.
owner_id | - | User ID | - | User ID.
collection_id | - | Collection ID | - | Collection ID.
file_id | - | File ID | - | File ID.
ids | - | ID,ID,ID | - | Favorite ID list as comma-separated list.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get favorite

Request: GET {baseURI}/favorites/favorite

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | Favorite ID | - | Favorite ID.

### Add/Get favorite

Request: POST {baseURI}/favorites/favorite

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
user_id | Yes | User ID | - | User ID.
owner_id | - | User ID | - | User ID. Add to favorite.
collection_id | - | Collection ID | - | Collection ID. Add to favorite.
file_id | - | File ID | - | File ID. Add to favorite.

### Delete favorite

Request: DELETE {baseURI}/favorites/favorite

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Favorite ID | - | Favorite ID.


## Owners

### Delete owner

When a owner has been deleted,
related data of the owner
and all files of the owner also deleted.

Request: DELETE {baseURI}/owners/owner

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | User ID | - | User ID.


## Media

### Get index of genres in media

Request: GET {baseURI}/media/genres

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
collection_id | - | Collection ID | - | Collection ID.
collection_category | - | Collection category | - | Collection category.
collection_tags | - | Tag,Tag,Tag | - | Collection tag list as comma-separated list.
collection_content_id | - | Collection content ID | - | Collection content ID.
file_id | - | File ID | - | File ID.
file_types | - | audio/mpeg,video/mpeg,video/mp4 | - | File type list as comma-separated list.
file_category | - | File category | - | File category.
file_tags | - | Tag,Tag,Tag | - | File tag list as comma-separated list.
file_content_id | - | File content ID | - | File content ID.
artist_id | - | Artist ID | - | Artist ID.
album_id | - | Album ID | - | Album ID.
genre | - | Genre | - | Genre.
search | - | Search term | - | 3 or more strings.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
sort | - | name/newest | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get index of owners in media

Request: GET {baseURI}/media/owners

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
collection_id | - | Collection ID | - | Collection ID.
collection_category | - | Collection category | - | Collection category.
collection_tags | - | Tag,Tag,Tag | - | Collection tag list as comma-separated list.
collection_content_id | - | Collection content ID | - | Collection content ID.
file_id | - | File ID | - | File ID.
file_types | - | audio/mpeg,video/mpeg,video/mp4 | - | File type list as comma-separated list.
file_category | - | File category | - | File category.
file_tags | - | Tag,Tag,Tag | - | File tag list as comma-separated list.
file_content_id | - | File content ID | - | File content ID.
artist_id | - | Artist ID | - | Artist ID.
album_id | - | Album ID | - | Album ID.
genre | - | Genre | - | Genre.
search | - | Search term | - | 3 or more strings.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
sort | - | name/newest | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get index of collections in media

Request: GET {baseURI}/media/collections

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
collection_id | - | Collection ID | - | Collection ID.
collection_category | - | Collection category | - | Collection category.
collection_tags | - | Tag,Tag,Tag | - | Collection tag list as comma-separated list.
collection_content_id | - | Collection content ID | - | Collection content ID.
file_id | - | File ID | - | File ID.
file_types | - | audio/mpeg,video/mpeg,video/mp4 | - | File type list as comma-separated list.
file_category | - | File category | - | File category.
file_tags | - | Tag,Tag,Tag | - | File tag list as comma-separated list.
file_content_id | - | File content ID | - | File content ID.
artist_id | - | Artist ID | - | Artist ID.
album_id | - | Album ID | - | Album ID.
genre | - | Genre | - | Genre.
search | - | Search term | - | 3 or more strings.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
sort | - | name/newest | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get index of media

Request: GET {baseURI}/media/index

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | - | Client ID | - | Client ID.
owner_id | - | User ID | - | User ID.
collection_id | - | Collection ID | - | Collection ID.
collection_category | - | Collection category | - | Collection category.
collection_tags | - | Tag,Tag,Tag | - | Collection tag list as comma-separated list.
collection_content_id | - | Collection content ID | - | Collection content ID.
file_id | - | File ID | - | File ID.
file_types | - | audio/mpeg,video/mpeg,video/mp4 | - | File type list as comma-separated list.
file_category | - | File category | - | File category.
file_tags | - | Tag,Tag,Tag | - | File tag list as comma-separated list.
file_content_id | - | File content ID | - | File content ID.
artist_id | - | Artist ID | - | Artist ID.
album_id | - | Album ID | - | Album ID.
genre | - | Genre | - | Genre.
search | - | Search term | - | 3 or more strings.
ids | - | ID,ID,ID | - | Media ID list as comma-separated list.
favoritesby | - | User ID | - | If this parameter has been set, must be set client_id.
played_timeperiod_begin | - | Datetime | 1970-01-01 00:00:00 | Begin of the played time period.
played_timeperiod_end | - | Datetime | Current datetime | End of the played time period.
sort | - | name/newest/track/recent/frequent | name | Sort order.
perpage | - | 1-100 | 20 | How many results retrieve per request.
page | - | 1-n | 1 | Page number of pagination.

### Get media

Request: GET {baseURI}/media/media

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | Media ID | - | Media ID.

### Get media stream

Request: GET {baseURI}/media/stream

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | Yes | Media ID | - | Media ID.

Response: Audio/Video data

### Get collection thumbnail

Request: GET {baseURI}/media/collectionthumbnail

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | - | Collection ID | - | Collection ID.

Response: JPEG data (image/jpeg)

### Add/Update collection thumbnail

Request: POST {baseURI}/media/collectionthumbnail

Enctype: multipart/form-data

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Collection ID | - | Collection ID.
file | Yes | FILE | - | Upload JPEG or PNG file.

### Get album thumbnail

Request: GET {baseURI}/media/albumthumbnail

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
id | - | Album ID | - | Album ID.

Response: JPEG data (image/jpeg)

### Add/Update album thumbnail

Request: POST {baseURI}/media/albumthumbnail

Enctype: multipart/form-data

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
client_id | Yes | Client ID | - | Client ID.
secret | Yes | Secret key | - | Secret key.
id | Yes | Album ID | - | Album ID.
file | Yes | FILE | - | Upload JPEG or PNG file.


## External

### Get external resource

Call external API over ocs-fileserver-API.

Request: POST {baseURI}/external/resource

Parameter | Required | Value | Default | Description
----------|----------|-------|---------|------------
uri | Yes | URI | - | External API URI.
type | - | xml/json | xml | Resource data format.
