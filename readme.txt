=== Tiny Contact Form ===
Contributors: Tom Braider
Tags: email, mail, contact, form
Donate link: http://www.tomsdimension.de/postcards
Requires at least: 2.8
Tested up to: 3.0.3
Stable tag: 0.6

Little form that allows site visitors to contact you by email.

== Description ==

Use '[TINY-CONTACT-FORM]' within any post or page.
Add the widget to your sidebar.

== Installation ==

1. unzip plugin directory into the '/wp-content/plugins/' directory
1. activate the plugin through the 'Plugins' menu in WordPress
1. check the settings (email, messages, style) in backend
1. insert '[TINY-CONTACT-FORM]' in your page or/and add the widget to your sidebar
1. without widgets use this code to insert the form in your sidebar.
   '&lt;?php if (isset($tiny_contact_form)) echo $tiny_contact_form->showForm(); ?&gt;'

== Frequently Asked Questions ==

= How to style? =
- The complete form is surrounded by a 'div class="contactform"'. Tags in FORM: LABEL, INPUT and TEXTAREA.
- To change the form style in your sidebar you can use '.widget .contactform' (plus tags above) in your template 'style.css'.
- Since v0.3 you can use the settings.

= Need Help? Find Bug? =
read and write comments on <a href="http://www.tomsdimension.de/wp-plugins/tiny-contact-form">plugin page</a>

== Screenshots ==

1. contact form on page
2. contact form widget in sidebar
3. settings page

== Arbitrary section ==

**Translations**

* by: Marcis Gasuns http://www.fatcow.com
* da: Jonas Thomsen http://jonasthomsen.com
* de: myself ;)
* es: Jeffrey Borb&oacute;n http://www.eljeffto.com 
* fr: Jef Blog
* he: Sahar Ben-Attar http://openit.co.il
* hr, it: Alen &Scaron;irola http://www.gloriatours.hr
* hu: MaXX http://www.novamaxx.hu
* sv: Thomas http://www.ajfix.se

== Changelog ==

= 0.7 =
+ new: multi widgets with different receivers
+ new: 5 additional fields (e.g. website, phone) possible
+ new: to hide form after submit is your choice now
+ new: alternative "question answer captcha"
+ new language: Swedish, thanks to Thomas http://www.ajfix.se
+ some bugfixes

.tcf_label
.tcf_field
.tcf_textarea
.tcf_submit

= 0.6 =
+ new: set reciever and subject in shortcode [TINY-CONTACT-FORM to="abc@def.hi" suject="Hello"]
+ new: get subject from url like '?subject=Hello'
+ now name and email of the writer are the default "From" data if non "From" given on options page

= 0.5.2 =
+ new translation: hebrew, thanks Sahar Ben-Attar

= 0.5.1 =
+ Bugfix: referer on pages with more post was wrong
+ Bugfix: PHP4 compatibility, "static" before function deleted
+ new translation: Danish, thanks to Jonas Thomsen

= 0.5 =
+ new: optional captcha
+ new: referer (page the mail was sent) in mail
+ new translation: France, thanks to Jef Blog

= 0.4.3 =
+ Bugfix: little change in stylesheet to realy hide the "hidden" fields

= 0.4.2 =
+ Bugfix: little change in spam check

= 0.4.1 =
+ new translation: Belorussian, thanks to Marcis Gasuns

= 0.4 =
+ new: custom widget title and submit button

= 0.3.3 =
+ new translation: hungarian, thanks MaXX

= 0.3.2 =
+ new translation: espanol, thanks Jeffrey

= 0.3.1 =
+ new translations: hr and italiano, thanks Alen

= 0.3 =
+ new: more user settings
+ new: language support (english, german)
+ change to wp_mail()

= 0.2 =
+ new: sidebar widget to easy add the form to the sidebar

= 0.1 =
+ first release