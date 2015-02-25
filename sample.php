<?php

// This is the 'Visualizer' for the SPADE project. Research assistants
// in the clinic will use this program to generate a visualization
// of the T-Scores for a subject based on their PROMIS survey.
//
//========================================================================= 

//--------------------------------------------------------------------------
// Required libraries
//--------------------------------------------------------------------------
require_once('SPADE/Connection.php');

//--------------------------------------------------------------------------
// Hard-coded Configurations
//--------------------------------------------------------------------------
// n/a

//-------------------------------------------------------------------------
// Constants
//-------------------------------------------------------------------------
// n/a

// START CREATING THE CLASS for this executable here. Everything will
// be in the class, and then below this we will simply create the class.

// MIGHT BE ABLE to move constants into the class since we're now planning
// on extending the Connection class.

//
class Visualizer extends Connection {

  protected $record_id;
  protected $authkey;

  protected $username;

  protected $viz_html;
  protected $bottom_html;

  protected $studyid;
  protected $tscores;
 
  function __construct($app) {

    // Parent constructor
    parent::__construct($app);

    //-------------------------------------------------------------------------
    // Read in passed values
    //-------------------------------------------------------------------------
    // Record_id is passed in the GET variable 'record'.
    // REDCap passes 'authkey' as a POST variable. This program then uses
    // authkey to request of REDCap which authorized user invoked this
    // program, if any.

    $record = isset($_GET['record']) ? 
      htmlspecialchars($_GET['record']) : '';

    $authkey = isset($_POST['authkey']) ? 
      htmlspecialchars($_POST['authkey']) : '';

    //------------------------------------------------------------------------
    // Initialize variables
    //------------------------------------------------------------------------
    $this->record_id = $record;
    $this->authkey = $authkey;
    $this->username = '';

    $this->viz_html = '';
    $this->bottom_html = '';


    //-------------------------------------------------------------------------
    // Authenticate
    //-------------------------------------------------------------------------

    // Use REDCap's Advanced Bookmark. REDCap sends a POST variable,'authkey'.
    // This program sends that authkey back to the REDCap API,
    // and if it is a valid key for a user that is still logged
    // in to REDCap, REDCap sends back the username. 

    // This program doesn't care _which_ user is authenticated,
    // as any user that could authenticate this way is authorized.
    $body_array = $this->proj_spade->check_advanced_link_auth($this->authkey);

    if (is_array($body_array) && isset($body_array['username'])) {
      $this->username = $body_array['username'];
    }

    //------------------------------------------------------------------------
    // Pageheader
    //------------------------------------------------------------------------
    $this->pageheader();

    if ($this->debug == 'yes') { print "Date: ".$date."<br/>\r\n"; }

    //-------------------------------------------------------------------------
    // Validate user is authorized
    //-------------------------------------------------------------------------

    if ('yes' == $this->debug) { 
      print "BEGIN Validate user is authorized<br/>"; }

    if (empty($this->username)) {

      $this->bottom_html.= 
	"Unable to validate that you have an active REDCap session and are authorized for this project.\n";
    }

    // User is authorized
    else {

      if ('yes' == $this->debug) { 
	print "END validate user is authorized<br/>"; }

      //-----------------------------------------------------------------------
      // Visualize T-Scores
      //-----------------------------------------------------------------------
      if ('yes' == $this->debug) { print "BEGIN visualize T-Scores<br/>"; }
  

      // Retrieve scores
      $results = 
	$this->proj_spade->get_records_by_ids('Unable to retrieve record',
					      array($this->record_id));
      if (count($results) <> 1) {
	$this->viz_html = "<h2>Wrong number of records found.</h2>\n";
      }

      else {

	$this->studyid = $results[0]['studyid'];

	$this->tscores = array(
			       'fatigue' => $results[0][T_FATIGUE],
			       'pain' => $results[0][T_PAIN],
			       'sleep' => $results[0][T_SLEEP],
			       'anxiety' => $results[0][T_ANXIETY],
			       'depression' => $results[0][T_DEPRESSION]
			 );

	$this->viz_html = $this->create_visualization();
      }



      if ('yes' == $this->debug) { print "END visualize T-Scores<br/>"; }


    }

    //-------------------------------------------------------------------------
    // Show main content
    //-------------------------------------------------------------------------
    $this->show_maincontent();
    
    //------------------------------------------------------------------------
    // Pagefooter
    //------------------------------------------------------------------------
    $this->pagefooter();

  }

  //===========================================================================
  // Helper Functions
  //===========================================================================

  //-------------------------------------------------------------------------
  // pageheader
  //
  protected function pageheader() {

    ?>
    <html>
      <head>
        <link rel="stylesheet" type="text/css" href="ctp.css" media="screen" />
      </head>

      <body bgcolor=white>

        <div id="header">
          SPADE Visualizer
        </div>

        <div id="maincontent">

      <?php
    return true;
  }
  // END pageheader
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // pagefooter
  //
  protected function pagefooter() {

    ?>

    </div> <!-- maincontent -->

    <div id="bottombar">&nbsp</div>

    </body></html>
    <?php

    return true;
  }
  // END pagefooter
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // show_maincontent
  //
  protected function show_maincontent() {

    ?>
    <table id="maintable">
      <tr><td><?php echo $this->viz_html; ?></td></tr>
      <tr><td><?php echo $this->bottom_html; ?></td></tr>
    </table>
    <?php

    return true;
  }
  // END show_maincontent
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // create_visualization
  //
  // Creates the HTML for visualizing the T Scores
  //
  protected function create_visualization() {

    $html = '';

    // Start/End x and y coordinates are determined dynamically based on
    // how much x and y space has already been used by previous objects,
    // moving down from the top and moving over from the left.

    // For each object or type of object below, the first section sets
    // the configuration of heights and widths for that object. The
    // second section calculates the Start/End x and y coordinates to use
    // for those objects. 

    // After all of the calculations have been made, HTML is created for
    // every object. All of the calculations have to be made before 
    // starting HTML creation because the svg object's width and height
    // can't be determined until everything else has been determined.

    // Variables that end in _x or _y refer to absolute coordinates.
    // Variables that end in _height or _width refer to a size for
    // an object. For instance, the height of a rectangle might be
    // 15 pixels tall.
    // Variables that end in _start refer to the first absolute coordinate
    // in a series of absolute coordinates. So if six rectangles will
    // need to be drawn, we might record the first absolute coordinates
    // and then in a loop add the heights and widths as needed.

    //----------------------------------------------------------------------
    // Begin configuring widths and heights, as well as others,
    // plus calculating x and y coordinates
    //----------------------------------------------------------------------

    $num_categories = count($this->tscores);

    // Starting absolute coordinates
    $cur_x = 0;               // Pixels from left
    $cur_y = 0;               // Pixels from top

    // Margins
    $margin_top_height = 25;
    $margin_left_width = 25;
    $margin_bottom_height = 25;
    $margin_right_width = 25;

    $cur_y = $cur_y + $margin_top_height;
    $cur_x = $cur_x + $margin_left_width;

    //-------------------------
    // Find Y coordinates
    $title = "Subject: ". $this->studyid;
    $title_font_size = '16pt';
    $title_height = 30;

    $title_y = round($cur_y + ($title_height * 0.5));
    $cur_y = $title_y;

    // Space above a scoring bar
    $category_offset_height = 25;  // Space before category bar

    // Height of scoring bar
    $category_bar_height = 15;

    // Height of categoring (space plus bar)
    $category_height = $category_offset_height + $category_bar_height;

    // Top of y-axis
    $yaxis_offset_height = $category_height;  // Space before top of midline
    $yaxis_top_y = $cur_y + $yaxis_offset_height;

    // Height that first bar starts
    $category_bar_y_start = $yaxis_top_y + $category_offset_height;

    // Height first category text starts
    $category_text_y_start = $yaxis_top_y + $category_height;

    // Bottom of y-axis, meeting the top of a hash mark
    $yaxis_bottom_y = 
      $yaxis_top_y + 
      ($category_height * $num_categories) +
      $category_offset_height;
    $cur_y = $yaxis_bottom_y;

    // Height of x-axis hash marks
    $hash_height = 30;

    // X-axis height
    $xaxis_y = $cur_y + round(0.5 * $hash_height);

    // Height hashes start and end
    $hash_top_y = $cur_y;
    $hash_bottom_y = $hash_top_y + $hash_height;

    // Height of hash text below bottom of hash
    $hash_text_offset_height = 15;
    $hash_text_y = $hash_bottom_y + $hash_text_offset_height;
    $cur_y = $hash_text_y;

    //-------------------------
    // Find x coordinates
    $title_x = $cur_x;

    // Left side of x-axis and start of category text
    $category_text_offset_width = 25;   // Offset from start of Title
    $category_text_x = $cur_x + $category_text_offset_width;
    $cur_x = $category_text_x;

    $xaxis_offset_width = 125; // Offset from start of category text
    $xaxis_left_x = $cur_x + $xaxis_offset_width;
    $cur_x = $xaxis_left_x;

    // Distance between hash marks
    $hash_width = 100;
    $hash_score_range = 10; // Number of scores between hashes

    // X Coord of first hash
    $hash_x_start = $cur_x;
    
    // X Coord of first hash text
    $hash_text_offset_width = -9;  // Offset from X coord of hash
    $hash_text_x_start = $hash_x_start + $hash_text_offset_width;

    // X Coord of y-axis
    $yaxis_x = $xaxis_left_x;

    // Right side of x-axis
    $xaxis_right_x = $xaxis_left_x + ($hash_width * 8);
    $cur_x = $xaxis_right_x;

    // line55
    $line55_x = round($xaxis_left_x + ($hash_width * 8 * (55/80)));

    // Start of Score text
    $score_offset = 5;  // Offset from end of category_bar

    // Blackout range of line55 -- the x coords that we can't put
    // a score in due to overlapping the line55.
    $score_width = 25;
    $line55blackout_left_x = $line55_x - $score_width;
    $line55blackout_right_x = $line55_x + $score_offset;

    $width_total = $cur_x + $margin_right_width;
    $height_total = $cur_y + $margin_bottom_height;

    // Add some extra width in case a score is marked as being high
    $width_total += 25;
    
    //----------------------------------------------------------------------
    // Creat HTML
    //----------------------------------------------------------------------

    // Start Scalar Vector Graphic object
    $html .= '<svg width="'. $width_total . '" '.
      'height="' . $height_total . '">'."\n";

    // Define pattern for high scoring category
    $html .= '<defs>'."\n".
      '<pattern id="diagonalHatch" width="10" height="10" '.
      'patternTransform="rotate(45 0 0)" patternUnits="userSpaceOnUse">'."\n".
      '<line x1="0" y1="0" x2="0" y2="10" '.
      'style="stroke:black; stroke-width:1" />'."\n".
      '</pattern>'."\n".
      '</defs>'."\n";

    // Title
    $html .= '<text x="'. $title_x .'" '.
      'y="'. $title_y .'" '.
      'font-size="' . $title_font_size .'" '.
      'fill="black">'. $title . "</text>\n";
    
    // Y-Axis
    $html .=
      '<line '.
      'x1="' . $yaxis_x . '" '.
      'x2="' . $yaxis_x . '" '.
      'y1="' . $yaxis_top_y . '" '.
      'y2="' . $yaxis_bottom_y. '" '.
      'style="stroke:rgb(0,0,0);stroke-width:2;"/>'."\n";

    // X-Axis
    $html .=
      '<line '.
      'x1="' . $xaxis_left_x . '" '.
      'x2="' . $xaxis_right_x . '" '.
      'y1="' . $xaxis_y . '" '.
      'y2="' . $xaxis_y. '" '.
      'style="stroke:rgb(0,0,0);stroke-width:2"/>'."\n";

    // Hash Marks
    $cur_hash_x = $hash_x_start;
    $cur_hash_text_x = $hash_text_x_start;
    for ($hash_label = 0; $hash_label <= 70; $hash_label += 10) {

      // Create the hash line
      $html .=
	'<line '.
	'x1="' . $cur_hash_x . '" '.
	'x2="' . $cur_hash_x . '" '.
	'y1="' . $hash_top_y . '" '.
	'y2="' . $hash_bottom_y. '" '.
	'style="stroke:rgb(0,0,0);stroke-width:2"/>'."\n";

      // Create the hash label
      $html .=
	'<text '.
	'x="' . $cur_hash_text_x . '" '.
	'y="' . $hash_text_y . '" '.
	'font-weight="bold" fill="black">' . $hash_label . "</text>\n";

      // Update X values
      $cur_hash_x += $hash_width;
      $cur_hash_text_x += $hash_width;
    }

    // Create the hash line for the 55 line
    $html .=
      '<line '.
      'x1="' . $line55_x . '" '.
      'x2="' . $line55_x . '" '.
      'y1="' . $hash_top_y . '" '.
      'y2="' . $hash_bottom_y. '" '.
      'style="stroke:rgb(0,0,0);stroke-width:2"/>'."\n";
    
    // Create the hash label for the 55 line
    $html .=
      '<text '.
      'x="' . ($line55_x + $hash_text_offset_width) . '" '.
      'y="' . $hash_text_y . '" '.
      'font-weight="bold" fill="black">55' . "</text>\n";

    // Categories
    $cur_category_text_y = $category_text_y_start;
    $cur_category_bar_y = $category_bar_y_start;
    foreach ($this->tscores as $category => $score) {

      $category_bar_style =
	'style="fill:rgb(230,230,230);stroke-width:1;stroke:rgb(0,0,0)"';
      $score_attributes = 'fill="black"';

      // Round to nearest integer
      $score = round($score);

      // Create the category label
      $html .=
	'<text '.
	'x="' . $category_text_x . '" '.
	'y="' . $cur_category_text_y . '" '.
	'font-weight="bold" fill="black">' . strtoupper($category) . 
	"</text>\n";

      // Create the Bar
      $cur_category_bar_width = round(
				      abs($score ) *
				      ($hash_width / $hash_score_range)
				      );
      $cur_category_bar_x = $yaxis_x;

      $html .=
	'<rect '.
	'x="' . $cur_category_bar_x . '" '.
	'y="' . $cur_category_bar_y . '" '.
	'width="' . $cur_category_bar_width . '" '.
	'height="' . $category_bar_height . '" '.
	$category_bar_style . "/>\n";

      // If the score is high, also create a cross pattern
      if ($score >= 55) {
	$category_bar_high_style =
	  'style="fill:url(#diagonalHatch);stroke-width:1;stroke:rgb(0,0,0);'.
	  'fill-opacity=0"';

	$html .=
	  '<rect '.
	  'x="' . $cur_category_bar_x . '" '.
	  'y="' . $cur_category_bar_y . '" '.
	  'width="' . $cur_category_bar_width . '" '.
	  'height="' . $category_bar_height . '" '.
	  $category_bar_high_style . "/>\n";
      }

      // Create the Score
      $cur_score_x = 
	$cur_category_bar_x + $cur_category_bar_width + $score_offset;

      // Make sure Sore is not over line55
      if (($cur_score_x > $line55blackout_left_x) &&
	  ($cur_score_x < $line55blackout_right_x)) {
	$cur_score_x = $line55blackout_right_x;
      }

      $score_text = round($score);
      if ($score_text >= 55) {	$score_text .= ' (HIGH)'; }

      $html .=
	'<text '.
	'x="' . $cur_score_x . '" '.
	'y="' . $cur_category_text_y . '" '.
	$score_attributes . '>' . $score_text . "</text>\n";

      // Update Y values
      $cur_category_text_y += $category_height;
      $cur_category_bar_y += $category_height;
    }

    // 55-line
    $html .=
      '<line '.
      'x1="' . $line55_x . '" '.
      'x2="' . $line55_x . '" '.
      'y1="' . $yaxis_top_y . '" '.
      'y2="' . $yaxis_bottom_y. '" '.
      'style="stroke:rgb(0,0,0);stroke-width:2;"/>'."\n";

    // End Scalar Vector Graphic object
    $html .= "</svg>\n";

    return $html;
  }
  // END create_visualization
  //-------------------------------------------------------------------------


}
// END of class Visualizer
//-------------------------------------------------------------------------

new Visualizer(__FILE__);

?>
