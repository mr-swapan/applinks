# Applinks (mr-swapan/applinks)

Redirect users to your mobile app using deep links, with safe fallbacks to Play Store / App Store or a web URL. Includes endpoints for Android Digital Asset Links and Apple App Site Association.

## Requirements

- PHP `>= 8.0`
- Laravel `9.x` / `10.x` / `11.x` / `12.x`

## Install

```bash
composer require mr-swapan/applinks
```

The service provider is auto-discovered by Laravel.

## Publish config & migrations

```bash
php artisan vendor:publish --tag=applinks-config
php artisan vendor:publish --tag=applinks-migrations
php artisan migrate
```

## Configuration

This package reads configuration from `config/applinks.php` (and the corresponding `.env` variables).

### Core

- `APPLINKS_PREFIX` (default: `applinks/*`)
- `APPLINKS_WEB` (default: `true`)
- `APPLINKS_WEB_URL` (default: empty; falls back to your app URL)
- `APPLINKS_DOMAIN` (optional; reserved for your associated domain value)

### Android

- `APPLINKS_ANDROID` (default: `true`)
- `ANDROID_PACKAGE` (example: `com.example.app`)
- `ANDROID_SHA256_CERT_FINGERPRINT` (your app signing SHA-256 fingerprint)
- `ANDROID_PLAYSTORE_URL` (example: `market://details?id=com.example.app`)

### iOS

- `APPLINKS_IOS` (default: `true`)
- `IOS_TEAM_ID` (your Apple Team ID)
- `IOS_PACKAGE` (your app bundle id)
- `IOS_APP_CUSTOM_LINK` (example: `com.example.app://applinks`)
- `IOS_APPSTORE_URL` (your App Store URL)

## What routes does it add?

Routes are loaded from `routes/web.php`:

- `/.well-known/assetlinks.json` and `/assetlinks.json`
- `/.well-known/apple-app-site-association` and `/apple-app-site-association`
- `/applinks/{any?}` (main redirect endpoint)
- `/applinks_save_device_info` (internal helper)
- `/get_app_link_parameters` (returns latest captured query params)
- `/js_editor` (simple JS eval page; do not expose publicly)

## Creating an applinks URL

The package registers a singleton named `applinks` (class: `MrSwapan\Applinks\Services\ApplinksService`).

Example (controller, job, etc.):

```php
$url = app('applinks')->create([
    'campaign' => 'summer',
    'user_id' => 123,
], webUrl: 'https://example.com/fallback');
```

This generates a URL like:

```
https://your-domain.test/applinks?campaign=summer&user_id=123&web_url=https%3A%2F%2Fexample.com%2Ffallback
```

When a user opens that link:

- **Android**: redirects to `ANDROID_PLAYSTORE_URL` and appends `&referrer=<id>` (where `<id>` is stored in `applinks_data`)
- **iOS**: tries to open `IOS_APP_CUSTOM_LINK` with the same query params; after ~30 seconds it falls back to `IOS_APPSTORE_URL`
- **Other devices**: redirects to `web_url` query param, or `APPLINKS_WEB_URL`, or your app URL

## App association files

### Android (Digital Asset Links)

Open `/.well-known/assetlinks.json` on your domain and verify it returns your:

- `ANDROID_PACKAGE`
- `ANDROID_SHA256_CERT_FINGERPRINT`

#### Android app configuration (required)

1. **Add an intent-filter for App Links** (Android 6.0+).

In your `AndroidManifest.xml` (inside the Activity you want to open):

```xml
<activity android:name=".MainActivity">
    <intent-filter android:autoVerify="true">
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />

        <!-- Use your real domain -->
        <data
            android:scheme="https"
            android:host="your-domain.com"
            android:pathPrefix="/applinks" />
    </intent-filter>
</activity>
```

Notes:

- `android:autoVerify="true"` asks Android to verify the domain ownership using `/.well-known/assetlinks.json`.
- `android:pathPrefix="/applinks"` should match your redirect path (default is `/applinks/...`).

2. **Get your signing certificate SHA-256 fingerprint** and set it as `ANDROID_SHA256_CERT_FINGERPRINT`.

Common ways to obtain the SHA-256:

- From Play Console (App Integrity / App signing)
- From your keystore using `keytool` (debug or release keystore)

3. **Ensure HTTPS is used** for your domain and that `/.well-known/assetlinks.json` is reachable publicly.

4. **Handle incoming links in your app**.

When the app is opened by an App Link, it receives the URL via the Activity intent data. Parse the query string to get the parameters you attached (e.g. `campaign`, `user_id`, etc.).

### iOS (Apple App Site Association)

Open `/.well-known/apple-app-site-association` and verify it returns:

- `IOS_TEAM_ID.IOS_PACKAGE`
- an `applinks` component pointing to your `APPLINKS_PREFIX`

#### iOS app configuration (required)

1. **Enable Associated Domains** capability in Xcode.

In your app target:

- Signing & Capabilities → `+ Capability` → **Associated Domains**

2. **Add your applinks domain entry**:

Add an entry like:

- `applinks:your-domain.com`

3. **Ensure `apple-app-site-association` is served correctly**.

This package provides:

- `/.well-known/apple-app-site-association`
- `/apple-app-site-association`

Notes:

- The response must be served over HTTPS.
- Apple expects no redirects for the `/.well-known/...` path.

4. **Handle Universal Links in the app**.

Implement handling for the incoming universal link URL and parse the query parameters you attached.

5. **iOS fallback behavior in this package**

If the request is detected as iOS, this package returns a small HTML page that:

- Attempts to open `IOS_APP_CUSTOM_LINK` with the same query string
- After ~30 seconds falls back to `IOS_APPSTORE_URL`

Set these values:

- `IOS_APP_CUSTOM_LINK` (your app’s custom URL scheme link, example: `com.example.app://applinks`)
- `IOS_APPSTORE_URL` (your App Store page URL)

## License

MIT
