# SummarAIze - Generate Key Takeaways with AI

![Plugin Banner](assets/banner-772x250.png)

## Description

**SummarAIze** is a powerful WordPress plugin designed to automatically generate and display the top 5 key points from your posts. With AI-driven content summarization, this plugin enhances your articles by providing readers with quick and engaging takeaways, making your content more accessible and easier to digest.

Whether you're a blogger looking to highlight essential points or a marketer wanting to ensure your audience gets the most out of your content, SummarAIze helps improve user engagement and retention by providing concise, easily digestible summaries.

### Important Information

SummarAIze relies on the OpenAI API to generate key takeaways. This means that data from your site will be sent to OpenAI's servers for processing, and results will be returned to your site. By using this plugin, you agree to OpenAI's [Terms of Use](https://openai.com/terms) and [Privacy Policy](https://openai.com/privacy).

## Features

- **AI-Powered Content Summarization**: Automatically generate top 5 key points from post content using AI.
- **Customizable Display Options**: Display key points in various styles and positions to suit your theme.
- **Ordered or Unordered Lists**: Choose between an ordered list (numbered) or an unordered list (bulleted) for displaying key points.
- **User-Friendly Interface**: Easily manage and customize key points for each post or page.
- **Flexible API Integration**: Integrate with your own OpenAI API key, allowing for control over usage and billing.
- **Assistant Configuration**: Use the default Assistant ID or configure your own in the OpenAI Playground.
- **Enhance SEO and Readability**: Improve your content’s SEO by providing search engines with structured summaries, and enhance readability for your audience.

## Drag-and-Drop Feature

You can now easily reorder your points using the drag-and-drop functionality:

1. Hover over the point you want to reorder.
2. Click and hold the "menu" icon (represented by the three lines) to the left of the point.
3. Drag the point to your desired position in the list.
4. The new order is automatically updated and saved when you publish or update the post.

Empty points will be removed from the front-end display automatically, ensuring only meaningful points are shown to your users.

## Shortcode Documentation

### Basic Shortcode Usage:

`[summaraize]`

### Available Attributes:

- **`id`** *(optional)*:  
  The post ID for which to display the key points. Default: current post ID.  
  Example: `[summaraize id="123"]`

- **`view`** *(optional)*:  
  Defines where the output should be positioned relative to the post content.  
  Possible values: `popup`, `above`, `below`.  
  Default: `above`.  
  Example: `[summaraize view="popup"]`

- **`mode`** *(optional)*:  
  Sets the display mode for light or dark themes.  
  Possible values: `light`, `dark`.  
  Default: `light`.  
  Example: `[summaraize mode="dark"]`

- **`title`** *(optional)*:  
  Sets a custom title for the key points widget or popup.  
  Default: "Key Takeaways".  
  Example: `[summaraize title="Quick Summary"]`

- **`button_style`** *(optional)*:  
  Defines the popup button style.  
  Default: `flat`.  
  Example: `[summaraize view="popup" button_style="rounded"]`

- **`button_color`** *(optional)*:  
  Sets the background color of the popup button.  
  Default: `#0073aa`.  
  Example: `[summaraize view="popup" button_color="#ff0000"]`

- **`list_type`** *(optional)*:  
  Specifies how the key points list is displayed.  
  Possible values: `ordered`, `unordered`.  
  Default: `unordered`.  
  Example: `[summaraize list_type="ordered"]`

### Example Usage:

- Basic Usage: `[summaraize]`
- Popup Style: `[summaraize view="popup" button_style="rounded" button_color="#ff0000"]`
- Dark Mode with Custom Title: `[summaraize mode="dark" title="Quick Summary"]`
- Ordered List Below Content: `[summaraize view="below" list_type="ordered"]`

## Installation

1. Upload the plugin files to the `/wp-content/plugins/summaraize` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Settings > SummarAIze** to configure the plugin.
4. Enter your OpenAI API key.
5. Optionally, configure your Assistant ID in the OpenAI Playground.

## Privacy

SummarAIze takes your privacy seriously. The plugin only sends the necessary content to OpenAI's servers for generating key points. No personal data or sensitive information is transmitted. However, please be aware that the content you choose to summarize will be processed by OpenAI. Review OpenAI's [Privacy Policy](https://openai.com/privacy) for details.

## Frequently Asked Questions

### Do I need an API key?
Yes. SummarAIze relies on the OpenAI API, which requires an active API key.

### Can I customize how the key points appear?
Yes! Use the plugin's settings or shortcode attributes to tailor the display to your needs.

### Are there costs for using SummarAIze?
The plugin is free, but OpenAI API usage may incur costs based on their pricing.

### How secure is the data transmitted to OpenAI?
Only the necessary content is sent to OpenAI's servers. No personal or sensitive data is shared.

## Screenshots

1. ![Above or Below Content](assets/above-or-below-content.png)  
   *Configure whether the key points appear above or below the content.*

2. ![Dark Mode](assets/dark-mode.png)  
   *Display key points in dark mode for a better visual experience.*

3. ![Classic Editor](assets/classic-editor.png)  
   *Interface for generating and editing key points in the Classic Editor.*

4. ![Popup View](assets/popup-view.png)  
   *Display key points in a popup view.*

5. ![Settings Screen](assets/settings-screen.png)  
   *The settings page for configuring display options.*

6. ![Drag and Drop Ordering](assets/DragDropOrdering.png)  
   *Reorder key points easily with drag-and-drop functionality.*

## Changelog

### 1.1.16
- Fixed metabox data saving issue.
- Improved UI for metabox inputs.
- Minor bug fixes and optimizations.

### 1.1.11
- Added drag-and-drop functionality to reorder points.
- Automatically removes empty points from the display.

### 1.1.10
- Added shortcode customization options for layout and style.

### 1.1.0
- Enabled dynamic Assistant creation via API.

### 1.0.0
- Initial release.

## License

This plugin is licensed under the GPLv2 or later.

## Donate

If you find this plugin useful, please consider [donating](https://oneclickcontent.com/donate/) to support further development.
