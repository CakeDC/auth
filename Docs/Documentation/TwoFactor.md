Two Factor (2FA)
================

Two-factor authentication (2FA) is an identity and access management security method that requires two forms of identification to access resources and data. 2FA gives businesses the ability to monitor and help safeguard their most vulnerable information and networks.

Configuration
-------------

Processors defined as Configure storage with key `TwoFactorProcessors`

By default `\RobThree\Auth\Providers\Qr\EndroidQrCodeProvider` is used.

You can disable it by adding this to any config file:

`OneTimePasswordAuthenticator.qrcodeprovider` => `YOUR QR CODE PROVIDER`

To get a list of available providers please visit [RobThree/TwoFactorAuth](https://robthree.github.io/TwoFactorAuth/qr-codes.html) documentation.


Processors
-------------

* `OneTimePassword` - Authenticator is an authenticator app used as part of a two-factor/multi-factor authentication (2FA/MFA) scheme. It acts as an example of a “something you have” factor by generating one-time passwords (OTPs) on a smartphone or other mobile device.
* `Webauthn2fa` - WebAuthn is a browser-based API that allows for web applications to simplify and secure user authentication by using registered devices (phones, laptops, etc) as factors. It uses public key cryptography to protect users from advanced phishing attacks.
