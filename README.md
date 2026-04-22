# RDB Tabletop

> BoardGameGeek data sources for Remote Data Blocks.

## Description

A WordPress plugin that adds BoardGameGeek (BGG) data sources to [Remote Data Blocks](https://wordpress.org/plugins/remote-data-blocks/). Editors can insert a "Board Game" block to search BGG by title and render full game detail, or a "Board Game Collection" block to browse a user's owned games and render detail for picks.

Built on the [BGG XML API2](https://boardgamegeek.com/wiki/page/BGG_XML_API2). Calls are made server-side and authenticated with a BGG application bearer token.

## Features

- **Board Game block**: search BGG → pick → render title, year, description, image, thumbnail, players, playing time, age, weight, rating, rank, designers, artists, publishers, categories, mechanics, families, and a BGG link
- **Board Game Collection block**: enter a BGG username → browse that user's owned games → pick → render detail
- **Bearer token auth**: secure server-side calls via `Authorization: Bearer <token>` (per BGG policy)
- **XML → array deserialization**: custom `QueryRunner` converts the BGG XML responses into shapes that Remote Data Blocks' output schema can bind directly
- **HTTP 202 retry**: handles BGG's queued-response pattern transparently
- **Built-in caching**: inherits Remote Data Blocks' object cache (5-minute default TTL)

## Installation

### Manual Installation

1. Download the plugin ZIP file from the GitHub repository.
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require s3rgiosan/rdb-tabletop
```

2. Run `composer install`.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

## Setup

> Before you can fetch any data, BoardGameGeek requires a registered application and a bearer token. Approval may take a week or more, so start here first.

### Account Requirements

- A BoardGameGeek account (free to create at boardgamegeek.com).
- Remote Data Blocks plugin active on the same WordPress site.

### Step 1: Apply for XML API Access

BGG reviews every application manually and approval can take a week or more.

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

1. In the block editor, search for "Board Game" or "Board Game Collection".
2. Insert the block, enter a search term (game title) or a BGG username, and pick a result.
3. Use Remote Data Blocks' binding UI to wire the selected item's fields (title, image, rating, etc.) into your layout.

### Step 5: Attribution

BGG policy requires public-facing apps that display BGG data to include the **"Powered by BGG"** logo linking back to `https://boardgamegeek.com`. The plugin ships the badge at `assets/powered-by.webp` and embeds it in the default block patterns ("Board Game teaser" and "Board Game Collection item") — when you insert a block and accept the suggested pattern, the attribution is already present. Do not remove it.

If you design a custom template from scratch, add the badge yourself. Use the image at `wp-content/plugins/rdb-tabletop/assets/powered-by.webp` and link it to `https://boardgamegeek.com`.

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
- **Policies can change.** BGG announces changes in the [Geek Tools News forum](https://boardgamegeek.com/forum/1000/bgg/geek-tools-news).

If your site is personal/non-commercial at launch but later starts showing ads or taking payment, go back to [https://boardgamegeek.com/applications](https://boardgamegeek.com/applications) and update your application.

## Usage

### Board Game block

Insert → search by title → pick a game. The block exposes fields for title, year, description, image, thumbnail, min/max players, playing time, min age, weight, average rating, rating count, overall rank, designers, artists, publishers, categories, mechanics, families, and the BGG URL.

### Board Game Collection block

Insert → enter a BGG username → browse the user's owned board games (expansions excluded by default) → pick one to render its full detail.

## Requirements

- WordPress 6.7 or higher
- PHP 8.2 or higher
- Remote Data Blocks plugin active
- BoardGameGeek account with an approved XML API application and token

## Changelog

All notable changes to this project are documented in [CHANGELOG.md](https://github.com/s3rgiosan/rdb-tabletop/blob/main/CHANGELOG.md).

## License

This project is licensed under the [GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html).
