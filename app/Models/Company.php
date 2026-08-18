<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name', 'address', 'phone', 'email', 'logo_path', 'currency',
        'tagline', 'services_line', 'tax_rate', 'invoice_prefix', 'accent_color', 'settings',
    ];

    protected function casts(): array
    {
        return ['tax_rate' => 'float', 'settings' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function logoUrl(): string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return asset('images/logo.png');
    }

    public function logoFilesystemPath(): ?string
    {
        if ($this->logo_path) {
            $path = storage_path('app/public/'.$this->logo_path);
            if (is_file($path)) {
                return $path;
            }
        }
        $default = public_path('images/logo.png');

        return is_file($default) ? $default : null;
    }

    public function logoDataUri(): string
    {
        $path = $this->logoFilesystemPath();
        if (! $path) {
            return '';
        }
        $mime = @mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    public function taglineText(): string
    {
        return $this->tagline ?: "BUILDING TOMORROW'S SOLUTIONS TODAY";
    }

    public function servicesText(): string
    {
        return $this->services_line ?: "Computer Systems, Development, Management, Maintenance\nAdministration And Consultive Support";
    }

    public function addressText(): string
    {
        return $this->address ?: "Mug House One, Kanjokya Street,\nKamwokya, Kampala district";
    }

    public function phoneLine(): string
    {
        return $this->phone ?: '+256 773 078860  +256 783261162';
    }

    public function emailLines(): string
    {
        return $this->email ?: "codecatalystug@gmail.com\ninfo@codecatalystug.com";
    }

    public function displayNameUpper(): string
    {
        return strtoupper($this->name ?: 'CODE CATALYST LABS');
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    public function vatRate(): float
    {
        return (float) ($this->tax_rate ?: $this->setting('vat_rate', 18));
    }
}
