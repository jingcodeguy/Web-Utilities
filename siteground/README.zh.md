# SiteGround Dynamic Cache 清除工具

[English](./README.md)

這是一個用來清除 SiteGround 主機上的 Dynamic Cache 的 PHP 小工具。

它透過 SiteGround 提供的 Unix socket 介面發出清除快取請求，模擬 WordPress 外掛中「清除快取」的功能，但本工具**不依賴 WordPress**，可單獨使用於任何非 WordPress 環境中。

***

## 🔍 為何需要這個工具？

SiteGround 的 Dynamic Cache 設定預設會快取所有頁面，甚至包含 PHP 執行結果與資料庫內容。在未安裝其官方 WordPress 外掛的情況下：

* **即使變更 PHP 程式碼，頁面仍可能顯示舊快取內容**

* **清除快取只能進入後台操作，非常不便**

因此，本工具可作為：

* 本地開發環境的快取測試配合

* 緊急狀況下用 curl / Postman 快速清除快取

* CI/CD 自動部署流程中的一部分

***
## 使用方式：
 **方式1：** 瀏覽器直接存取
```
GET /purge_cache.php
```
**方式2：** 透過 API 呼叫（可加 `url` 參數）
```
GET /purge_cache.php?url=[https://example.com/any-page]()
```
**用自訂 API KEY:**
```
Authorization: Bearer YOUR\_SECRET\_TOKEN
```
**方式3：** 使用 `curl` 指令或腳本整合至自動化流程
```
curl "https://example.com/tools/clear.php?token=xxxxx&url=https://target.com/page"
```
***

## 📎 備註

* 此工具之邏輯**參考自 SiteGround 官方 WordPress Plugin 的快取清除邏輯**，但為獨立簡化版本，未包含其原始碼。

* 僅使用 SiteGround 提供的公用 socket 路徑與呼叫格式，無破解或反編譯行為。

* 請勿用於非授權用途。

***

## 📄 授權方式

請見本目錄下之 [LICENSE](LICENSE.md) 文件。

### 🔖 致謝與來源說明

本工具的原始邏輯參考自 SiteGround 官方提供之 WordPress 插件「Speed Optimizer」中之清除快取機制。原始版本設計為 WordPress 插件使用，本版本僅保留其核心邏輯、去除 UI 與 WordPress 綁定，並以純 PHP 形式重構，方便於非 WordPress 環境中使用。原始工具屬於 SiteGround 所有。

本專案僅作學習與開發用途，無意侵犯任何原著權益，如有侵權請聯絡移除。

