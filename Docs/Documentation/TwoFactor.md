Two Factor (2FA)
================

Two-factor authentication (2FA) is an identity and access management security method that requires two forms of identification to access resources and data. 2FA gives businesses the ability to monitor and help safeguard their most vulnerable information and networks.

Configuration
-------------

The processors are listed in the `TwoFactorProcessors` Configure key, see `config/auth.php`.

The one time password processor is configured with the `OneTimePasswordAuthenticator` key:

* `login`: enable the one time password verification, `false` by default
* `verifyAction`: url of the verify action
* `checker`: class deciding if the verification is required, `DefaultOneTimePasswordAuthenticationChecker` by default
* `issuer`: issuer shown in the authenticator app
* `digits`: number of digits of the codes, `6` by default
* `period`: seconds a code is valid, `30` by default
* `algorithm`: `\RobThree\Auth\Algorithm` case, `Sha1` by default
* `qrcodeprovider`: QR code provider, `\RobThree\Auth\Providers\Qr\EndroidQrCodeProvider` by default
* `rngprovider`: random number generator provider, `null` for the default one

The default QR code provider needs `endroid/qr-code`. To use another one, set
`OneTimePasswordAuthenticator.qrcodeprovider` in any config file. To get a list of available
providers please visit [RobThree/TwoFactorAuth](https://robthree.github.io/TwoFactorAuth/qr-codes.html)
documentation.

The Webauthn processor is configured with the `Webauthn2fa` key: `enabled`, `appName`, `id` (defaults
to the current domain), `checker` and `startAction`.


The url of each processor (`OneTimePasswordAuthenticator.verifyAction`, `Webauthn2fa.startAction`,
`U2f.startAction`) can be a routing array or a string url. The query params of the login request
are appended to it.

Processors
-------------

* `OneTimePassword` - Authenticator is an authenticator app used as part of a two-factor/multi-factor authentication (2FA/MFA) scheme. It acts as an example of a “something you have” factor by generating one-time passwords (OTPs) on a smartphone or other mobile device.
* `Webauthn2fa` - WebAuthn is a browser-based API that allows for web applications to simplify and secure user authentication by using registered devices (phones, laptops, etc) as factors. It uses public key cryptography to protect users from advanced phishing attacks.
* `U2F` - Deprecated, browsers no longer support U2F, use Webauthn instead. Configured with the `U2f`
  key (`enabled`, `startAction`).
