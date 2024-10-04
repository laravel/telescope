<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\Crypt;

class EntryModelEncryptionCast extends Json implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        return parent::decode(
            $this->decryptContent($value)
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return $this->encryptContent(parent::encode(
            $value
        ));
    }

    /**
     * Decode/Decrypt content attribute. Check that is encrypted.
     *
     * @return mixed
     */
    protected function decryptContent($content)
    {
        if (
            ! $content
            || ! $this->encryptionIsEnabled()
            || substr($content, 0, 8) != 'encrypt:'
        ) {
            return $content;
        }

        return Crypt::decryptString(substr($content, 8, strlen($content)));
    }

    /**
     * Encode/Encrypt content attribute. Add 'encrypt' prefix for mark
     * that string is encrypted.
     *
     * @return mixed
     */
    protected function encryptContent($content)
    {
        if (! $content || ! $this->encryptionIsEnabled()) {
            return $content;
        }

        return 'encrypt:'.Crypt::encryptString($content);
    }

    /**
     * Check that encryption is enabled.
     *
     * @return bool
     */
    protected function encryptionIsEnabled()
    {
        return config('telescope.encryption');
    }

}
