<?php
namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
class KeycloakService {
    private Client $client;
    public function __construct() {
        $this->client = new Client(['base_uri' => config('keycloak.base_url'), 'timeout' => 5.0]);
    }
    public function validateToken(string $token): array {
        $response = $this->client->post(config('keycloak.introspection_endpoint'), ['form_params' => ['client_id' => config('keycloak.client_id'), 'client_secret' => config('keycloak.client_secret'), 'token' => $token]]);
        $data = json_decode((string) $response->getBody(), true);
        if (empty($data['active'])) throw new \RuntimeException('Token is not active or invalid');
        return $data;
    }
    public function getUserRoles(array $tokenClaims): array { return $tokenClaims['realm_access']['roles'] ?? []; }
    public function hasRole(array $tokenClaims, string $role): bool { return in_array($role, $this->getUserRoles($tokenClaims), true); }
    public function getTenantId(array $tokenClaims): ?string { return $tokenClaims['tenant_id'] ?? $tokenClaims['tenantId'] ?? null; }
}
