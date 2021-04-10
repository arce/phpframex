<?php

/**
* Template engine
* @author  Ruslan Ismagilov <ruslan.ismagilov.ufa@gmail.com>
* @contributor  Armando Arce <armando.arce@gmail.com>
*/
class Template {
    /**
     * Content variables
     * @access private
     * @var array
     */
    private $vars = array();

    /**
     * Content delimiters
     * @access private
     * @var string
     */
    private $l_delim = '{{', $r_delim = '}}';
    
    public function countDim($array) {
	  if (is_bool($array)) return 0;
      if (is_array(reset($array)))
        $dim = $this->countDim(reset($array)) + 1;
      else
        $dim = 1;
      return $dim;
    }

    /**
     * Set template property in template file
     * @access public
     * @param string $key property name
     * @param string $value property value
     */
    public function assign( $key, $value ) {
        $this->vars[$key] = $value;
    }
    
    /**
     * Parce template file
     * @access public
     * @param string $template_file
     */
    public function parse( $template_file ) {
        if ( file_exists( $template_file ) ) {
            $content = file_get_contents($template_file);
            while (true) {
              $start = strpos($content,'{{>');
              if ($start === false) break;
              $end = strpos($content,'}}',$start);
              if ($end === false) break;
              $partial_file = substr($content,$start+4,$end-$start-4);
              $partial = file_get_contents('views/'.$partial_file.'.html');
              $partial_tag = substr($content,$start,$end-$start+2);
              $content = str_replace($partial_tag,$partial,$content);
		    }
            foreach ( $this->vars as $key => $value ) {
                $$key = $value;
                if ( is_array( $value ) || is_bool( $value) ) {
					if ($this->countDim($value)<=1) $value = [$value];
                    $content = $this->parsePair($key, $value, $content);
                } else {
                    $content = $this->parseSingle($key, (string) $value, $content);
                }
            }
            return $content;
        } else {
            exit( '<h1>Template error</h1>' );
        }
    }

    /**
     * Parsing content for single varliable
     * @access private
     * @param string $key property name
     * @param string $value property value
     * @param string $string content to replace
     * @param integer $index index of loop item
     * @return string replaced content
     */
    private function parseSingle( $key, $value, $string, $index = null ) {
        if ( isset( $index ) ) {
            $string = str_replace( $this->l_delim . '.' . $this->r_delim, $index+1, $string );
        }
        return str_replace( $this->l_delim . $key . $this->r_delim, $value, $string );
    }

    /**
     * Parsing content for loop varliable
     * @access private
     * @param string $variable loop name
     * @param string $value loop data
     * @param string $string content to replace
     * @return string replaced content
     */
    private function parsePair( $variable, $data, $string ) {
        $match = $this->matchPair($string, $variable);
        if (!$match) return $string;
        
        if (is_bool($data[0])) {
			$start = $this->l_delim . '#'. $variable . $this->r_delim;
			$end = $this->l_delim . '\/'. $variable . $this->r_delim;
            $endx = $this->l_delim . '/'. $variable . $this->r_delim;
            
            if (!$data[0])
              $string = preg_replace('/'.$start.'[\s\S]+?'.$end.'/', '', $string);
            else {
			  $string = str_replace($start,'',$string);
              $string = str_replace($endx,'',$string);
			}

            $start = $this->l_delim . '^'. $variable . $this->r_delim;
			$startx = $this->l_delim . '\^'. $variable . $this->r_delim;
            $end = $this->l_delim . '~'. $variable . $this->r_delim;
            
            if ($data[0])
              $string = preg_replace('/'.$startx.'[\s\S]+?'.$end.'/', '', $string);
            else {
			  $string = str_replace($start,'',$string);
              $string = str_replace($end,'',$string);
			}
            return $string;
        }
        
        $str = '';
        foreach ( $data as $k_row => $row ) {
            $temp = $match['1'];
            foreach( $row as $key => $val ) {
                if( !is_array( $val ) ) {
                    $index = array_search( $k_row, array_keys( $data ) );
                    $temp = $this->parseSingle( $key, $val, $temp, $index );
                } else {
                    $temp = $this->parsePair( $key, $val, $temp );
                }
            }
            $str .= $temp;
        }

        return str_replace( $match['0'], $str, $string );
    }

    /**
     * Match loop pair
     * @access private
     * @param string $string content with loop
     * @param string $variable loop name
     * @return string matched content
     */
    private function matchPair( $string, $variable ) {
        if ( !preg_match("|" . preg_quote($this->l_delim) . '#' . $variable . preg_quote($this->r_delim) . "(.+?)". preg_quote($this->l_delim) . '/' . $variable . preg_quote($this->r_delim) . "|s", $string, $match ) ) {
            return false;
        }

        return $match;
    }
}

function view($filename,$variables=[]) {
    if (!isset($template)) {
      $template = new Template();
    }
    foreach ($variables as $key => $value) {
      $template->assign($key,$value);
    }
	if (file_exists('views/'.$filename.'.html'))
      return $template->parse('views/'.$filename.'.html');
	if (file_exists('views/'.$filename.'.php')) {
	  extract($variables);
	  ob_start();
	  include('views/'.$filename.'.php');
	  return ob_get_clean();
    }
	return 'Template error';
}
?>
