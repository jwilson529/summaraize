=== SummarAIze ===
Contributors: jwilson529
Tags: ai, summary, openai, gemini, tldr
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.3.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free bring-your-own-key AI summaries for WordPress using OpenAI or Google Gemini.

== Description ==

SummarAIze is a free WordPress plugin that generates editable key takeaways for posts and pages using your own OpenAI or Google Gemini API key.

This plugin does not bundle AI credits or route requests through a hosted SummarAIze service. You connect your own provider account, choose the model, and control the cost.

SummarAIze is built for publishers who want a lightweight summary workflow:

* Bring your own OpenAI or Google Gemini API key
* Generate the top 5 key takeaways for posts and pages
* Edit and reorder takeaway points before publishing
* Display summaries above content, below content, or in a popup
* Customize output with shortcode attributes for title, mode, list type, and popup button styling
* Use the plugin for free, with API usage billed only by your chosen provider

== Privacy ==

SummarAIze sends post content to the AI provider you configure so it can generate takeaways. The plugin does not proxy requests through a third-party SummarAIze service.

* OpenAI: [Terms of Use](https://openai.com/terms), [Privacy Policy](https://openai.com/privacy)
* Google Gemini: [Terms of Service](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy)

== Installation ==

1. Upload the `summaraize` folder to `/wp-content/plugins/`, or install the plugin through Plugins > Add New in WordPress.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to Settings > SummarAIze.
4. Select OpenAI or Google Gemini and enter your API key.
5. Open a post or page, generate takeaways, then edit or reorder them before publishing.

== Shortcode ==

Basic usage:

`[summaraize]`

Supported attributes:

* `id` - Display takeaways for a specific post ID. Defaults to the current post.
* `view` - `above`, `below`, or `popup`. Defaults to `above`.
* `mode` - `light` or `dark`. Defaults to `light`.
* `title` - Custom heading for the takeaway box.
* `button_style` - Popup button style when `view="popup"`.
* `button_color` - Popup button color when `view="popup"`.
* `list_type` - `ordered` or `unordered`. Defaults to `unordered`.

== Frequently Asked Questions ==

= Do I need an API key? =
Yes. You’ll need an API key from either OpenAI or Google Gemini. Both offer free tiers, though high usage may require a paid account.

= Does SummarAIze include AI credits or a hosted API service? =
No. SummarAIze is a free plugin. You bring your own provider account and pay OpenAI or Google directly for any API usage.

= Can I customize the summaries? =
Absolutely. You can edit the generated key takeaways in the editor and use shortcodes to change the display.

= How much does SummarAIze cost? =
The plugin is free and open-source. API usage with OpenAI or Gemini may incur charges based on your provider plan.

= How do I switch between OpenAI and Gemini? =
Go to Settings > SummarAIze, select your provider, and enter your API key. Switching is instant.

= Is my data safe? =
SummarAIze sends post content to the AI provider you configure for summary generation. No hosted SummarAIze relay is involved.

== Screenshots ==

1. Configure whether takeaways appear above or below content.
2. Show the front-end takeaway box in dark mode.
3. Generate and refine takeaways in the classic editor meta box.
4. Render the summary in a popup layout.
5. Configure providers, API keys, and display defaults.
6. Reorder takeaway points before publishing.

== Changelog ==

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

= 1.3.0 =
Enhanced OpenAI support, better parsing reliability, and cleaner uninstall behavior.

= 1.2.6 =
**Language consistency.** Summaries now explicitly follow the source language for Gemini and OpenAI.

= 1.2.5 =
**Centralized OpenAI defaults.** OpenAI endpoints and default model are now set via constants so you can update them in one place.

= 1.2.3 =
**Improved compatibility and summary rendering.** This update includes bug fixes, sanitization improvements, and better support for WordPress 6.7. Update now for the best performance.

== Other Notes ==
SummarAIze is designed as a free, bring-your-own-key summary plugin for WordPress publishers who want control over provider choice and API spend.
