=== bbPress ===
Contributors: elhardoum
Tags: multisite, users, forms, login, reset-password, activation, signup, registration, addons
Requires at least: 4.7
Tested up to: 4.7
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author URI: http://samelh.com/
Donate link: http://samelh.com/

A multisite authentication plugin for handling logins, signups, password resets, activation account auth components all in one WordPress blog/site

== Description ==

<h3>About Multisite Auth</h3>

<p>Multisite Auth is a plugin you can use to handle all the authentication and account componenets under the same site/blog. You can create a custom blog for the authentication, say for instance accounts.mynetwork.com, and all the other sites and blogs (including the parent main one) will be redirected to this auth site to process the following:</p>

<ul>
<li>Account login</li>
<li>Account logout</li>
<li>Password reset and reset email</li>
<li>User registrations with blogs</li>
<li>Account activations</li>
</ul>

<h3>Addons</h3>

<ul>
<li><a href="https://github.com/elhardoum/muauth-recaptcha">Google reCaptcha</a> for handling spam.</li>
<li><a href="https://github.com/elhardoum/muauth-mailchimp">MailChimp</a> to opt-in users to your mailing list(s) easily from signup forms.</li>
<li><a href="https://github.com/elhardoum/muauth-zero-spam">Zero Spam</a> to eliminate spam signups with no captchas or annoying tests.</li>
<li><a href="https://github.com/elhardoum/muauth-google-authenticator">Google Authenticator</a> implementing Google Authenticator for your WordPress blog.</li>
</ul>

<p>This plugin is recently released so we will be working on more addons and implementations as soon as possible.</p>

The development version of this plugin is hosted on Github, feel free to fork it, contribute and improve it, or start a new issue if you want to report something like an unusual bug. 

Here's the Github repo: https://github.com/elhardoum/multisite-auth

Thank you!

== Installation ==

1. Visit 'Plugins > Add New'
2. Search for 'Multisite Auth'
3. Activate Multisite Auth from your Plugins page. You will have to activate it for the whole network.

Once you activate the plugin, all regular WordPress auth pages (e.g wp-(login|signup|activate).php) will be redirected to the custom auth site, as long as you select a site for the authentication and the component is active.

== Screenshots ==

1. Signup front-end form
2. Signup front-end form with blog signup
3. Login form
4. Lost password preview
5. User account activation form
6. Network settings admin page

== Changelog ==

= 0.1.4 =
* Initial stable release. See Github for logs.