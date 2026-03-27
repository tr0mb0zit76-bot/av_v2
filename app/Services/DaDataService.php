<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DaDataService
{
    protected $token;
    protected $secret;
    protected $baseUrl = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs';
    
    public function __construct()
    {
        $this->token = config('services.dadata.token');
        $this->secret = config('services.dadata.secret');
    }
    
    /**
     * Поиск адресов
     */
    public function suggestAddress($query, $count = 10, $type = 'address')
    {
        if (strlen($query) < 2) {
            return ['suggestions' => []];
        }
        
        try {
            $params = [
                'query' => $query,
                'count' => $count,
                'language' => 'ru'
            ];
            
            // Для поиска только городов
            if ($type === 'city') {
                $params['from_bound'] = ['value' => 'city'];
                $params['to_bound'] = ['value' => 'city'];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/suggest/address', $params);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('DaData API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return ['suggestions' => []];
            
        } catch (\Exception $e) {
            Log::error('DaData Service error: ' . $e->getMessage());
            return ['suggestions' => []];
        }
    }
    
    /**
     * Поиск организаций
     */
    public function suggestParty($query, $count = 10)
    {
        if (strlen($query) < 2) {
            return ['suggestions' => []];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/suggest/party', [
                'query' => $query,
                'count' => $count
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['suggestions' => []];
            
        } catch (\Exception $e) {
            Log::error('DaData Party error: ' . $e->getMessage());
            return ['suggestions' => []];
        }
    }
}