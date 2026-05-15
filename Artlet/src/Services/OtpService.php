<?php

declare(strict_types=1);

namespace App\Services;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;

class OtpService
{
    private TwoFactorAuth $tfa;

    public function __construct()
    {
        $this->tfa = new TwoFactorAuth(
            new BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg'),
            'Artlet'
        );
    }

    //Generate Secret Once
    public function createSecret(): string
    {
        return $this->tfa->createSecret();
    }

    //QR code
    public function getQr(string $label, string $secret): string
    {
        return $this->tfa->getQRCodeImageAsDataUri($label, $secret);
    }

    //Verify code
    public function verify(string $secret, string $code): bool
    {
        return $this->tfa->verifyCode($secret, $code);
    }
}
