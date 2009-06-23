=== Tiny Contact Form ===
Contributors: Tom Braider
Donate link: http://www.unicef.org
Tags: email, mail, contact, form
Requires at least: 2.7
Tested up to: 2.8
Stable tag: 0.5

Little form that allows site visitors to contact you by email.

== Description ==

Use '[TINY-CONTACT-FORM]' within any post or page.
Add the widget to your sidebar.

== Installation ==

1. unzip plugin directory into the '/wp-content/plugins/' directory
1. activate the plugin through the 'Plugins' menu in WordPress
1. insert '[TINY-CONTACT-FORM]' in your page or/and add the widget to your sidebar
1. check the settings (email, messages, style) in backend
1. without widgets use this code to insert the form in your sidebar.
   '&lt;?php if (isset($tiny _ contact _ form)) $tiny _ contact _ form->showForm(); ?&gt;'

== Frequently Asked Questions ==

= How to style? =
- The complete form is surrounded by a '<div class="contactform">'. Tags in FORM: LABEL, INPUT and TEXTAREA.
- To change the form style in your sidebar you can use '.widget .contactform' (plus tags above) in your template 'style.css'.
- Since v0.3 you can use the settings.

= Need Help? Find Bug? =
read and write comments on <a href="http://www.tomsdimension.de/wp-plugins/tiny-contact-form">plugin page</a>

== Screenshots ==

1. contact form on page
2. contact form widget in sidebar
3. settings page

== Arbitrary section ==

**Silent Helper**

* Jay Shergill http://www.pdrater.com

**Translations**

* by: Marcis Gasuns http://www.fatcow.com
* de: myself ;)
* es: Jeffrey Borb&oacute;n http://www.eljeffto.com 
* fr: Jef Blog
* hr, it: Alen &Scaron;irola http://www.gloriatours.hr
* hu: MaXX http://www.novamaxx.hu

**Filelist**

* tiny-contact-form.php
* locale/tcf-lang-by_BY.po
* locale/tcf-lang-de_DE.po
* locale/tcf-lang-es_ES.po
* locale/tcf-lang-fr_FR.po
* locale/tcf-lang-hr_HR.po
* locale/tcf-lang-hu_HU.po
* locale/tcf-lang-it_IT.po
* locale/tcf-lang-pt_BR.po

== Changelog ==

0.5
+ new: optional captcha
+ new: referer (page the mail was sent) in mail
+ new: translation: France, thanks to Jef Blog

0.4.3
+ Bugfix: little change in stylesheet to realy hide the "hidden" fields

0.4.2
+ Bugfix: little change in spam check

0.4.1
+ new translation: Belorussian, thanks to Marcis Gasuns

0.4
+ new: custom widget title and submit button

0.3.3
+ new translation: hungarian, thanks MaXX

0.3.2
+ new translation: espanol, thanks Jeffrey

0.3.1
+ new translations: hr and italiano, thanks Alen

0.3
+ new: more user settings
+ new: language support (english, german)
+ change to wp_mail()

0.2
+ new: sidebar widget to easy add the form to the sidebar

0.1
+ first release