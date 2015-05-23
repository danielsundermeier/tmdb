<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

  /**
   * @author Daniel Sundermeier
   * 
   * origninally developed for www.serienguide.tv
   */

  class TmDB
  {
    const API_VERSION = '3';
    const API_URL = 'api.themoviedb.org';
    const API_SCHEME = 'http://';
    const API_SCHEME_SSL = 'https://';
    
    /**
     * CI Object
     */
    protected $ci;
    
    /**
     * The API-key
     *
     * @var string
     */
    protected $apikey;

    /**
     * Error message
     * 
     * @var string
     */    
    protected $error = '';
    
    /**
     * API Scheme
     *
     * @var string
     */
    protected $apischeme;

    /**
     * API Language
     *
     * @var string
     */
    protected $default_language;
    protected $fallback_language;
    
    /**
     * Default constructor
     *
     * @return void
     */
    public function __construct($scheme = TMDb::API_SCHEME) 
    {
        // Load config file
        $this->ci = &get_instance();
        $this->ci->load->config('tmdb');

        //Assign Api Key
        $this->apikey = (string) $this->ci->config->item('tmdb_api');
        
        // Language
        $this->default_language = (string) $this->ci->config->item('tmdb_default_lang');
        $this->fallback_language = (string) $this->ci->config->item('tmdb_fallback_lang');
        
        // Scheme
        $this->apischeme = ($scheme == TMDb::API_SCHEME) ? TMDb::API_SCHEME : TMDb::API_SCHEME_SSL;
    }
    
    /**
     * Get Error Message
     * 
     * @return string
     */
    public function get_error()
    {
      return $this->error;
    }
    
    /**
     * Get the primary information about a TV episode by combination of a season and episode number.
     * 
     * @param number $tv_id
     * @param number $season_number
     * @param number $episode_number
     * 
     * @return array
     */
    public function get_episode($tv_id, $season_number, $episode_number)
    {
      // Params
      $params = array(
        'language' => $this->default_language
      );
      
      $result = $this->make_call('tv/'.$tv_id.'/season/'.$season_number.'/episode/'.$episode_number, $params);
      $name_deutsch = $result['name'];
      
      if(empty($result['name']) || empty($result['overview']))
      {
        $params = array(
          'language' => $this->fallback_language
        );
        
        if(empty($name_deutsch))
        {
          return $this->make_call('tv/'.$tv_id.'/season/'.$season_number.'/episode/'.$episode_number, $params);
        }
        else
        {
          $result = $this->make_call('tv/'.$tv_id.'/season/'.$season_number.'/episode/'.$episode_number, $params);
          $result['name'] = $name_deutsch;
          
          return $result;
        }
      }
      
      return $result;
    }

    /**
     * Get the TV episode credits by combination of season and episode number.
     * 
     * @param number $tv_id
     * @param number $season_number
     * @param number $episode_number
     * 
     * @return array
     */
    public function get_episode_credits($tv_id, $season_number, $episode_number)
    {
      // Params
      $params = array(
        'language' => $this->default_language
      );
    
      return $this->make_call('tv/'.$tv_id.'/season/'.$season_number.'/episode/'.$episode_number.'/credits', $params);
    }
    
    public function find($id, $source = FALSE)
    {
      if($source)
      {
        $params = array(
          'external_source' => $source
        );
      }
      
      return $this->make_call('find/'.$id, $params);
    }
    
    /**
     * Get all Genres
     * 
     * @return array
     */
    public function get_genres()
    {
      // Params
      $params = array(
        'language' => 'en'
      );
      
      $movie = $this->make_call('genre/movie/list', $params);
      $tv = $this->make_call('genre/movie/list', $params);
      
      return array_unique(array_merge($movie, $tv)); 
    }
    
    /**
     * Get the basic movie information for a specific movie id.
     * 
     * @param int $id
     * @param string $lang = FALSE
     * 
     * @return array
     */
    public function get_movie($id, $lang = NULL)
    {
      // Default language
      if(empty($lang)) { $lang = $this->default_language; }

      // Params
      $params = array(
        'language' => $lang
      );
      
      $result = $this->make_call('movie/'.$id, $params);
      
      if(empty($result['title']))
      {
        $params = array(
          'language' => $this->fallback_language
        );
        
        return $this->make_call('movie/'.$id, $params);
      }
      
      return $result;
    }
    
    /**
     * Get the changes for a specific movie id or all movie changes.
     * 
     * @param int $id = FALSE
     * 
     * @return array
     */
    public function get_movie_changes($id = FALSE)
    {
      if($id)
      {
        return $this->make_call('movie/'.$id.'/changes');
      }
      else
      {      
        $page = 1;
        $result = array();
        do 
        {
          $params = array(
            'page' => $page
          );
          $r = $this->make_call('movie/changes', $params);
          
          $total_pages = $r['total_pages'];
          $r = $r['results'];
          
          $result = array_merge($result, $r);
          
          $page++;
        }
        while($page <= $total_pages);
        
        return $result;
      }
    }
    
    /**
     * Get the cast and crew information for a specific movie id.
     * 
     * @param number $id
     * 
     * @return array
     */
    public function get_movie_credits($id)
    {
      return $this->make_call('movie/'.$id.'/credits');
    }
    
    /**
     * Get the list of upcoming movies by release date.
     * 
     * @return array
     */
    public function get_movie_upcoming()
    {
      $page = 1;
      $result = array();
      do
      {
        $params = array(
          'page'      => $page,
          'language'  => $this->default_language
        );
        $r = $this->make_call('movie/upcoming', $params);
      
        $total_pages = $r['total_pages'];
        $r = $r['results'];
      
        $result = array_merge($result, $r);
      
        $page++;
      }
      while($page <= $total_pages);
      
      return $result;
    }
    
    /**
     * Get the cast and crew information for a specific movie id.
     *
     * @param int $id
     * @param string $lang = FALSE
     * @return array
     */
    public function get_movie_videos($id, $lang = FALSE)
    {
      // Default language
      if(empty($lang)) { $lang = $this->default_language; }
      
      // Params
      $params = array(
          'language' => $lang
      );
      
      return $this->make_call('movie/'.$id.'/videos', $params);
    }
    
    /**
     * Get the general person information for a specific id.
     * 
     * @param number $id
     * @param string $lang
     * @return array
     */
    public function get_person($id, $lang = NULL)
    {
      // Default language
      if(empty($lang)) { $lang = $this->default_language; }
      
      // Params
      $params = array(
        'language' => $lang
      );
      
      $result = $this->make_call('person/'.$id, $params);
      
      if(empty($result['name']))
      {
        $params = array(
          'language' => $this->fallback_language
        );
        
        return $this->make_call('person/'.$id, $params);
      }
      
      return $result;
    }
    
    /**
     * Get the changes for a specific person id or all. 
     * 
     * @param int $id
     * @return array
     */
    public function get_person_changes($id = FALSE)
    {
      if($id)
      {
        return $this->make_call('person/'.$id.'/changes');
      }
      else
      {
        $page = 1;
        $result = array();
        do
        {
          $params = array(
            'page' => $page
          );
          $r = $this->make_call('person/changes', $params);
    
          $total_pages = $r['total_pages'];
          $r = $r['results'];
    
          $result = array_merge($result, $r);
    
          $page++;
        }
        while($page <= $total_pages);
    
        return $result;
      }
    }
    
    /**
     * Get the primary information about a TV series by id.
     * 
     * @param int $id
     * @param string $lang
     * @return array
     */
    public function get_tv($id, $lang = NULL)
    {
      // Default language
      if(empty($lang)) { $lang = $this->default_language; }
    
      // Params
      $params = array(
        'language' => $lang
      );

      $result = $this->make_call('tv/'.$id, $params);
      
      if(empty($result['overview']))
      {
        $params = array(
          'language' => $this->fallback_language
        );
      
        return $this->make_call('tv/'.$id, $params);
      }
      
      return $result;
    }
    
    /**
     * Get the changes for a specific TV series or all.
     * 
     * @param int $id = FALSE
     * @return array
     */
    public function get_tv_changes($id = FALSE)
    {
      if($id)
      {
        return $this->make_call('tv/'.$id.'/changes');
      }
      else
      {
        $page = 1;
        $result = array();
        do
        {
          $params = array(
            'page' => $page
          );
          $r = $this->make_call('tv/changes', $params);
    
          $total_pages = $r['total_pages'];
          $r = $r['results'];
    
          $result = array_merge($result, $r);
    
          $page++;
        }
        while($page <= $total_pages);
    
        return $result;
      }
    }
    
    /**
     * Get the external ids that we have stored for a TV series.
     * 
     * @param int $id
     * @return int
     */
    public function get_tv_external_ids($id)
    {
      $result = $this->make_call('tv/'.$id.'/external_ids');
      
      return $result['tvdb_id'];
    }
    
    /**
     * Get the cast and crew information for a specific movie id.
     *
     * @param int $id
     * @param string $lang = FALSE
     * @return array
     */
    public function get_tv_videos($id, $lang = NULL)
    {
      // Default language
      if(empty($lang)) { $lang = $this->default_language; }
    
      // Params
      $params = array(
        'language' => $lang
      );
    
      return $this->make_call('tv/'.$id.'/videos', $params);
    }
    
    public function get_season($id, $season_number)
    {
      // Params
      $params = array(
        'language' => $this->default_language
      );
      
      $result = $this->make_call('tv/'.$id.'/season/'.$season_number, $params);
      
      if(empty($result['overview']))
      {
        $params = array(
          'language' => $this->_fallback_language
        );
        
        return $this->make_call('tv/'.$id.'/season/'.$season_number, $params);
      }
      
      return $result;
    }
    
    /**
     * Look up a TV season's changes by season ID. 
     * 
     * @param int $id
     * @return array
     */
    public function get_season_changes($id)
    {      
      return $this->make_call('tv/season/'.$id.'/changes');
    }
    
    /**
     * Get the cast & crew credits for a TV season by season number.
     *
     * @param int $id
     * @param int $season_number
     * @return array
     */
    public function get_season_credits($id, $season_number)
    {
      return $this->make_call('tv/'.$id.'/season/'.$season_number.'/credits');
    }
    
    /**
     * Search for movies by title. 
     * 
     * @param mixed $query
     * @param int $page
     * @param string $adult
     * @param string $year
     * @return array
     */
    public function search_movie($query, $page = 1, $adult = false, $year = null)
    {
      $params = array(
        'query' => $query,
        'page' => (int) $page,
        'include_adult' => (bool) $adult,
        'year' => $year,
        'language' => $this->default_language
      );
      
      return $this->make_call('search/movie', $params);
    }
    
    /**
     * Search for TV Shows by title.
     *
     * @param unknown $query
     * @param number $page
     * @param string $adult
     * @param string $year
     */
    public function search_tv($query, $page = 1, $adult = false, $year = null)
    {
      $params = array(
        'query' => $query,
        'page' => (int) $page,
        'language' => $this->default_language,
      );
    
      return $this->make_call('search/tv', $params);
    }
    
    /**
     * 
     * 
     * @param string $call
     * @param array $params
     * @param string $method
     * @return mixed
     */
    private function make_call($call, $params = NULL, $method = TMDb::GET)
    {
      // API Key einfügen
      $params['api_key'] = $this->apikey;
      
      // URL erstellen
      $url = $this->apischeme . TMDb::API_URL . '/' . TMDb::API_VERSION . '/' . $call . '?' . http_build_query($params);
      
      // Debug
      // echo "URL: ".$url."<br>";
      
      // Anfrage senden
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
      
      $response = curl_exec($ch);
      
      $http_code = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if($http_code{0} != 2)
      {
        $this->error = '[URI='.$_SERVER["REQUEST_URI"].'] Fehler: '.$response.' ['.$url.']';        
        // Debug
        //log_message('DEBUG', $this->error);
        
        return FALSE;
      }
      
      curl_close($ch);
      
      return json_decode($response, true);
    }
    
  }