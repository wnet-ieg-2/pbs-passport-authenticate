# PBS Passport Authenticate

PBS Passport Authenticate is a WordPress plugin to enable user logins with PBS Passport 


## Contents

PBS Passport Authenticate includes the following files:

* pbs-passport-authenticate.php, which invokes the plugin and provides the basic required functions.
* A subdirectory named `classes` containing the core PHP class files that most functions depend on.
* A subdirectory named `assets` containing JavaScript, CSS, and image files.
* A subdirectory named `templates` that contains PHP files to respond to some custom endpoints.
* A subdirectory named `examples` with sample code for custom interactions.

## Installation

1. Copy the `pbs-passport-authenticate` directory into your `wp-content/plugins` directory
2. Navigate to the *Plugins* dashboard page
3. Locate the menu item that reads *PBS Passport Authenticate*
4. Click on *Activate*
5. Navigate to *Settings* and select *PBS Passport Authenticate Settings* 
6. Enter values for all fields
7. It may be necessary to visit the 'Permalinks' settings page to make sure that the endpoints provided by the plugin resolve correctly.

## Usage

The plugin provides two functions:

1. A [pbs-passport-authenticate] shortcode that provides a login/logout link for the user, including going through the PBS Passport 'activation' process
2. A simple AJAX function to determine the login status of the user.

Activation enqueues a custom CSS file and a custom javascript file on every user-facing page.  The CSS file can be disabled from the plugin's settings page.

### Shortcode

Drop the shortcode [pbs-passport-authenticate] into place where you would want a login link to appear.  The link can be styled.  

Clicking on the link launches a Colorbox overlay with a Facebook/Google/PBS chooser, options for entering an activation code or becoming a member, and a remember me checkbox.  

Clicking the activation code selector presents a box to enter the activation code.  Doing so, a validity check will be performed, and if successful the MVault info is returned and the Facebook/Google/PBS chooser is presented again.

The chooser links open in the same window (not an overlay), send the user through the authentication process, redirect the user to /pbsoauth/callback/, which then completes the oAuth2 authentication process.

If the user skipped the activation code selector but the logged-in user has no Membership Vault account associated with his or her login, they're presented with the 'enter your activation code' prompt.  Entering the activation code performs a validity check, and then the login is connected to the MVault account.  

In any of these cases, the logged-in user is at the end redirected to the page they started on.

The login link will then be replaced with basic Welcome (name) text and a logout link.

#### Shortcode Arguments

The shortcode takes the following arguments

* `login_text` -- replaces the default login link text


### 'authenticate' AJAX endpoint

The /pbsoauth/authenticate URL can be hit via AJAX, and it takes no arguments.  It reads cookie and session info to find and decrypt a current oAuth access token and use that token to confirm the identity against PBS's PIDS and the MVAULT.   

It returns JSON with some basic info:  

* `authenticated`: true|false.  If false, no other fields are returned.
* `first_name` and `last_name`: The 'pbs_profile' first/last names.  These will be from the login provider (Facebook/Google/PBS), not from the MVault profile.
* `login_provider`: google/facebook/pbs
* `email`: the email associated with the login provider.
* `thumbnail_URL`: the avatar for the user provided by the login provider.  May be a generic icon if the user never set one up.  The 'pbs' login provider always provides a generic icon.

If the PIDS account is associated with a WNET member in the MVault, further info will appear in a 'membership_info' object:

* `offer`: the offer code, typically 'AVOD'.  This will match up against permissions in the COVE windowing system.
* `first_name` and `last_name`: these are what his or her membership is listed under, and can be completely different from the google etc provided values.
* `expire_date`: The expiration date of their membership
* `grace_period`: Three months after the expiration date, when the user loses access to Passport videos.
* `membership_id`: The user's membership id in the MVault.
* `provisional`: true|false .  Provisional records haven't been confirmed to match actual member records, and expire a few days after creation if not matched.

This endpoint is hit as part of the login process.  During login and/or reauthentication, the endpoint will also set an unencrypted cookie restricted to the current server but readable by Javascript with the values for `first_name`, `last_name`, `offer`, and `thumbnail_URL`.

This endpoint reads and (as necessary) sets two encrypted cookies:

* one with the user's oAuth 'access_token', for use to get access to PBS Passport resources such as member-restricted videos
* one with the user's oAuth 'refresh_token', used to get a new 'access_token' when the current one expires.  

These two cookies are encrypted with different keys, and both will be restricted from normal Javascript access.  

## Notes

On activation, the plugin registers two rewrite rules that redirect to some custom template files:

* `pbsoauth/authenticate`, which is an endpoint for our jQuery files to interact with during the authentication process
* `pbsoauth/callback`, which will accept any callbacks from the PBS LAAS oAuth2 flow and forward the grant token to the appropriate script.  The callback URI must then be registered with PBS as a valid redirect_uri for your LAAS key -- this is typically done via email.

If you need to overwrite or add some built-in functions, you can create a 'pluggable.php' file in the main directory of this plugin.  One particular use for this is if you have problems getting curl() working correctly -- create an 'mvault_curl_extras($ch)' function that adds whatever specific curl options your environment requires.




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