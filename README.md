# Popup Zen

Popups are broken, <a href="https://getpopupzen.com/">get Popup Zen</a>.

## FAQ

*Does it use the wp_mail() function to send mail?* 

Yes, if you have an SMTP plugin like Postman, Mailgun, or other mail plugin it will automatically use that to send mail.

*How do I setup MailChimp?*

First, add your API key under Popup Zen => Settings.

You can find your API key in your MailChimp account under Account => Extras => API Keys. Generate a new one if it doesn't exist.

Save your API key.

Next, in your popup, choose Mailchimp as the opt-in provider. Add your list ID. You can find this under Lists => Your List => Settings => List name and defaults. Look on the right side of the screen for List ID.

Copy/paste that into the MailChimp list ID field and save.

*How do I find my ConvertKit form ID and API key?*

Your API key is on your account page.

To get your form ID, visit Forms => your form. Look in the browser address bar, it should be something like this:

https://app.convertkit.com/landing_pages/445667/edit

That number is the form ID, in this case it's 445667. Enter that number as the ConvertKit form ID.

*How do I setup MailPoet?*

Install MailPoet, version 3 or later. Create a new popup, and select MailPoet as the email provider. Choose your list and save, new subscribers will be added to this.

**Troubleshooting**

*Emails are not sending*

The wp_mail() function is unreliable on many hosts. Install [Postman](https://wordpress.org/plugins/postman-smtp/) or another SMTP plugin to use a more reliable mail service.