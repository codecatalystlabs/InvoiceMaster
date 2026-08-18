<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

class YoPayments
{
    public function __construct(
        protected string $username,
        protected string $password,
        protected string $mode = 'sandbox',
    ) {
        $this->mode = strtolower($mode) === 'live' ? 'live' : 'sandbox';
    }

    public static function forCompany(Company $company): ?self
    {
        $username = trim((string) ($company->setting('yo_username') ?: config('services.yo.username')));
        $password = self::secret($company);
        if ($username === '' || $password === '') {
            return null;
        }

        $mode = (string) ($company->setting('yo_mode') ?: config('services.yo.mode', 'sandbox'));

        return new self($username, $password, $mode);
    }

    public static function enabled(Company $company): bool
    {
        $provider = strtolower(trim((string) ($company->setting('payment_provider') ?: 'yo')));
        if ($provider !== '' && $provider !== 'yo') {
            return false;
        }

        return self::forCompany($company) !== null;
    }

    public static function secret(Company $company): string
    {
        $stored = (string) $company->setting('yo_password', '');
        if ($stored !== '') {
            try {
                return decrypt($stored);
            } catch (Throwable) {
                return $stored;
            }
        }

        return (string) config('services.yo.password', '');
    }

    public static function encryptSecret(string $plain): string
    {
        return encrypt($plain);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function endpoint(): string
    {
        return $this->mode === 'live'
            ? (string) config('services.yo.live_url')
            : (string) config('services.yo.sandbox_url');
    }

    public static function normalizeMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '256') && strlen($digits) >= 12) {
            return substr($digits, 0, 12);
        }
        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            return '256'.substr($digits, 1, 9);
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '256'.$digits;
        }

        return $digits;
    }

    public static function guessMethod(string $msisdn): string
    {
        $prefix = substr(self::normalizeMsisdn($msisdn), 0, 5);

        return match ($prefix) {
            '25670', '25674', '25675', '25620' => 'airtel_money',
            default => 'mtn_momo',
        };
    }

    /**
     * Ask the subscriber to approve a collection (receive money into the Yo account).
     *
     * @return array<string, string>
     */
    public function deposit(string $msisdn, float $amount, string $narrative, array $options = []): array
    {
        $msisdn = self::normalizeMsisdn($msisdn);
        $amountText = abs($amount - round($amount)) < 0.001
            ? (string) (int) round($amount)
            : number_format($amount, 2, '.', '');

        $fields = [
            'APIUsername' => $this->username,
            'APIPassword' => $this->password,
            'Method' => 'acdepositfunds',
            'NonBlocking' => ($options['non_blocking'] ?? true) ? 'TRUE' : 'FALSE',
            'Account' => $msisdn,
            'Amount' => $amountText,
            'Narrative' => $narrative,
        ];
        if (! empty($options['external_reference'])) {
            $fields['ExternalReference'] = (string) $options['external_reference'];
        }
        if (! empty($options['provider_reference_text'])) {
            $fields['ProviderReferenceText'] = (string) $options['provider_reference_text'];
        }
        if (! empty($options['instant_notification_url'])) {
            $fields['InstantNotificationUrl'] = (string) $options['instant_notification_url'];
        }
        if (! empty($options['failure_notification_url'])) {
            $fields['FailureNotificationUrl'] = (string) $options['failure_notification_url'];
        }

        return $this->request($fields);
    }

    /**
     * @return array<string, string>
     */
    public function checkStatus(?string $transactionReference, ?string $externalReference = null): array
    {
        $fields = [
            'APIUsername' => $this->username,
            'APIPassword' => $this->password,
            'Method' => 'actransactioncheckstatus',
            'DepositTransactionType' => 'PULL',
        ];
        if ($transactionReference) {
            $fields['TransactionReference'] = $transactionReference;
        }
        if ($externalReference) {
            $fields['PrivateTransactionReference'] = $externalReference;
        }

        return $this->request($fields);
    }

    public function verifyIpn(array $payload): bool
    {
        $signature = base64_decode((string) ($payload['signature'] ?? ''), true);
        if ($signature === false || $signature === '') {
            return false;
        }

        $data = ($payload['date_time'] ?? '')
            .($payload['amount'] ?? '')
            .($payload['narrative'] ?? '')
            .($payload['network_ref'] ?? '')
            .($payload['external_ref'] ?? '')
            .($payload['msisdn'] ?? '');

        $certPath = $this->certificatePath();
        if (! is_file($certPath)) {
            return false;
        }

        $key = openssl_pkey_get_public((string) file_get_contents($certPath));
        if ($key === false) {
            return false;
        }

        return openssl_verify($data, $signature, $key) === 1;
    }

    public function certificatePath(): string
    {
        $file = $this->mode === 'live'
            ? 'Yo_Uganda_Public_Certificate.crt'
            : 'Yo_Uganda_Public_Sandbox_Certificate.crt';

        return resource_path('certs/yo/'.$file);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    protected function request(array $fields): array
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate><Request>';
        foreach ($fields as $tag => $value) {
            $xml .= '<'.$tag.'>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$tag.'>';
        }
        $xml .= '</Request></AutoCreate>';

        try {
            $response = Http::timeout(90)
                ->connectTimeout(20)
                ->withHeaders([
                    'Content-Type' => 'text/xml',
                    'Content-Transfer-Encoding' => 'text',
                ])
                ->withBody($xml, 'text/xml')
                ->post($this->endpoint());
        } catch (ConnectionException $e) {
            Log::warning('Yo Payments connection failed', ['mode' => $this->mode, 'error' => $e->getMessage()]);

            return ['Status' => 'ERROR', 'StatusMessage' => 'Could not reach Yo Payments.', 'TransactionStatus' => 'INDETERMINATE'];
        }

        if (! $response->successful() || trim($response->body()) === '') {
            Log::warning('Yo Payments HTTP error', ['status' => $response->status(), 'mode' => $this->mode]);

            return ['Status' => 'ERROR', 'StatusMessage' => 'Yo Payments returned an empty or failed response.', 'TransactionStatus' => 'INDETERMINATE'];
        }

        return $this->parse($response->body());
    }

    /**
     * @return array<string, string>
     */
    protected function parse(string $body): array
    {
        try {
            $xml = new SimpleXMLElement($body);
        } catch (Throwable $e) {
            Log::warning('Yo Payments XML parse failed', ['error' => $e->getMessage()]);

            return ['Status' => 'ERROR', 'StatusMessage' => 'Invalid response from Yo Payments.', 'TransactionStatus' => 'INDETERMINATE'];
        }

        $response = $xml->Response ?? $xml;
        $out = [];
        foreach ([
            'Status', 'StatusCode', 'StatusMessage', 'TransactionStatus',
            'ErrorMessageCode', 'ErrorMessage', 'TransactionReference',
            'MNOTransactionReferenceId', 'IssuedReceiptNumber', 'Amount',
        ] as $key) {
            if (isset($response->{$key}) && (string) $response->{$key} !== '') {
                $out[$key] = (string) $response->{$key};
            }
        }

        return $out;
    }
}
