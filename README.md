# PBS Passport Authenticate

WNET Interactive Engagement Group (IEG) is pleased to share the source code for our 'PBS Passport Authenticate' WordPress plugin with any PBS station, free of charge, under the terms of the GNU General Public License (attached below and included with the plugin source code).    

The 'PBS Passport Authenticate' plugin, when installed in a PBS station website running WordPress, creates a turnkey system where a station member who has an account within the PBS Membership Vault can login to the station website using Google, Facebook, Apple, or the member's 'PBS Account'.   If the member has 'activated' via PBS.org, or used the included 'activation' form that is part of our plugin, the station website can show or hide content -- including embedded Passport video -- on the station website based on the visitor's status as a Passport-eliglble member of the station.


### Notes: 
* We do not provide membership donation or creation forms, nor any process to send 'activation codes' to members.   If your station uses an 'instant gratification' donation process that generates activation links or activation codes, it is likely possible to adapt that process to use our included activation form.  We've been able to do so for Thirteen.org.  
* Otherwise, the PBS-provided activation code that your station gets when importing new members into the PBS Membership Vault ('MVault') will also work with our included activation form, and that 'activation' status will carry through to PBS.org as well.    
* The login system can be added to your theme with a single line of code in your theme header.
* We provide basic example code to restrict or allow access to a COVE Passport video based on the visitor's status as a Passport-eliglible member of the station, but it is up to the station to embed COVE Passport video onto their website.  
* We include CSS with the plugin, but the station will probably want to override some of that CSS to match their own website design.
* The login system requires no heavy database interaction -- member information and authentication status is stored within cookies, and no member data is stored in your website's database, just the PBS Membership Vault.
* Enabling the login system requires contacting PBS for MVault access keys and PBS Profile Service access keys.  Make sure when you request the PBS Profile Service access key to request that they include the 'vppa' scope.

### Support:
We welcome bug reports made via our GitHub repository, and while we do not provide any warranty for this plugin, we'll make a good faith effort to address bugs and update the source code in a timely fashion.  We use this plugin in our own website and we want the best and most reliable product possible!

In addition to the code itself, we will also be happy to provide limited support for the plugin:

* Included installation instructions that should allow a person who is familiar with installing and configuring WordPress plugins to get setup;
* One free phone call, scheduled in advance, with a duration of not longer than an hour, for initial installation and setup help;
* Further email and phone consultation for installation and setup assistance can be arranged at a rate of $175/hour.   





## Contents

PBS Passport Authenticate includes the following files:

* pbs-passport-authenticate.php, which invokes the plugin and provides the basic required functions.
* A subdirectory named `classes` containing the core PHP class files that most functions depend on.
* A subdirectory named `assets` containing JavaScript, CSS, and image files.
* A subdirectory named `templates` that contains PHP files to respond to some custom endpoints.
* This README 
* A copy of the GNU Public License




## Setup and Installation

Before you install, you will need to file some support tickets with PBS to get credentials for some APIs that they provide.

### Public Media SSO
Public Media Single-Sign-On is an Akamai-hosted OpenID Connect system shared between PBS and NPR.   This plugin integrates with that system to provide member logins on a local station website.   Visit https://docs.pbs.org/space/PMSSO/29392997/Public+Media+Single+Sign-On+(SSO) for more technical details.  This plugin uses the "public client" setup that by default they will setup.

### PBS Account (deprecated but still supported by this plugin)
*NOT NEEDED ANYMORE, But if you already have it setup this plugin will allow you to use both and switch over with a checkbox in the plugin settings.*
This is PBS's 'Login as a service' (LAAS) API that the plugin uses to provide member logins on a local station website.   Visit https://docs.pbs.org/display/uua and review the documentation there, particularly "Integrating PBS Account with your website or app".   

**Using either PMSSO or PBS Account**, you will need to go to the PBS Digital Support site and request the keys etc for your station, and that request will need to include a redirect URI for each website that will use that key; that URI will be in the form 'https://yourstation.org/pbsoauth/callback' (replacing 'yourstation.org' with the actual exact hostname).  The URI must be exact; 'Wildcard' domains aren't allowed, so if you have a redirect to send somebody from yourstation.org to www.yourstation.org , then use www.yourstation.org; and if your station can only be accessed via http (not https), then the protocol in the redirect URI must match.   If you have a dev server and a production server, include the matching redirect URIs for both -- there's no real limit to how many redirect URIs you can have, but each must be added.   

### Membership Vault API
If your station is a Passport station, you should already have a Membership Vault (MVault) API keypair.   If your station is not a Passport station this plugin is not for you.   You will need to enter the MVault API keypair for your station in the plugin settings.

### Installation
1. Copy the `pbs-passport-authenticate` directory into your `wp-content/plugins` directory
2. Navigate to the *Plugins* dashboard page
3. Locate the menu item that reads *PBS Passport Authenticate*
4. Click on *Activate*
5. Navigate to *Settings* and select *PBS Passport Authenticate Settings* 
6. Enter values for all fields, particularly the PMSSO, LAAS and MVault sections (see above).
7. It may be necessary to visit the 'Permalinks' settings page to make sure that the endpoints provided by the plugin resolve correctly.

## Usage

The plugin provides two functions:

1. A [pbs-passport-authenticate] shortcode that provides a login/logout link for the user, including going through the PBS Passport 'activation' process
2. A simple AJAX function to determine the login status of the user.

Activation enqueues a custom CSS file and a custom javascript file on every user-facing page.   

### Login Form Shortcode

Drop the shortcode [pbs-passport-authenticate] into place where you would want a login link to appear.  The link can be styled.  

Clicking on the link navigates to a page with a Facebook/Google/Apple/PBS chooser, options for entering an activation code or becoming a member, and a remember me checkbox.  

Clicking the activation code selector presents a box to enter the activation code.  Doing so, a validity check will be performed, and if successful the MVault info is returned and the Facebook/Google/Apple/PBS chooser is presented again.

The chooser links open in the same window (not an overlay), send the user through the authentication process, redirect the user to /pbsoauth/callback/, which then completes the oAuth2 authentication process.

If the user skipped the activation code selector but the logged-in user has no Membership Vault account associated with his or her login, they're presented with the 'enter your activation code' prompt.  Entering the activation code performs a validity check, and then the login is connected to the MVault account.  

In any of these cases, the logged-in user is at the end redirected to the page they started on.

The login link will then be replaced with basic Welcome (name) text and a logout link.

#### Shortcode Arguments

The shortcode takes the following arguments:

* `login_text` -- replaces the default login link text

#### Customizing the CSS

The plugin automatically 'enqueues' CSS on your site to make the login form and activation form look nice.  For a good starting point on making the fonts and colors of those forms match your site's look and feel, you may want to add some CSS to your website's theme CSS;

```css
.pbs-passport-authenticate-wrap { color: "yourcolor"; font-family: "yourfont"; }

.passport-help-text { color: "yourcolor"; font-size: 1em; }
.passport-help-text .fa {  color: "yourcolor";}
.passport-help-text a {color: "yourcolor";}

.service-sign-in  h3 {font-weight: 600;}
.service-sign-in .create-pbs a { color: "yourcolor";}

.pbs-passport-authenticate.activate { color: "yourcolor"; }
.pbs-passport-authenticate.activate  form button {color: "yourcolor";}
.pbs-passport-authenticate.activate  h3.error { color: "yourcolor";}
.pbs-passport-authenticate.activate  p a {color: "yourcolor";}
```

There's also a custom screen for handling "VPPA" (Video Privacy and Protection Act) assent.  That page is very simplified, and has a single stylesheet.  If you place a CSS file named 'passport-vppa.css' in your theme's main directory, that stylesheet will also be included, allowing you to override styles set on that page.

### Embeding a Passport Video

The plugin includes a 'cove-passport' shortcode that will render a Passport video player.  If the visitor is a member of your station, has activated their Passport benefit, logged into your site, and is eligible to watch Passport videos, when the video is rendered with the shortcode it will show the visitor the video.  It the visitor is NOT logged in he or she will see an overlay that directs them to log in to view the video.

#### Shortcode Arguments

The shortcode takes the following arguments:

* `id` -- the COVE 'tp_media_object_id'.  This is found in Merlin or the COVE API.
* `window` -- 'all_members', 'station_members', and 'public' are the options.  'all_members' should be used if the video is available to members of any PBS station.
* `image` -- the 'mezzanine image' that should appear in place of the video, behind the 'login' overlay, when a visitor isn't logged in; This should be a high-res, 16x9 still from the video.  The COVE API provides a 'mezzanine' image that can be used here.


### 'authenticate' AJAX endpoint

The /pbsoauth/authenticate URL can be hit via AJAX, and it takes no arguments.  It reads cookie and session info to find and decrypt a current oAuth access token and use that token to confirm the identity against PBS's PIDS and the MVAULT.   

It returns JSON with some basic info:  

* `first_name` and `last_name`: The 'pbs_profile' first/last names.  These will be from the login provider (Facebook/Google/Apple/PBS), not from the MVault profile.
* `pid`: the 'pbs_profile' pid/uid
* `thumbnail_URL`: the avatar for the user provided by the login provider.  May be a generic icon if the user never set one up.  The 'pbs' login provider always provides a generic icon.

If the PIDS account pid/uid is associated with a member in your station's MVault, further info will appear in a 'membership_info' object:

* `offer`: the offer code, typically 'MVOD'.  This will match up against permissions in the COVE windowing system.
* `first_name` and `last_name`: these are what his or her membership is listed under, and can be completely different from the google etc provided values.
* `expire_date`: The expiration date of their membership
* `grace_period`: Three months after the expiration date, when the user loses access to Passport videos.
* `membership_id`: The user's membership id in the MVault.
* `status`: 'On'|'Off' . 'Off' will either be set by the user being manually disabled in the MVault console, past their grace period, or simply not activated.  

NOTE: to simplify coding we create a membership_info block regardless of whether the logged-in visitor is actually a member.  A non-activated visitor will only have values for status ('Off') and offer (null).  An activated but past grace period member will have an offer (whatever was last set on their account) but status will be 'Off'.

This endpoint is hit as part of the login process.  During login and/or reauthentication, the endpoint will also set an unencrypted cookie restricted to the current server but readable by Javascript with the values for `first_name`, `last_name`, `pid`, `thumbnail_URL`, and the contents of the membership_info block.

This endpoint reads and (as necessary) sets an encrypted cookie with:

* the user's oAuth 'access_token', for use to get access to PBS Passport resources such as member-restricted videos
* the user's oAuth 'refresh_token', used to get a new 'access_token' when the current one expires.  

The cookie is encrypted with a secret key, a random IV, and set as a 'server-only' cookie.  

## Further details

On activation, the plugin registers two rewrite rules that redirect to some custom template files:

* `pbsoauth/authenticate`, which is an endpoint for our jQuery files to interact with during the authentication process
* `pbsoauth/callback`, which will accept any callbacks from the PBS LAAS oAuth2 flow and forward the grant token to the appropriate script.  The callback URI must then be registered with PBS as a valid redirect_uri for your LAAS key -- this is typically done via a ticket on the PBS Digital Support portal.
* `pbsoauth/loginform`, which generates a page that displays login options.
* `pbsoauth/activate`, which generates a page that allows a member to enter an activation code.
* `pbsoauth/userinfo`, which generates a page that displays the current member status of the logged-in visitor, for instance if they're expired or not activated.
* `pbsoauth/vppa`, which generates a page that explains and directs the user to review terms around data sharing required by "VPPA" (Video Privacy and Protection Act).

If you need to overwrite or add some built-in functions, you can create a 'pluggable.php' file in the main directory of this plugin.  One particular use for this is if you have problems getting curl() working correctly -- create an 'mvault_curl_extras($ch)' function that adds whatever specific curl options your environment requires.

## Authors
William Tam and Brian Santalone


## License

The PBS Passport Authenticate plugin is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

> This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

> You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
