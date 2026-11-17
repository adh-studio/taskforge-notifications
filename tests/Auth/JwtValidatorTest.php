<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Auth;

use PHPUnit\Framework\TestCase;
use TaskForge\Notifications\Auth\JwtValidator;
use TaskForge\Notifications\Domain\Exception\AuthenticationException;
use TaskForge\Notifications\Tests\Support\FrozenClock;
use TaskForge\Notifications\Tests\Support\JwtForge;

final class JwtValidatorTest extends TestCase
{
    private const SECRET = 'secret-partage-taskforge-pour-les-tests';

    private function validateur(string $instant = '2026-01-10T12:00:00+00:00'): JwtValidator
    {
        return new JwtValidator(self::SECRET, FrozenClock::a($instant));
    }

    public function testJetonValideRenvoieLIdentifiantUtilisateur(): void
    {
        $jwt = JwtForge::signer(['sub' => 42, 'exp' => 9999999999], self::SECRET);

        $userId = $this->validateur()->utilisateurDepuisEntete('Bearer ' . $jwt);

        self::assertSame(42, $userId);
    }

    public function testEnteteSansBearerEstRejetee(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->validateur()->utilisateurDepuisEntete('Token abc');
    }

    public function testEnteteAbsenteEstRejetee(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->validateur()->utilisateurDepuisEntete(null);
    }

    public function testSignatureInvalideEstRejetee(): void
    {
        $jwt = JwtForge::signer(['sub' => 42, 'exp' => 9999999999], 'mauvais-secret');

        $this->expectException(AuthenticationException::class);
        $this->validateur()->validerEtExtraireUtilisateur($jwt);
    }

    public function testJetonExpireEstRejete(): void
    {
        // exp au 2026-01-01, horloge au 2026-01-10 : expire.
        $jwt = JwtForge::signer(['sub' => 42, 'exp' => 1767225600], self::SECRET);

        $this->expectException(AuthenticationException::class);
        $this->validateur()->validerEtExtraireUtilisateur($jwt);
    }

    public function testAlgorithmeNoneEstRefuse(): void
    {
        $jwt = JwtForge::signer(['sub' => 42], self::SECRET, 'none');

        $this->expectException(AuthenticationException::class);
        $this->validateur()->validerEtExtraireUtilisateur($jwt);
    }

    public function testJetonMalformeEstRejete(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->validateur()->validerEtExtraireUtilisateur('deux.segments');
    }

    public function testSubAbsentEstRejete(): void
    {
        $jwt = JwtForge::signer(['exp' => 9999999999], self::SECRET);

        $this->expectException(AuthenticationException::class);
        $this->validateur()->validerEtExtraireUtilisateur($jwt);
    }

    public function testCleSecreteVideEstRefuseeALaConstruction(): void
    {
        $this->expectException(AuthenticationException::class);
        new JwtValidator('', FrozenClock::a('2026-01-10T12:00:00+00:00'));
    }

    public function testJetonPasEncoreValideEstRejete(): void
    {
        // nbf dans le futur par rapport a l'horloge figee.
        $jwt = JwtForge::signer(['sub' => 42, 'nbf' => 9999999999], self::SECRET);

        $this->expectException(AuthenticationException::class);
        $this->validateur()->validerEtExtraireUtilisateur($jwt);
    }
}
