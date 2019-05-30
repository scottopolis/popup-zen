=== Popup Zen - Small, Simple, Lightweight Email Optin ===

Contributors: scottopolis
Tags: popup, pop up, optin, lead generation, email opt-in
Requires at least: 4.5
Tested up to: 5.2.1
Stable tag: 0.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress popup that is ultra lightweight, simple to use, and small.

== Description ==

The Popup Zen Box is a cleaner, simpler way to collect emails with a popup.

It looks great, works great, and stays out of the way of your site visitors.

Integrate your email provider, choose where to show it, customize the colors, and publish. Done!

No complex settings or 3rd party SaaS dependencies.

**Features**

- Customize the text, image, and colors
- Choose where it displays - posts, pages, taxonomies, etc.
- Integrate your email provider: MailChimp, ConvertKit, MailPoet 3, Drip, Active Campaign, or send to your email address

**Why Popup Zen?**

Popup Zen is clean, fast, and simple. It's also non-intrusive.

Obnoxious popups may have a good opt-in rate, but they could be damaging your brand. Popup Zen is a new way to get high quality leads without pissing off your site visitors.

- Simply collect emails
- Minimalist design, easy to use
- Ultra-lightweight codebase

We built Popup Zen because we just couldn't bring ourselves to put that horrible popup in front of our site visitors. We wanted something that represented our brand better.

Instead of damaging your brand just to get a higher opt-in rate, Popup Zen believes in building trust.

Popup Zen gets high-quality leads by asking permission before displaying a popup. This builds trust with your site visitors, and keeps your brand image intact.

**What it won't do**

- Create obnoxious spinning and shaking animations
- Choose from 100 different designs
- Create insanely complex rules for how to display it
- Block the screen without permission

Learn more at [getpopupzen.com](https://getpopupzen.com)

Developers can contribute on [Github](https://github.com/scottopolis/popup-zen)

== Installation ==

Install and activate this plugin.

Visit Popup Zen => Settings, and add the API key for your chosen email provider (if necessary). Some providers only require that you have their plugin installed, such as Drip or MailPoet 3.

Next, visit the Popup Zen menu item, and add a new item.

**Options**

Choose to display your item at the bottom right or bottom left of your website.

Next, choose Zen Box or Popup. This is what your opt-in will look like when someone chooses to open it. The Zen Box is the default, it's a small vertical box. If you choose Popup, it will show a screen blocking popup, but only after the user clicks to show it.

**Customize**

Choose your colors, text content, and image. Save as a draft and then click the Preview button to see what it looks like. You can also click "View on site" to preview the popup on your site.

**Email Integrations**

Choose an email provider.

You must have an API key saved in the Popup Zen settings for MailChimp or Active Campaign.

For Drip, MailPoet 3, and others you must have the plugin installed. 

Note, we do not support MailPoet 1 or 2, only version 3.

**Display Settings**

- Pages: choose all pages, or select certain pages and begin typing a page title. It will automatically populate a drop down list, simply click the page title or enter page titles comma separated like this: Home, Features, Pricing
- New or returning: show to only new visitors (since you activated the plugin), or returning visitors. Tracked with the hwp_visit cookie.
- When should we show: after the page loads, show immediately, with a delay, or based on user scroll.
- When should it disappear: if you want the notification to show briefly and then disappear automatically, enter a delay here.
- How often show we show it: a visitor will be shown your message, then you can choose to continue showing it, or hide it based on number of days or user interaction. Interaction is either submitting an email, or clicking a link with a class of hwp-interaction.
- Show on devices: choose mobile, desktop, or both.

== FAQ ==

*Are there any limitations?*

No, you can create unlimited popups using all of the features described on this page.

*Does it use the wp_mail() function to send mail?* 

Yes, if you have an SMTP plugin like Postman, Mailgun, or other mail plugin it will automatically use that to send mail.

*How do I setup MailChimp?*

First, add your API key under Popup Zen => Settings.

You can find your API key in your MailChimp account under Account => Extras => API Keys. Generate a new one if it doesn't exist.

Save your API key.

Next, in your Popup Zen, choose Mailchimp as the opt-in provider. Add your list ID. You can find this under Lists => Your List => Settings => List name and defaults. Look on the right side of the screen for List ID.

Copy/paste that into the MailChimp list ID field and save.

<strong>How do I find my ConvertKit form ID and API key?</strong>

Your API key is on your account page.

To get your form ID, visit Forms => your form. Look in the browser address bar, it should be something like this:

https://app.convertkit.com/landing_pages/445667/edit

That number is the form ID, in this case it's 445667. Enter that number as the ConvertKit form ID.

*How do I setup MailPoet?*

Install MailPoet, version 3 or later. Create a new Popup, and select MailPoet as the email provider. Choose your list and save, new subscribers will be added to this list.

*Email signups are not working* 

Make sure your email form does not have a required field that is not displayed. For example, if you required first and last name, it will not work. Change your form to only require email, the rest of the fields optional.

== Screenshots ==

1. Zen box

2. Zen box expanded

3. Popup