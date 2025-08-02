# A Simple Dynamic Cache Flush Tool (SiteGround Compatible)

[繁體中文](./README.zh.md)

This is a standalone PHP utility that connects to a SiteGround-style local socket and clears the dynamic cache for a specified URL.

## 💡 Why this tool?

SiteGround's default configuration aggressively caches even dynamic content like PHP responses and MySQL-driven pages.  
If you’re **not using SiteGround's official WordPress plugin**, clearing the dynamic cache becomes inconvenient — it requires logging into the control panel manually.

This tool lets you **flush the cache remotely** via a URL call or API client (e.g. `curl`, Postman).  
It’s useful for testing, emergency fixes, or automation scripts.

## 🚀 Features

- 🔄 Flush dynamic cache via internal Unix socket
- 🌐 Auto-detect current URL, or accept a `?url=` parameter
- 🔒 Supports optional token-based authentication (via `Authorization: Bearer`)
- 📝 Records flush results to `flush.log`

## 🔧 How to Use

**Option 1:** Flush current page cache
```
GET /purge_cache.php
```
**Option 2:** Flush a specific URL
```
GET /purge_cache.php?url=[https://example.com/any-page]()
```
**With Token:**
```
Authorization: Bearer YOUR\_SECRET\_TOKEN
```
**Option 3:** Flushing using `curl` from command line or script for automation
```
curl "https://example.com/tools/clear.php?token=xxxxx&url=https://target.com/page"
```

## ⚙️ Setup

1. Make sure your SiteGround server exposes the socket (e.g. `/chroot/tmp/site-tools.sock`)
2. Edit `purge_cache.php` to match your environment path if needed
3. Place `purge_cache.php` on your server in a web-accessible path
4. (Optional) Set your own secret token inside the file

## 📜 Disclaimer

This is a utility script provided "as-is". Use with care.  
Ensure your socket path is secured and not exposed to unauthorized users.  
You are responsible for securing the endpoint (e.g., IP whitelisting, firewall, etc.)

## 🪪 License

MIT License — See [LICENSE](./LICENSE.md)

### 🔖 Acknowledgements & Origin Reference

This tool’s core logic is based on the cache clearing mechanism found in SiteGround’s official WordPress plugin “Speed Optimizer.” The original plugin was designed for WordPress; this version extracts and refactors its core logic into a barebone PHP utility, removing UI and WordPress dependency, for usage in non-WordPress environments. Original tool copyright belongs to SiteGround.

This project is intended for educational and development use only. No infringement is intended. Please contact us for takedown requests if necessary.




