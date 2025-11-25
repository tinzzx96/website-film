<?php
require_once '../../config/config.php';
require_once '../../config/tmdb_config.php';

header('Content-Type: application/json');

try {
    // Fetch discover movies dari TMDb (sorted by popularity)
    $data = fetchTMDb('/discover/movie?sort_by=popularity.desc&page=1');
    
    if (!$data || !isset($data['results'])) {
        throw new Exception('Failed to fetch from TMDb API');
    }
    
    // Ambil hanya 21 film pertama
    $movies = array_slice($data['results'], 0, 21);
    
    // Convert ke format website
    $result = [];
    foreach ($movies as $movie) {
        $result[] = convertTMDbMovie($movie);
    }
    
    echo json_encode($result, JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("Error in get_all_movies.php: " . $e->getMessage());
    
    // Fallback ke database jika API error
    $sql = "SELECT * FROM movies ORDER BY created_at DESC LIMIT 21";
    $dbResult = $conn->query($sql);
    
    $fallbackMovies = [];
    if ($dbResult && $dbResult->num_rows > 0) {
        while($row = $dbResult->fetch_assoc()) {
            $genreArray = json_decode($row['genre'], true);
            if (!is_array($genreArray)) {
                $genreArray = array_map('trim', explode(',', $row['genre']));
            }
            
            $fallbackMovies[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'rating' => (float)$row['rating'],
                'poster' => $row['poster'],
                'trailer' => $row['trailer'],
                'watchLink' => $row['watchLink'] ?? '',
                'year' => $row['year'],
                'duration' => $row['duration'],
                'genre' => $genreArray,
                'director' => $row['director'] ?? '',
                'plot' => $row['plot'] ?? ''
            ];
        }
    }
    
    echo json_encode($fallbackMovies, JSON_UNESCAPED_SLASHES);
}
?>