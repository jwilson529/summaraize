=== SummarAIze - AI-Powered Key Takeaways for WordPress ===
Contributors: jwilson529
Donate link: https://oneclickcontent.com/donate/
Tags: ai, summary, content-enhancement, engagement, OpenAI, Google Gemini
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.1.14
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# SummarAIze - AI-Powered Key Takeaways for WordPress

## Description

SummarAIze transforms your WordPress content into concise, engaging summaries with the power of AI. It generates **five key takeaways** to enhance readability, improve engagement, and make your content more digestible.

Whether you're a blogger, marketer, or news publisher, SummarAIze helps your audience quickly grasp the essence of your posts, boosting retention and usability.

### Key Features

- **AI-Powered Summaries**: Automatically extract the top 5 takeaways from your content.
- **Flexible Display Options**: Choose list styles, display positions, or a modal popup for summaries.
- **Drag-and-Drop Reordering**: Effortlessly rearrange key points in the admin interface.
- **Shortcode Support**: Customize how and where summaries are displayed using attributes.
- **Multi-Provider Support**: Works seamlessly with both OpenAI and Google Gemini APIs.
- **SEO Boost**: Improve search visibility with structured, concise summaries.
- **Privacy Awareness**: Clearly inform users about AI API usage and data handling.

### Important Information

SummarAIze supports both **OpenAI** and **Google Gemini** APIs for generating summaries. Data from your site is sent to the selected API provider's servers for processing. By using this plugin, you agree to their respective [Terms of Use](https://openai.com/terms) and [Privacy Policy](https://openai.com/privacy) or [Google Gemini Privacy Policy](https://policies.google.com/privacy).

---

## Shortcode Documentation

The `[summaraize]` shortcode allows you to display key takeaways with customizable options.

### Basic Usage:
`[summaraize]`

### Attributes:

- **`id`**: Specify the post ID for the key points. Default: current post ID.  
  Example: `[summaraize id="123"]`

- **`view`**: Set the display method (`above`, `below`, or `popup`). Default: `above`.  
  Example: `[summaraize view="popup"]`

- **`list_type`**: Display as `ordered` (`<ol>`) or `unordered` (`<ul>`). Default: `unordered`.  
  Example: `[summaraize list_type="ordered"]`

- **`mode`**: Choose between `light` and `dark` themes. Default: `light`.  
  Example: `[summaraize mode="dark"]`

- **`button_style`**: Customize the popup button (`flat`, `rounded`, etc.). Default: `flat`.  
  Example: `[summaraize view="popup" button_style="rounded"]`

- **`title`**: Add a custom title. Default: "Key Takeaways."  
  Example: `[summaraize title="Quick Summary"]`

---

## Installation

1. **Upload**: Upload to `/wp-content/plugins/` or install directly via the WordPress plugin screen.
2. **Activate**: Activate via the 'Plugins' screen.
3. **Configure**: Go to Settings > SummarAIze and enter your OpenAI or Google Gemini API key.

---

## Privacy

SummarAIze sends the necessary content to the selected AI provider (OpenAI or Google Gemini) for generating summaries. No personal data or sensitive information is transmitted. Please review the privacy policies of [OpenAI](https://openai.com/privacy) and [Google Gemini](https://policies.google.com/privacy) for more details.

---

## Frequently Asked Questions

### Do I need an API key?
Yes. SummarAIze requires an API key to connect to either OpenAI or Google Gemini.

### Can I customize how the key points appear?
Absolutely! Use the plugin's settings or shortcode attributes to tailor the display to your needs.

### Are there costs for using SummarAIze?
The plugin is free, but API usage (OpenAI or Google Gemini) may incur costs based on their pricing.

### How do I switch between OpenAI and Google Gemini?
Navigate to the plugin's settings page and select your preferred provider under **AI Provider**. Enter the corresponding API key for the selected provider.

---

## Screenshots

1. **Above or Below Content**: Flexible display options for key points.
2. **Dark Mode**: A sleek display for enhanced readability.
3. **Popup View**: Summaries in a modal popup.
4. **Drag-and-Drop Admin Interface**: Easy reordering of key points.
5. **Settings Page**: Configure your API provider and key.

---

## Changelog

### 1.1.14
- Added support for Google Gemini API.
- Fixed metabox saving issue.
- Improved UI for key point inputs.
- Bug fixes and optimizations.

### 1.1.11
- Added drag-and-drop functionality for reordering.
- Auto-remove empty points from front-end display.
- Enhanced meta box save behavior.

### 1.1.0
- Dynamic Assistant creation with the OpenAI API.

### 1.0.0
- Initial release.

---

## Future Plans

- Support for more AI providers.
- Advanced analytics for key point engagement.
- Expanded customization options.

---

## License

This plugin is licensed under GPLv2 or later.