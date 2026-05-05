# Registering Your BGG XML API Application

This guide walks through the one-time BoardGameGeek application and token setup
required before the plugin can fetch any data.

> BGG requires every application to be reviewed by a human. Approval can take a
> week or more, so start this before you expect to build pages with the blocks.

## Prerequisites

- A BoardGameGeek account (free, at `https://boardgamegeek.com`).
- The URL of the site where this plugin will run (e.g. `https://your-domain.com`).
- An email address you actively monitor — BGG may reply there.
- 10 minutes to fill the form.

Before starting, read the policy once:
**<https://boardgamegeek.com/using_the_xml_api>**

The application form references it, and reviewers expect applicants to know it.

---

## Step 1 — Open the Application Form

1. Log in at [Board Game Geek](https://boardgamegeek.com).
2. Go to **<https://boardgamegeek.com/applications>**.
3. Click **Create an application**.

---

## Step 2 — Fill the Form

All fields are required unless noted otherwise. Recommended answers below
assume a personal, non-commercial WordPress site. Adapt to your situation.

### Application name

A short, descriptive name. Include your site domain so BGG can distinguish
multiple applications if you register more later.

```
RDB Tabletop – your-domain.com
```

### Your full legal name

Your real name.

```
Sérgio Santos
```

### Your organization's name, if applicable

Leave blank for a personal site. Fill only if the site is run by a registered
business, non-profit, or similar.

### Your organization's website

The URL of the site that will run the plugin.

```
https://your-domain.com
```

### Your organization's location (City/town)

Type your city in the search box and pick it from the list.

### Contact email

Use an address you actually check. BGG's review emails can end up in spam —
watch for them.

### Describe your organization's (or your own) activities as they relate to BoardGameGeek and your use of the XML API

A short paragraph in plain language. Something like:

> I am a board game hobbyist. I maintain a personal WordPress site where I
> write about games I own and play. I want to use the XML API to embed board
> game information (title, year, description, rating, players, playing time,
> designers, categories, etc.) directly in my posts, pulled from BGG so the
> data stays accurate and up to date.

### Detailed description of your application(s), and how you will use our API

Non-technical but specific. Mention:

- What the application is (a WordPress plugin, built on Remote Data Blocks).
- Which XML API endpoints you call (`/search`, `/thing`, `/collection`).
- Where the requests originate (server-side from WordPress PHP).
- That responses are cached.
- That this is a library — every site that installs it registers its own
  application and uses its own token (BGG explicitly asks libraries to do
  this).

Example:

> The application is a WordPress plugin that extends the Remote Data Blocks
> plugin with four blocks: "Board Game" (renders a single game's detail,
> selected by title search) and three collection blocks — "Board Game
> Collection", "Expansion Collection", and "Accessory Collection" — each of
> which takes a BGG username and renders all owned items of that subtype as a
> list. All calls are made server-side from WordPress PHP to
> `boardgamegeek.com/xmlapi2`, hitting the `/search`, `/thing`, and
> `/collection` endpoints. Responses are cached for several minutes via
> WordPress's object cache, so a page view does not necessarily translate to a
> BGG request. The plugin is open source (GPL-3.0) at
> `https://github.com/s3rgiosan/rdb-tabletop`; each installation registers its
> own BGG application and uses its own token.

### Is your application available to the public?

**Yes** if any of the following are true:

- The source code is in a public repository (GitHub, GitLab, etc.).
- The site that renders the blocks is public-facing.
- You let other people use it (distribute, share, deploy elsewhere).

Answer **No** only if you are the only user and the code is private.

### Your API client(s), comma separated list, if any

The public URLs where the data will be displayed. For a single site:

```
https://your-domain.com
```

For multiple: `https://site-a.com, https://site-b.com`.

### Is your endeavor commercial in nature?

Per BGG, an application is commercial if **any** of the following are true
for the site or organization running it:

- For-profit organization.
- Used to raise money in any way (including donations tied to benefits).
- Shows advertising (AdSense, affiliate links, sponsored posts).
- Offers users paid features, memberships, or any benefit for payment.

Otherwise it is **non-commercial** and the license is usually free.

If you are unsure, answer honestly — BGG can downgrade or refund a
classification more easily than un-revoke a license obtained by misrepresenting
the site.

### Please add any other information you think would be useful…

Use this to link the source repo and call out anything a reviewer might
question. Example:

> Source code: <https://github.com/s3rgiosan/rdb-tabletop>. The plugin is distributed
> as a library — each installation registers its own BGG application and uses
> its own token, per BGG's policy on third-party libraries. All requests are
> server-side and cached. The plugin ships a "Powered by BGG" attribution
> badge that site owners include on pages rendering the blocks.

### Check to indicate that you agree to the API Terms of Use

Open the link, read it, check the box.

---

## Step 3 — Submit and Wait

1. Click **Submit for evaluation**.
2. You will receive email confirmation. Approval can take a week or more.
3. If BGG emails follow-up questions, reply promptly — unanswered requests get
   abandoned.

---

## Step 4 — Create a Token

Once approved:

1. Return to `https://boardgamegeek.com/applications`.
2. Your application now has a **Tokens** button next to it. Click it.
3. Click **Create a token**. Copy the generated token (looks like
   `e3f8c3ff-9926-4efc-863c-3b92acda4d32`).
4. Treat the token like a password — do not commit it to a repo or paste it
   into client-side code.

---

## Step 5 — Paste the Token Into the Plugin

1. In WordPress admin go to **Settings → RDB Tabletop**.
2. Paste the token into the **Application token** field.
3. Click **Save Changes**.

The plugin will now send `Authorization: Bearer <token>` on every request to
`boardgamegeek.com/xmlapi2` (note: no `www.`).

---

## Monitoring and Maintenance

- **Usage dashboard**: `https://boardgamegeek.com/applications` → your app →
  **Usage** tab. Shows your request volume against BGG's (currently unpublished)
  limits.
- **Policy updates**: check the [BGG forums](https://boardgamegeek.com/forums) periodically.
- **License changes**: if your site's monetization changes (adds ads, starts a
  paid tier, etc.), go back to the applications page and update your
  classification.
- **Lost token**: regenerate from the Tokens panel. Update the plugin
  settings with the new value.

---

## Troubleshooting

**"Unauthorized" responses on every request**

Token is wrong, expired (unlikely — BGG currently does not expire them, but
they reserve the right), or the site is calling `www.boardgamegeek.com`
instead of `boardgamegeek.com`. Double-check the token in **Settings → RDB
Tabletop**.

**HTTP 202 errors**

BGG queues some `/thing` and `/collection` responses. The plugin already
retries up to 5 times with a 2-second delay. If they still fail, the target
data was unusually slow — try again in a minute.

**Request volume warnings from BGG**

Make sure the WordPress object cache is enabled. Lower page traffic to BGG
endpoints by caching page output as well (page caching plugin, Varnish, CDN).
