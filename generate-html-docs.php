<?php 

require_once('vendor/autoload.php');

/**
 * Code to generate HTML documents from the Markdown documents.
 *
 */

class MyParsedown extends \Parsedown
{
    protected $highlighter;
    protected $minimalHighlighter;

    public function __construct()
    {
        $this->highlighter = new \FSHL\Highlighter(new \FSHL\Output\Html());
        $this->highlighter->setLexer(new \FSHL\Lexer\Php());
        
        $this->minimalHighlighter = new \FSHL\Highlighter(new \FSHL\Output\Html());
        $this->minimalHighlighter->setLexer(new \FSHL\Lexer\Minimal());
    }

    public function blockFencedCodeComplete($block)
    {
        $text = print_r($block, true);
        
        $matches = array();
        preg_match('/\[class\] => language-([a-zA-Z]+)/', $text, $matches);
        
        if (count($matches) > 1 && $matches[1] === 'shell') {
            $block['element']['text']['text'] = $this->minimalHighlighter->highlight($block['element']['text']['text']);
        }
        else {
            $block['element']['text']['text'] = $this->highlighter->highlight($block['element']['text']['text']);
        }

        return $block;
    }

}


function translateFile($file)
{
    $parsedown = new MyParsedown();
    
    $markdown = file_get_contents($file);
    
    $content = $parsedown->text($markdown);
    
    $content = str_replace('<pre>', '<div class="description"><pre>', $content);
    $content = str_replace('</pre>', '</pre></div>', $content);
    
    $html = "<!DOCTYPE html>\n" . "<html>\n" . "<head>\n" . '<meta charset="UTF-8">' . "\n"
            . '<link rel="stylesheet" href="' . __DIR__ . '/docs/themes/apigen/theme-phpcap/src/resources/style.css">' . "\n"
            . '<link rel="stylesheet" href="' . __DIR__ . '/docs/themes/apigen/theme-phpcap/src/resources/docstyle.css">' . "\n"
            . "<title>PHPCap Documentation</title>\n" 
            . "</head>\n" . "<body>\n" . '<div id="content">' . "\n"
            . $content
            . '</div>' . "\n" 
            . '<div id="footer">' 
            . "\n" . 'PHPCap documentation' . "\n" . '</div>' . "\n"
            //. '<script src="' . __DIR__ . '/docs/api/resources/combined.js"></script>'. "\n" 
            //. '<script src="' . __DIR__ . '/docs/api/elementlist.js"></script>' . "\n"
            . "</body>\n" . "</html>\n";
    
    $outputFile =  pathinfo($file, PATHINFO_DIRNAME).'/'.pathinfo($file, PATHINFO_FILENAME).".html";
    $outputFile = str_replace('docs-md', 'docs', $outputFile);
    
    print "{$outputFile}\n";
    
    file_put_contents($outputFile, $html);
}





$inputDirectory  = __DIR__."/docs-md/";
$outputDirectory = __DIR__."/docs/";

$inputResources  = $inputDirectory.'/resources/';
$outputResources = $outputDirectory.'/resources/';

# Create the html resources directory if it doesn't already exist
if (!file_exists($outputResources)) {
    mkdir($outputResources);
}

# Copy the Markdown resources to the HTML resources directory
$resources = glob($inputResources . "*");
foreach ($resources as $resource) {
    $dest = str_replace('docs-md', 'docs', $resource);
    copy($resource, $dest);
}

# Process each Markdown file
$files = glob($inputDirectory . "*.md");
foreach($files as $file)
{
    print "\nTranslating\n$file\n";
    translateFile( $file );
}

print "\nDone.\n";

