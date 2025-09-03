<?php

namespace Core;

use App\Models\User;

class Auth
{
    protected const SESSION_KEY = 'auth_user_id';

    protected ?User $user = null;
    protected bool $resolved = false;

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?User
    {
        if ($this->resolved) {
            return $this->user;
        }

        $session = App::getInstance()->session();
        $id = $session->get(self::SESSION_KEY);

        if ($id) {
            $this->user = User::find($id);
        }

        $this->resolved = true;

        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->user()?->id ?? null;
    }

    public function login(User $user): void
    {
        App::getInstance()->session()->put(self::SESSION_KEY, $user->id);
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        App::getInstance()->session()->forget(self::SESSION_KEY);
        $this->user = null;
        $this->resolved = true;
    }

    public function attempt(array $credentials): bool
    {
        $user = $this->validate($credentials);

        if ($user) {
            $this->login($user);
            return true;
        }

        return false;
    }

    public function validate(array $credentials): ?User
    {
        if (!isset($credentials['email'], $credentials['password'])) {
            return null;
        }

        $user = User::findByEmail($credentials['email']);

        if (!$user || !$user->verifyPassword($credentials['password'])) {
            return null;
        }

        return $user;
    }

    public function once(User $user): void
    {
        $this->user = $user;
        $this->resolved = true;
    }
}
