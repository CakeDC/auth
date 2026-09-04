<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2026, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Auth\Test\TestCase\Traits;

use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Traits\ReCaptchaTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class ReCaptchaTraitTest extends TestCase
{
    protected function request(mixed $parsedBody): ServerRequest
    {
        /** @var \Cake\Http\ServerRequest $request */
        $request = ServerRequestFactory::fromGlobals(['REMOTE_ADDR' => '127.0.0.1']);

        return $request->withParsedBody($parsedBody);
    }

    /**
     * A subject that uses the trait and stubs the actual verification so the tests
     * never reach _getReCaptchaInstance()/the google/recaptcha dependency and can
     * observe whether the guard short-circuited.
     *
     * @param bool $return value the stubbed validateReCaptcha() returns
     * @return object
     */
    protected function subject(bool $return = true): object
    {
        return new class ($return) {
            use ReCaptchaTrait;

            public bool $validatorCalled = false;
            public array $validatorArgs = [];

            public function __construct(protected bool $return)
            {
            }

            public function validateReCaptcha(string $recaptchaResponse, string $clientIp): bool
            {
                $this->validatorCalled = true;
                $this->validatorArgs = [$recaptchaResponse, $clientIp];

                return $this->return;
            }
        };
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function tokenlessProvider(): array
    {
        return [
            'missing field' => [[]],
            'empty string' => [['g-recaptcha-response' => '']],
            'array value' => [['g-recaptcha-response' => ['nested']]],
            'object body' => [(object)['g-recaptcha-response' => 'x']],
        ];
    }

    /**
     * A missing/empty/non-string token is invalid and must not reach the verifier.
     *
     * @param mixed $parsedBody parsed request body
     * @return void
     */
    #[DataProvider('tokenlessProvider')]
    public function testTokenlessRequestFailsWithoutCallingValidator(mixed $parsedBody): void
    {
        $subject = $this->subject(true);

        $result = $subject->validateReCaptchaFromRequest($this->request($parsedBody));

        $this->assertFalse($result);
        $this->assertFalse(
            $subject->validatorCalled,
            'The guard must short-circuit before validateReCaptcha().',
        );
    }

    /**
     * A real token is forwarded verbatim to validateReCaptcha() with the client IP.
     *
     * @return void
     */
    public function testValidTokenDelegatesToValidator(): void
    {
        $subject = $this->subject(true);

        $result = $subject->validateReCaptchaFromRequest(
            $this->request(['g-recaptcha-response' => 'the-token']),
        );

        $this->assertTrue($result);
        $this->assertTrue($subject->validatorCalled);
        $this->assertSame(['the-token', '127.0.0.1'], $subject->validatorArgs);
    }
}
