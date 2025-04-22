Two Factor (2FA)
================

Two-factor authentication (2FA) is an identity and access management security method that requires two forms of identification to access resources and data. 2FA gives businesses the ability to monitor and help safeguard their most vulnerable information and networks.

Configuration
-------------

Processors defined as Configure storage with key `TwoFactorProcessors`


Processors
-------------

* `U2FProcessor` - *deprecated*. Universal 2nd Factor (U2F) is an open standard that strengthens and simplifies two-factor authentication (2FA) using specialized Universal Serial Bus (USB) or near-field communication (NFC) devices based on similar security technology found in smart cards.
* `OneTimePassword` - Authenticator is an authenticator app used as part of a two-factor/multi-factor authentication (2FA/MFA) scheme. It acts as an example of a “something you have” factor by generating one-time passwords (OTPs) on a smartphone or other mobile device.
* `Webauthn2fa` - WebAuthn is a browser-based API that allows for web applications to simplify and secure user authentication by using registered devices (phones, laptops, etc) as factors. It uses public key cryptography to protect users from advanced phishing attacks.
