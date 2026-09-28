<?php

namespace App\Services\Auth;

use App\Models\MfaRecoveryCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class MfaService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrSvg(User $user, string $secret): string
    {
        $otpUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd);
        $writer = new Writer($renderer);

        return $writer->writeString($otpUrl);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code) === true;
    }

    /**
     * @return list<string>
     */
    public function issueRecoveryCodes(User $user): array
    {
        $user->recoveryCodes()->delete();

        $plain = [];

        for ($i = 0; $i < 8; $i++) {
            $code = Str::upper(Str::random(10));
            $plain[] = $code;
            $user->recoveryCodes()->create([
                'code_hash' => Hash::make($code),
            ]);
        }

        return $plain;
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        foreach ($user->recoveryCodes()->whereNull('used_at')->get() as $stored) {
            if (Hash::check($code, $stored->code_hash)) {
                $stored->forceFill(['used_at' => now()])->save();

                return true;
            }
        }

        return false;
    }

    public function enable(User $user, string $secret): void
    {
        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled_at' => now(),
        ])->save();
    }

    public function reset(User $user): void
    {
        $user->recoveryCodes()->delete();
        $user->forceFill([
            'mfa_secret' => null,
            'mfa_enabled_at' => null,
        ])->save();
    }
}
