<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;

trait Hashidable
{
    // Get route key
    public function getRouteKey()
    {
        return $this->encodeKey($this->getKey());
    }

    // Encode key by Hashids
    public function encodeKey(int|string $key): string
    {
        return app("hashids")->encode($key);
    }

    // Decode hashid to original key
    public function decodeKey(string $hashid): int|string|null
    {
        $decoded = app("hashids")->decode($hashid);
        return $decoded[0] ?? null;
    }

    // Find model by hashid
    public static function findByHashid(string $hashid): ?static
    {
        $id = (new self())->decodeKey($hashid);
        if ($id) {
            return static::find($id);
        }
        throw (new ModelNotFoundException())->setModel(static::class, [$hashid]);
    }
}
