<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @author     Rowan Parker
 */
class Wkhtml
{
    static public function topdf($data, $download = FALSE)
    {
        $bin = Kohana::config('wkhtml.paths.bin');
        
        if ( ! file_exists($bin)) {
            throw new Kohana_Exception('wkhtml binary does not exist at: '.$bin);
        }
        
        // Create unique temporary file
        $uuid = uniqid('wkhtml_temp_', TRUE);
        
        // Store working files in cache
        $folder = Kohana::config('wkhtml.paths.temp');
        
        $file_in  = $folder . $uuid . '.html';
        $file_out = $folder . $uuid . '.pdf';
        
        // Write temporary file
        file_put_contents($file_in, $data);
        
        // Build command
        $cmd = $bin . ' ' . escapeshellarg($file_in) . ' ' . escapeshellarg($file_out);
        
        // Convert file
        passthru($cmd);
        
        // Delete HTML file
        unlink($file_in);
                
        // Handle any errors
        if ( ! file_exists($file_out)) {
            throw new Kohana_Exception('Unknown wkhtmltopdf error.');
        }
        
        // Force PDF download or return cache ID
        if ($download) {
            $filename = (is_string($download)) ? $download : 'print.pdf';
            
            Request::current()->response()->send_file($file_out, $filename);
        }
        
        return $file_out;
    }
}
