=== SummarAIze – Automatically create TL;DRs for your posts ===
Contributors: jwilson529
Tags: ai, summary, openai, google gemini, seo
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.2.6
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered post summaries using OpenAI or Google Gemini. Instantly boost engagement, SEO, and readability with smart key takeaways.

== Description ==

SummarAIze is the *secret weapon* you need. This powerful, yet incredibly easy-to-use plugin leverages cutting-edge AI from **OpenAI** or **Google Gemini** to automatically generate concise, engaging summaries (key takeaways) for your posts and pages.

**Here's how SummarAIze will transform your WordPress site:**

* **Boost Reader Engagement** – Hook readers instantly with clear, concise summaries that highlight your content’s core ideas.
* **Improve Readability** – Make complex topics easier to digest, increasing time on site and lowering bounce rates.
* **Enhance SEO** – Structured, keyword-rich summaries help search engines understand and rank your content more effectively.
* **Save Time** – Stop manually writing summaries! Let AI handle it while you focus on content.
* **Choose Your AI Powerhouse** – Use OpenAI or Google Gemini — switch anytime.

== Key Features ==

* **Dual AI Engines** – OpenAI *or* Google Gemini support. Toggle in settings with your preferred API key.
* **Automatic Summary Generation** – Generates the top 5 key takeaways for each post or page.
* **Flexible Display Options** – Show summaries above content, below, or in a slick popup.
* **Drag-and-Drop Simplicity** – Easily reorder points with a visual interface.
* **Customizable Appearance** – Choose list style (ordered/unordered), dark or light mode, and popup styles via shortcode.
* **SEO Friendly** – Outputs structured HTML for better indexing.
* **Privacy Conscious** – Sends only content (not personal data) to AI providers for processing.

== Important Information ==

SummarAIze uses either the **OpenAI API** or the **Google Gemini API** for summary generation. This requires sending your post content to the selected provider’s servers. By using this plugin, you agree to their respective terms and policies:

* **OpenAI:** [Terms of Use](https://openai.com/terms), [Privacy Policy](https://openai.com/privacy)
* **Google Gemini:** [Privacy Policy](https://policies.google.com/privacy), [Terms of Service](https://policies.google.com/terms)

== Installation ==

1. **Upload:** Upload the `summaraize` folder to `/wp-content/plugins/`, or install via Plugins > Add New in WordPress.
2. **Activate:** Activate the plugin through the 'Plugins' menu.
3. **Configure:** Go to Settings > SummarAIze, select your AI provider, and enter your API key.

== Frequently Asked Questions ==

= Do I need an API key? =
Yes. You’ll need an API key from either OpenAI or Google Gemini. Both offer free tiers, though high usage may require a paid account.

= Can I customize the summaries? =
Absolutely. You can edit the generated key takeaways in the editor and use shortcodes to change the display.

= How much does SummarAIze cost? =
The plugin is 100% free and open-source. However, API usage with OpenAI or Gemini may incur charges based on their pricing.

= How do I switch between OpenAI and Gemini? =
Go to Settings > SummarAIze, select your provider, and enter your API key. Switching is instant.

= Is my data safe? =
SummarAIze only sends post content to the AI provider for summary generation. No personal or user data is sent.

== Screenshots ==

1. Summaries shown above content. (`screenshot-1.png`)
2. Dark mode summary display. (`screenshot-2.png`)
3. Popup summary presentation. (`screenshot-3.png`)
4. Drag-and-drop UI to reorder takeaways. (`screenshot-4.png`)
5. Plugin settings screen. (`screenshot-5.png`)

== Changelog ==


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

= 1.2.6 =
**Language consistency.** Summaries now explicitly follow the source language for Gemini and OpenAI.

= 1.2.5 =
**Centralized OpenAI defaults.** OpenAI endpoints and default model are now set via constants so you can update them in one place.

= 1.2.3 =
**Improved compatibility and summary rendering.** This update includes bug fixes, sanitization improvements, and better support for WordPress 6.7. Update now for the best performance.

== Other Notes ==
**Ready to transform your WordPress content and boost engagement? Install SummarAIze today!**
