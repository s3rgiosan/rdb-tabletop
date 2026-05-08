# RDB Tabletop

> BoardGameGeek data source for Remote Data Blocks.

## Description

A WordPress plugin that adds a BoardGameGeek (BGG) data source to [Remote Data Blocks](https://wordpress.org/plugins/remote-data-blocks/). Editors can insert a "Board Game" block to search BGG by title and render full game detail, or one of six collection / wishlist blocks to render items from a BGG user's collection.

Built on the [BGG XML API2](https://boardgamegeek.com/wiki/page/BGG_XML_API2). Calls are made server-side and authenticated with a BGG application bearer token.

## Features

- **Board Game block**: search BGG → pick → render title, year, description, image, thumbnail, players, playing time, age, weight, rating, rank, designers, artists, publishers, categories, mechanics, families, and a BGG link
- **Board Game / Expansion / Accessory Collection blocks**: enter a BGG username → render all owned items of that subtype (thumbnail, title, version, year, rating, status)
- **Board Game / Expansion / Accessory Wishlist blocks**: same shape, scoped to wishlisted items
- **Auto-injected attribution**: a `rdb-tabletop/powered-by` block is appended after every RDB Tabletop block at render time
- **Bearer token auth**: secure server-side calls via `Authorization: Bearer <token>` (per BGG policy)
- **XML → array deserialization**: custom `QueryRunner` converts the BGG XML responses into shapes that Remote Data Blocks' output schema can bind directly
- **HTTP 202 retry**: handles BGG's queued-response pattern transparently
- **Built-in caching**: inherits Remote Data Blocks' object cache (5-minute default TTL)

## Installation

### Manual Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/s3rgiosan/rdb-tabletop/releases/latest).
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require s3rgiosan/rdb-tabletop
```

1. Run `composer install`.
2. Activate the plugin from your WordPress admin area or using WP-CLI.

## Setup

> Before you can fetch any data, BoardGameGeek requires a registered application and a bearer token. Approval may take a week or more, so start here first.

### Account Requirements

- A BoardGameGeek account (free to create at boardgamegeek.com).
- Remote Data Blocks plugin active on the same WordPress site.

### Step 1: Apply for XML API Access

BGG reviews every application manually and approval can take a week or more. See [docs/application-setup.md](docs/application-setup.md) for a field-by-field walk-through with copy-paste-ready answers.

### Step 2: Create a Token

1. Once approved, go back to [https://boardgamegeek.com/applications](https://boardgamegeek.com/applications).
2. Click **Tokens** next to your approved application.
3. Create a token and copy it. It will look like `e3f8c3ff-9926-4efc-863c-3b92acda4d32`.

### Step 3: Configure the Plugin

1. In WordPress admin go to **Settings → RDB Tabletop**.
2. Paste the token into the **Application token** field.
3. Click **Save Changes**.

The plugin now sends `Authorization: Bearer <token>` on every request to `boardgamegeek.com/xmlapi2` (note: no `www.`). You can monitor your request volume on the **Usage** tab of your application on the BGG applications page.

### Step 4: Add the Block

**Board Game block**

1. In the block editor search for "Board Game" and insert the block.
2. Enter a game title to search BGG, then pick a result from the list.
3. Use Remote Data Blocks' binding UI to wire fields (title, image, rating, etc.) into your layout.

**Collection / Wishlist blocks**

1. Search for "Board Game Collection", "Expansion Collection", "Accessory Collection", "Board Game Wishlist", "Expansion Wishlist", or "Accessory Wishlist" and insert the desired block.
2. Enter a BGG username. Collection blocks render all items the user owns (BGG `own=1`); wishlist blocks render all items the user has wishlisted (BGG `wishlist=1`). Both use the same default pattern (thumbnail, title, version, year, rating, status).
3. Customise the layout by editing the inner blocks or registering a replacement pattern (see [Filters](#filters)).

### Step 5: Attribution

BGG policy requires public-facing apps that display BGG data to include the **"Powered by BGG"** logo linking back to `https://boardgamegeek.com`. The plugin handles this automatically: a dedicated `rdb-tabletop/powered-by` block is appended after every RDB Tabletop block at render time via a `render_block` filter. No editor action required, and the markup is not stored in `post_content`.

## License Considerations

BGG classifies applications as either **commercial** or **non-commercial**. Authorization is required in both cases, but the approval path and possible fee differ.

Your plugin install is **commercial** (per BGG's definition) if any of the following are true for the site where it runs:

- The organization running it is for-profit.
- The site is used to raise money in any way.
- The site shows advertising (yes, including AdSense or affiliate links).
- The site offers users a benefit in exchange for payment (memberships, paid features, etc.).

Otherwise it is **non-commercial**. Typical personal blogs that do not show ads or accept donations fall under non-commercial.

Current BGG pricing (summarized from [Using the XML API](https://boardgamegeek.com/using_the_xml_api), subject to change):

- **Non-commercial**: free license.
- **User-payment monetization**: usually free up to 100 paying users.
- **Ad-monetization** (no user payments): usually free up to 1,000 users.
- **Sales-monetization** (online game stores, etc.): paid commercial license usually required up-front. Local brick-and-mortar stores may get a free license.
- **Donation-only**: commercial license required but usually free.

Other policy points you should know:

- **This plugin is a library, not a service.** Each WordPress site installing it registers its own BGG application and uses its own token. You must not share your token across sites you do not control, and you must not repackage this plugin into a hosted service that re-exposes BGG data to other applications — BGG explicitly prohibits that.
- **Server-side only.** All XML API calls happen from the WordPress PHP process. Do not wire the token into any client-side JavaScript.
- **Caching is your friend.** The plugin relies on Remote Data Blocks' built-in object cache. Keep it enabled. BGG can suspend access for applications that generate excessive traffic.
- **Domain matters.** Requests must go to `boardgamegeek.com`, not `www.boardgamegeek.com`. The plugin already uses the correct host.
- **Attribution required.** See Step 5 above.
- **Policies can change.** Check the [BGG applications page](https://boardgamegeek.com/applications) and the [BGG forums](https://boardgamegeek.com/forums) periodically.

If your site is personal/non-commercial at launch but later starts showing ads or taking payment, go back to [https://boardgamegeek.com/applications](https://boardgamegeek.com/applications) and update your application.

## Usage

### Board Game block

Insert → search by title → pick a game. The block exposes fields for title, year, description, image, thumbnail, min/max players, playing time, min age, weight, average rating, rating count, overall rank, designers, artists, publishers, categories, mechanics, families, and the BGG URL.

### Collection blocks (Board Game / Expansion / Accessory Collection)

Insert the relevant block → enter a BGG username → the block fetches all owned items of that subtype (BGG `own=1`) and renders them as a repeating list. Each item exposes: title, year, image, thumbnail, version (name, year, language, publisher), min/max players, playing time, number of plays, user rating, geek rating, comment, status flags, and subtype.

### Wishlist blocks (Board Game / Expansion / Accessory Wishlist)

Same shape and fields as the Collection blocks, but scoped to items the user has wishlisted (BGG `wishlist=1`).

## Filters

### `rdb_tabletop_board_game_patterns`

Swap the default inner block pattern for the Board Game block.

```php
add_filter( 'rdb_tabletop_board_game_patterns', function ( array $patterns ): array {
    $patterns[0]['html'] = file_get_contents( __DIR__ . '/patterns/my-board-game.html' );
    return $patterns;
} );
```

### `rdb_tabletop_{subtype}_{kind}_patterns`

Swap the default inner block pattern for a specific subtype × kind. `{subtype}` is `boardgame`, `boardgameexpansion`, or `boardgameaccessory`. `{kind}` is `collection` or `wishlist`.

```php
add_filter( 'rdb_tabletop_boardgame_collection_patterns', function ( array $patterns ): array {
    $patterns[0]['html'] = file_get_contents( __DIR__ . '/patterns/my-collection.html' );
    return $patterns;
} );

add_filter( 'rdb_tabletop_boardgame_wishlist_patterns', function ( array $patterns ): array {
    $patterns[0]['html'] = file_get_contents( __DIR__ . '/patterns/my-wishlist.html' );
    return $patterns;
} );
```

### `rdb_tabletop_{subtype}_{kind}_query_params`

Add or override query parameters sent to the BGG `/collection` endpoint for a specific subtype × kind. Use this to combine status flags (e.g. owned + wishlisted), apply rating thresholds (`minrating`, `minbggrating`), or scope to recent changes (`modifiedsince`).

```php
// Include both owned and wishlisted items in the board game collection block.
add_filter( 'rdb_tabletop_boardgame_collection_query_params', function ( array $params ): array {
    $params['wishlist'] = '1';
    return $params;
} );

// Restrict the wishlist block to high-priority entries.
add_filter( 'rdb_tabletop_boardgame_wishlist_query_params', function ( array $params ): array {
    $params['wishlistpriority'] = '1';
    return $params;
} );
```

## Requirements

- WordPress 6.7 or higher
- PHP 8.2 or higher
- Remote Data Blocks plugin active
- BoardGameGeek account with an approved XML API application and token

## Changelog

All notable changes to this project are documented in [CHANGELOG.md](https://github.com/s3rgiosan/rdb-tabletop/blob/main/CHANGELOG.md).

## License

This project is licensed under the [GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html).
