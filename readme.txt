=== SummarAIze ===
Contributors: jwilson529
Tags: ai, summary, openai, gemini, tldr
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.5.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publisher-controlled AI key takeaways for WordPress. Bring your own API key, edit summaries, and bulk-manage them at scale.

== Description ==

SummarAIze is a free WordPress plugin for publishers who want editor-approved AI key takeaways across their site, using their own OpenAI, Google Gemini, or OpenRouter API key.

It is built as a publishing workflow, not a hosted summary subscription. You connect your own provider account, choose the model, review the output, and control the cost. SummarAIze does not bundle AI credits or route requests through a hosted SummarAIze service.

Use SummarAIze when you need to keep summaries accurate, editable, and current across more than one post:

* Bring your own OpenAI, Google Gemini, or OpenRouter API key
* Choose supported OpenAI models including GPT-6 Astra, GPT-5.5, GPT-5, GPT-5 mini, and GPT-5 nano
* Generate scannable key takeaways for posts and pages
* Edit and reorder takeaway points before publishing
* Track whether summaries are missing, current, stale, or manually edited
* Generate missing summaries in bulk and optionally auto-generate on first publish
* Display summaries above content, below content, or in a popup
* Customize output with shortcode attributes for title, mode, list type, and popup button styling
* Use the plugin for free, with no SummarAIze usage quota or summary subscription

== OpenRouter ==

Choose OpenRouter under AI Provider and enter your API key. Click Load models, filter by provider or model name, and choose a result. You can also enter an exact provider/model ID manually.

Test model generates five sample takeaways using the same response validation as real summaries and may incur a small API charge. Review the preview and the Untested, Test passed, or Test failed badge. Test results apply to the tested key/model pair for seven days. Changing the key or model requires another test. A successful sample does not guarantee every article will succeed.

Click Save Changes to retain the key and model before generating post summaries. Searching or testing alone does not save these settings.

== Upgrading to 1.5.0 ==

Existing provider selections, API keys, OpenAI model choices, display settings, and saved summaries are retained. OpenRouter is optional. Switching providers preserves inactive providers' credentials. GPT-5 mini remains the default for new OpenAI configurations; GPT-6 Astra is optional.

Gemini now uses Gemini 3.5 Flash-Lite because the previously hard-coded model is unavailable to new users. Key validation no longer generates billable content. The upgrade does not regenerate existing summaries.

== Privacy ==

SummarAIze sends post content to the AI provider you configure so it can generate takeaways. The plugin does not proxy requests through a third-party SummarAIze service, and generated takeaways remain editable in WordPress before publishing.

* OpenAI: [Terms of Use](https://openai.com/terms), [Privacy Policy](https://openai.com/privacy)
* OpenRouter (when selected): Post content is sent through OpenRouter to the model provider. Account privacy and routing settings apply. [Terms of Service](https://openrouter.ai/terms), [Privacy Policy](https://openrouter.ai/privacy)
* Google Gemini: [Terms of Service](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy)

== Installation ==

1. Upload the `summaraize` folder to `/wp-content/plugins/`, or install the plugin through Plugins > Add New in WordPress.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to Settings > SummarAIze.
4. Select OpenAI, Google Gemini, or OpenRouter and enter your API key.
5. Open a post or page, generate takeaways, then edit or reorder them before publishing.
6. Optionally use bulk actions or publish-time automation to keep summaries current across your site.

== Shortcode ==

Basic usage:

`[summaraize]`

Supported attributes:

* `id` - Display takeaways for a specific post ID. Defaults to the current post.
* `view` - `above`, `below`, or `popup`. Defaults to `above`.
* `mode` - `light` or `dark`. Defaults to `light`.
* `title` - Custom title text for the takeaway box.
* `button_style` - Popup button style when `view="popup"`.
* `button_color` - Popup button color when `view="popup"`.
* `list_type` - `ordered` or `unordered`. Defaults to `unordered`.

== Frequently Asked Questions ==

= Do I need an API key? =
Yes. You need an API key from OpenAI, Google Gemini, or OpenRouter. Model availability and usage charges depend on your provider and account.

= Does SummarAIze include AI credits or a hosted API service? =
No. SummarAIze is a free, bring-your-own-key plugin. You bring your own provider account and pay OpenAI or Google directly for any API usage. There is no SummarAIze-hosted relay and no monthly summary quota from SummarAIze.

= Can I customize the summaries? =
Yes. You can edit and reorder the generated key takeaways in the editor, then use shortcodes to control where and how they display.

= Can I generate summaries in bulk? =
Yes. SummarAIze adds bulk actions to supported post type list screens so publishers can generate missing summaries or explicitly regenerate existing ones across a larger content library.

= Will auto-generation overwrite my edited summaries? =
No. The publish-time automation mode only generates a summary when one is missing. Manual edits are treated as user-managed.

= How much does SummarAIze cost? =
The plugin is free and open-source. API usage with OpenAI or Gemini may incur charges based on your provider plan.

= How do I switch between OpenAI and Gemini? =
Go to Settings > SummarAIze, select your provider, and enter your API key. Switching is instant.

= Is my data safe? =
SummarAIze sends post content to the AI provider you configure for summary generation. No hosted SummarAIze relay is involved.

== Screenshots ==

1. Configure whether takeaways appear above or below content.
2. Show the front-end takeaway box in dark mode.
3. Render the summary in a popup layout.
4. Reorder takeaway points before publishing.

== Changelog ==

= 1.5.0 =
* Replaced the OpenRouter autocomplete with separate catalog search and selection, preserving manual model IDs and test status while browsing.
* Added persistent OpenRouter model-test badges, five-takeaway previews, and clearer provider failure messages. Test status is tied to the key/model pair and expires after seven days.
* Added GPT-6 Astra (gpt-6-astra) to the OpenAI model selector with compatible reasoning parameters; GPT-5 mini remains the default.
* Updated Gemini to gemini-3.5-flash-lite and fixed valid keys being rejected when the old generation model is unavailable.
* Gemini key validation now uses a read-only request and distinguishes model access, quota, and authentication failures.
* Fixed API-key validation/save timing and refreshed the OpenAI model cache.
* Added OpenRouter support for Claude and other text models using your own API key.
* Added searchable model discovery, manual model IDs, and an explicit connection test.
* Added strict validation of five takeaways and actionable OpenRouter API errors.
* Preserved inactive provider credentials when saving settings.
* Restored the Save Changes button for explicit credential and model saving.
* Added OpenRouter support to editor, bulk, and publish-time summary workflows.


= 1.4.6 =
* Updated WordPress Coding Standards to 3.4.1 to address CVE-2026-45293 in development and automated code checks.
* Updated PHP_CodeSniffer to 3.13.6 or later for CVE-2026-67434 and added dependency auditing to CI.
* Removed generated WordPress test caches from source control; integration tests download fresh copies.

= 1.4.5 =
* Rendered the SummarAIze widget title as paragraph text by default so table-of-contents plugins do not treat Key Takeaways as a content heading.
* Added a `summaraize_widget_title_tag` filter for sites that need to opt back into a heading tag.

= 1.4.4 =
* Tested compatibility against WordPress 7.0.
* Corrected WordPress.org screenshot caption ordering after the screenshot asset cleanup.

= 1.4.3 =
* Restored captions for the remaining safe screenshot assets after removing the unwanted cropped screenshots.

= 1.4.2 =
* Removed two screenshot assets that included unwanted menu cropping.

= 1.4.1 =
* Added OpenAI GPT-5.5 and `gpt-5.5-2026-04-23` model support.
* Updated OpenAI settings guidance to call out GPT-5.5 as the latest flagship option while keeping GPT-5 mini as the cost-conscious default.
* Expanded OpenAI model tests for GPT-5.5 sanitization, fallback ordering, and request payload handling.

= 1.4.0 =
* Added summary lifecycle tracking for missing, current, stale, and manually edited summaries.
* Added bulk generation and regeneration actions in supported post type list screens.
* Added optional auto-generate-on-publish for posts that do not already have a summary.
* Added summary provenance details in the editor so users can see provider, model, and generation time.

= 1.3.0 =
* Expanded OpenAI support to newer GPT-5 and reasoning-capable models.
* Improved parsing and fallback handling for AI-generated takeaway points.
* Increased API timeout tolerance for more complex model responses.
* Proper cleanup of plugin settings and metadata on uninstall.

= 1.2.6 =
* Ensured summaries stay in the same language as the source content for both Gemini and OpenAI.

= 1.2.5 =
* Centralized OpenAI endpoints and default model into constants for easier updates.
* Updated docs to reflect configurable OpenAI model and endpoint values.

= 1.2.4 =
* Fixed autosave in the settings page for the post types section.
* Fixed spinner message placement in the settings page for the post types section.

= 1.2.3 =
* Improved compatibility with WordPress 6.7.
* Enhanced sanitization of key takeaway input.
* Fixed link rendering in summary points.

= 1.2.1 =
* Updated URL for Google Gemini.

= 1.2.0 =
* **Major Feature:** Added full support for the Google Gemini API.
* Updated documentation and settings to support Gemini.
* Improved error handling for API failures.
* General performance and code cleanup.

= 1.1.16 =
* Fixed meta box saving behavior.
* Improved key point UI.
* Minor bug fixes and polish.

= 1.1.11 =
* Added drag-and-drop ordering for key points.
* Auto-removal of empty entries.
* Optimized save handling.

= 1.1.0 =
* Initial dynamic Assistant integration with OpenAI API.

= 1.0.0 =
* Initial public release.

== Upgrade Notice ==

= 1.5.0 =
Adds OpenRouter with model compatibility tests and sample previews, GPT-6 Astra support, and updated Gemini support. Existing provider selections remain in place.

= 1.4.6 =
Maintenance release updating the development security checks.

= 1.4.5 =
**Table of contents compatibility.** The widget title now renders as paragraph text by default so generated summary labels do not appear in content heading lists.

= 1.4.4 =
**WordPress 7.0 support.** Tested against WordPress 7.0 and corrected screenshot captions for the remaining safe assets.

= 1.4.3 =
**Screenshot captions.** Remaining safe screenshots now display with their correct captions after the screenshot cleanup.

= 1.4.2 =
**Screenshot cleanup.** This release removes two screenshots that included unwanted menu cropping.

= 1.4.1 =
**GPT-5.5 support.** OpenAI users can now select GPT-5.5 when it is available to their account, while GPT-5 mini remains the default model.

= 1.4.0 =
**Workflow automation.** This release adds summary status tracking, bulk generation tools, and an optional first-publish auto-generate mode without changing how existing summaries render.

= 1.3.0 =
Enhanced OpenAI support, better parsing reliability, and cleaner uninstall behavior.

= 1.2.6 =
**Language consistency.** Summaries now explicitly follow the source language for Gemini and OpenAI.

= 1.2.5 =
**Centralized OpenAI defaults.** OpenAI endpoints and default model are now set via constants so you can update them in one place.

= 1.2.3 =
**Improved compatibility and summary rendering.** This update includes bug fixes, sanitization improvements, and better support for WordPress 6.7. Update now for the best performance.

== Other Notes ==
SummarAIze is designed as a free, bring-your-own-key key takeaways workflow for WordPress publishers who want control over provider choice, API spend, and the final published summary.
