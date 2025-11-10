<?php
// config.php - Backend configuration for Musicfy with Spotify API

// Enable CORS for frontend requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Spotify API Configuration
define('SPOTIFY_CLIENT_ID', '669799d628c34cd79b49bf9d059eeb74');
define('SPOTIFY_CLIENT_SECRET', '6111376b1fde45b0abffa7a7861862a9');
define('SPOTIFY_API_URL', 'https://api.spotify.com/v1/');

// Get Spotify Access Token
function getSpotifyAccessToken() {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    return null;
}

// Function to make Spotify API requests
function makeSpotifyRequest($endpoint, $accessToken) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, SPOTIFY_API_URL . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {

        return json_decode($response, true);
    } else {
        return ['error' => 'Spotify API request failed with code: ' . $httpCode];
    }
}

// Function to search music on Spotify
function searchSpotify($query) {
    $accessToken = getSpotifyAccessToken();
    
    if (!$accessToken) {
        return ['error' => 'Failed to get Spotify access token'];
    }
    
    $endpoint = 'search?q=' . urlencode($query) . '&type=track&limit=20';
    echo makeSpotifyRequest($endpoint, $accessToken);
    return makeSpotifyRequest($endpoint, $accessToken);
}

// Handle search request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'search') {
    $query = $_GET['query'] ?? '';
    
    if (!empty($query)) {
        $results = searchSpotify($query);
        header('Content-Type: application/json');
        echo json_encode($results);
    } else {
        echo json_encode(['error' => 'Empty query']);
    }
    exit;
}

// Handle get track request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_track') {
    $trackId = $_GET['track_id'] ?? '';
    
    if (!empty($trackId)) {
        $accessToken = getSpotifyAccessToken();
        if ($accessToken) {
            $results = makeSpotifyRequest('tracks/' . $trackId, $accessToken);
            header('Content-Type: application/json');
            echo json_encode($results);
        } else {
            echo json_encode(['error' => 'Failed to get access token']);
        }
    } else {
        echo json_encode(['error' => 'Empty track ID']);
    }
    exit;
}
?>