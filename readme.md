# Global Options for WordPress

**Version:** 1.5  
**Author:** Yasir Shabbir  
**Author URI:** https://yasirshabbir.com  
**License:** GPL v2 or later  
**Requires at least:** WordPress 5.0  
**Tested up to:** WordPress 6.4  
**Requires PHP:** 7.4

A powerful WordPress plugin that provides a centralized location to manage site-wide settings, contact details, business information, social media links, and more. Seamlessly integrates with Bricks Builder through dynamic data tags.

---

## 🌟 Features

### Comprehensive Data Management
- **Contact Details** - Multiple phone numbers (main, WhatsApp, toll-free, mobile) and emails (general, support, info, billing, sales)
- **Business Information** - Company name, tagline, registration details, VAT/Tax numbers
- **Location Details** - Full address, city, state, country, ZIP code, business hours
- **Social Media Links** - 12 popular platforms (Facebook, Instagram, LinkedIn, Twitter, YouTube, TikTok, Pinterest, WhatsApp, Telegram, Discord, Snapchat, Reddit)
- **E-commerce Settings** - Shipping info, return policy, currency, product badges
- **Legal/Policy URLs** - Privacy policy, terms, cookie policy, refund & shipping policies
- **Banner Settings** - Animated typing banner with prefix/suffix and toggle
- **Call-to-Action** - Primary and secondary CTA buttons with text and URLs

### Bricks Builder Integration
- **Dynamic Data Tags** - Access all settings through `{global_*}` tags
- **Works Everywhere** - Text fields, URLs, attributes, href, src, and more
- **Real-time Rendering** - Tags are processed and displayed automatically
- **Custom Data Group** - Organized under "Global Options" in Bricks dynamic data

### Advanced Features
- **Import/Export** - Backup and restore all settings via JSON files
- **Dark Mode UI** - Beautiful, modern interface with Yasir Shabbir branding
- **Settings Modal** - Convenient modal for plugin configuration
- **Data Cleanup** - Optional automatic data removal on plugin uninstall
- **Tab Navigation** - Organized interface with persistent tab selection

---

## 📦 Installation

### Method 1: Upload via WordPress Admin
1. Download the `global-options.php` file
2. Go to **Plugins** → **Add New** → **Upload Plugin**
3. Choose the file and click **Install Now**
4. Activate the plugin

### Method 2: Manual Installation
1. Download the `global-options.php` file
2. Upload it to `/wp-content/plugins/` directory
3. Go to **Plugins** in WordPress admin
4. Activate **Global Options**

### Method 3: FTP Upload
1. Connect to your site via FTP
2. Upload `global-options.php` to `/wp-content/plugins/`
3. Activate from WordPress admin

---

## 🚀 Getting Started

### Basic Setup

1. **Access the Plugin**
   - After activation, find **Global Options** in your WordPress admin menu
   - Recognizable by the 🌐 globe icon

2. **Configure Your Settings**
   - Navigate through the tabs (Contact, Business, Social, etc.)
   - Fill in your information
   - Click **Save Changes**

3. **Use in Bricks Builder**
   - Add any element in Bricks
   - Use dynamic data tags: `{global_phone}`, `{global_email}`, etc.
   - Tags work in text, URLs, and all attributes

---

## 📖 Usage Guide

### Available Dynamic Tags

#### Contact Details
```
{global_phone}              - Main phone number
{global_phone_whatsapp}     - WhatsApp number
{global_phone_tollfree}     - Toll-free number
{global_phone_mobile}       - Mobile number
{global_email}              - General email
{global_email_support}      - Support email
{global_email_info}         - Info email
{global_email_billing}      - Billing email
{global_email_sales}        - Sales email
{global_address}            - Business address
{global_business_hours}     - Business hours
```

#### Business Information
```
{global_company_name}       - Company name
{global_tagline}            - Company tagline
{global_registration_number} - Registration number
{global_vat_number}         - VAT number
{global_tax_id}             - Tax ID
```

#### Location Details
```
{global_city}               - City
{global_state}              - State/Province
{global_country}            - Country
{global_zipcode}            - ZIP/Postal code
```

#### Social Media
```
{global_social_facebook}    - Facebook URL
{global_social_instagram}   - Instagram URL
{global_social_linkedin}    - LinkedIn URL
{global_social_twitter}     - Twitter URL
{global_social_youtube}     - YouTube URL
{global_social_tiktok}      - TikTok URL
{global_social_pinterest}   - Pinterest URL
{global_social_whatsapp}    - WhatsApp URL
{global_social_telegram}    - Telegram URL
{global_social_discord}     - Discord URL
{global_social_snapchat}    - Snapchat URL
{global_social_reddit}      - Reddit URL
```

#### E-commerce
```
{global_shipping_info}              - Shipping information
{global_return_policy}              - Return policy
{global_free_shipping_threshold}    - Free shipping threshold
{global_currency_symbol}            - Currency symbol
{global_sale_badge}                 - Sale badge text
{global_out_of_stock_badge}         - Out of stock badge
{global_in_stock_badge}             - In stock badge
```

#### Legal/Policy
```
{global_privacy_policy_url}    - Privacy policy URL
{global_terms_url}             - Terms & conditions URL
{global_cookie_policy_url}     - Cookie policy URL
{global_refund_policy_url}     - Refund policy URL
{global_shipping_policy_url}   - Shipping policy URL
```

#### Banner
```
{global_banner_prefix}         - Banner prefix text
{global_banner_suffix}         - Banner suffix text
{global_banner_enabled}        - Banner enabled (True/False)
{global_animated_typing_1}     - First animated text
{global_animated_typing_2}     - Second animated text
{global_animated_typing_3}     - Third animated text
```

#### Call-to-Action
```
{global_cta_text}              - Primary CTA text
{global_cta_url}               - Primary CTA URL
{global_cta_secondary_text}    - Secondary CTA text
{global_cta_secondary_url}     - Secondary CTA URL
```

---

## 💡 Usage Examples

### Example 1: Phone Number Link
```html
<a href="tel:{global_phone}">{global_phone}</a>
```

### Example 2: WhatsApp Link
```html
<a href="https://wa.me/{global_phone_whatsapp}">Chat on WhatsApp</a>
```

### Example 3: Email Link
```html
<a href="mailto:{global_email}">{global_email}</a>
```

### Example 4: Social Media Icon
```html
<a href="{global_social_facebook}" target="_blank">
    <i class="fab fa-facebook"></i>
</a>
```

### Example 5: Company Address
```html
<address>
    {global_address}<br>
    {global_city}, {global_state} {global_zipcode}<br>
    {global_country}
</address>
```

### Example 6: Business Hours
```html
<div class="hours">
    <h3>Business Hours</h3>
    <p>{global_business_hours}</p>
</div>
```

### Example 7: Sale Badge with Dynamic Discount
```html
<!-- In Global Options, set sale_badge to: -->
{woo_product_on_sale} OFF

<!-- This will display: -49% OFF (or any discount percentage) -->
```

---

## ⚙️ Settings Modal

### Accessing Settings
1. Click the **⚙️ Settings** button in the top-right corner
2. Modal opens with two sections:

### Backup & Restore
- **Export Settings**: Download all settings as JSON file
- **Import Settings**: Upload a JSON file to restore settings
- Great for:
  - Moving between staging and production
  - Backing up before major changes
  - Sharing configurations

### Danger Zone
- **Data Cleanup Toggle**: Enable to automatically delete all plugin data when uninstalling
- **Warning**: This action cannot be undone
- Useful for clean plugin removal

---

## 🎨 Interface Overview

### Tabs Organization
1. **📞 Contact** - Phone numbers and email addresses
2. **🏢 Business** - Company info, address, and location
3. **🔗 Social** - Social media profile links
4. **🛒 E-commerce** - Shipping, badges, and store settings
5. **⚖️ Legal** - Policy and legal page URLs
6. **🎉 Banner** - Animated banner configuration
7. **🎯 CTA** - Call-to-action button settings

### Dark Mode Design
- Modern dark interface
- Yasir Shabbir branding colors
- Green accent color (#16e791)
- Smooth animations and transitions
- Responsive layout

---

## 🔧 Technical Details

### How It Works

#### Tag Replacement System
The plugin uses a multi-stage tag replacement system:

1. **Element Settings Processing** (Priority 5)
   - Replaces tags in element configuration
   - Handles URLs, attributes, and settings

2. **Content Rendering** (Priority 5)
   - Processes tags in text content
   - Works with Bricks dynamic data

3. **Final Output Processing** (Priority 999)
   - Catches any remaining tags in HTML output
   - Ensures complete tag replacement

This ensures tags work **everywhere**:
- ✅ Plain text: `{global_phone}`
- ✅ URLs: `https://wa.me/{global_phone_whatsapp}`
- ✅ Relative paths: `/{global_city}`
- ✅ Attributes: `href="{global_email}"`
- ✅ Complex URLs: `mailto:{global_email}?subject=Hello`

### Database Storage
- **Option Name**: `global_options_data`
- **Cleanup Option**: `global_options_cleanup_on_delete`
- **Storage Type**: Serialized array
- **Size**: Minimal (text-only storage)

### Security Features
- Nonce verification for all form submissions
- Capability checks (`manage_options`)
- Data sanitization on save
- URL validation for all URL fields
- Email validation for all email fields

---

## 🆘 Troubleshooting

### Tags Not Showing
**Problem**: Tags appear as `{global_*}` instead of values  
**Solution**:
- Ensure you've saved settings in Global Options
- Clear Bricks cache: Bricks → Settings → Performance → Clear Cache
- Clear WordPress cache if using caching plugin

### Tags Not Working in URLs
**Problem**: Tags in href or src attributes don't work  
**Solution**:
- Update to version 1.5 or higher
- The multi-stage replacement system fixes this

### Import Fails
**Problem**: Import shows error message  
**Solution**:
- Ensure you're uploading a valid JSON file
- File must be exported from Global Options
- Check file wasn't corrupted during download

### Settings Not Saving
**Problem**: Changes don't persist  
**Solution**:
- Check file permissions on WordPress installation
- Ensure database connection is stable
- Try deactivating other plugins that might conflict

### Modal Won't Open
**Problem**: Settings button doesn't work  
**Solution**:
- Clear browser cache
- Check browser console for JavaScript errors
- Ensure jQuery is loaded

---

## 🔄 Changelog

### Version 1.5 (Current)
- ✨ Added Settings Modal for better UX
- ✨ Moved Import/Export to modal
- ✨ Moved Danger Zone to modal
- 🐛 Fixed dynamic tags not working in URLs
- 🐛 Fixed tags in href and src attributes
- 🔧 Improved multi-stage tag replacement system
- 🎨 Enhanced modal design with animations
- 📱 Better mobile responsiveness

### Version 1.4
- ✨ Added Import/Export functionality
- ✨ Added data cleanup option
- 🎨 Improved dark mode styling
- 📝 Better field organization

### Version 1.3
- ✨ Added CTA fields
- ✨ Added banner settings
- 🐛 Fixed tab navigation

### Version 1.2
- ✨ Added e-commerce settings
- ✨ Added legal policy URLs
- 🎨 Improved UI/UX

### Version 1.1
- ✨ Added social media links
- ✨ Added location details
- 🐛 Bug fixes

### Version 1.0
- 🎉 Initial release
- Basic contact and business fields
- Bricks Builder integration

---

## 🤝 Support

### Getting Help
- **Documentation**: This README file
- **Issues**: Check troubleshooting section above
- **Feature Requests**: Contact the author

### Author Contact
- **Name**: Yasir Shabbir
- **Website**: https://yasirshabbir.com
- **Email**: Available on website

---

## 📝 License

This plugin is licensed under the GPL v2 or later.

```
Global Options - WordPress Plugin
Copyright (C) 2024 Yasir Shabbir

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

---

## 🙏 Credits

- **Developer**: Yasir Shabbir
- **Design**: Custom dark mode interface
- **Icons**: Emoji icons for visual clarity
- **Font**: Lato (Google Fonts)

---

## 🎯 Roadmap

### Planned Features
- [ ] Conditional display based on tags
- [ ] Multi-language support
- [ ] Custom field types
- [ ] API for third-party integrations
- [ ] Shortcode support for non-Bricks themes
- [ ] Field groups and organization
- [ ] Role-based access control
- [ ] Revision history for settings

---

## ⭐ Rate This Plugin

If you find this plugin helpful, please consider:
- ⭐ Rating it on WordPress.org (when published)
- 💬 Sharing it with other developers
- 🐛 Reporting bugs or suggesting features
- ☕ Supporting the developer

---

**Made with 💚 by Yasir Shabbir**

*For the latest updates and more WordPress tools, visit [yasirshabbir.com](https://yasirshabbir.com)*