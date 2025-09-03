<?php

namespace App\Models;

use Core\Model;

class User extends Model
{

    /**
     * The table associated with the model.
     */
    protected string $table = 'users';

    /**
     * The primary key for the model.
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public bool $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'email',
        'password_hash',
        'email_verified',
        'email_verification_token',
        'email_verification_sent_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected array $hidden = [
        'password_hash',
        'email_verification_token',
    ];

    /**
     * Verify a plain password against the user's hashed password.
     */
    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->password_hash);
    }

    /**
     * Find a user by email address.
     */
    public static function findByEmail(string $email): ?self
    {
        // Assuming Model has a where() and first() method
        return static::where('email', $email)->first();
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user's email is verified.
     */
    public function isEmailVerified(): bool
    {
        return (bool) $this->email_verified;
    }

    /**
     * Check if user needs email verification.
     */
    public function needsEmailVerification(): bool
    {
        return !$this->isEmailVerified();
    }

    /**
     * Generate email verification token.
     */
    public function generateEmailVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update([
            'email_verification_token' => $token,
            'email_verification_sent_at' => date('Y-m-d H:i:s')
        ]);
        return $token;
    }

    /**
     * Generate password reset token.
     */
    public function generatePasswordResetToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update([
            'password_reset_token' => $token,
            'password_reset_sent_at' => date('Y-m-d H:i:s')
        ]);
        return $token;
    }

    /**
     * Verify email with token.
     */
    public function verifyEmailWithToken(string $token): bool
    {
        if ($this->email_verification_token !== $token) {
            return false;
        }

        // Check if token is not expired (24 hours)
        if ($this->email_verification_sent_at) {
            $sentAt = strtotime($this->email_verification_sent_at);
            $expiryTime = $sentAt + (24 * 60 * 60); // 24 hours
            
            if (time() > $expiryTime) {
                return false;
            }
        }

        $this->update([
            'email_verified' => 1,
            'email_verification_token' => null,
            'email_verification_sent_at' => null
        ]);

        return true;
    }

    /**
     * Check if verification token is expired.
     */
    public function isVerificationTokenExpired(): bool
    {
        if (!$this->email_verification_sent_at) {
            return true;
        }

        $sentAt = strtotime($this->email_verification_sent_at);
        $expiryTime = $sentAt + (24 * 60 * 60); // 24 hours
        
        return time() > $expiryTime;
    }

    /**
     * Get the user's full name.
     */
    public function fullName(): string
    {
        return $this->name;
    }
}
