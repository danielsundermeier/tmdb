# TmDB API
Codeigniter TmDB API for www.themovieDB.org with fall-back-language

#Installation/Usage

Download the folder and drag it into your application folder. 
Fill the config/tmdb.php file with your API Key.

Loading the library:

    $this->load->library('tmdb');

Make call:

    $result = $this->tmdb->get_movie(99861, 'credits,videos');
    // Avengers 2 Info, Credits + Trailer
