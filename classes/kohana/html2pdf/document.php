<?php

class Kohana_HTML2PDF_Document {

	/**
	 * The location of the wkhtmltopdf binary
	 */
	protected $_binary;

	/**
	 * Temproray file locations
	 */
	protected $_tmp_files = array();

	/**
	 * Extra options for the wkhtmltopdf binary
	 *
	 * @link http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltopdf-0.9.9-doc.html
	 */
	protected $_options;

	/**
	 * The HTML contents of the PDF
	 */
	protected $_body;

	/**
	 * Save the location of the wkhtmltopdf binary
	 *
	 * @param string $binary The path to the wkhtmltopdb binary
	 */
	public function __construct($binary)
	{
		// Store the location of the wkhtmltopdf binary
		$this->_binary = $binary;
	}

	/**
	 * Unlink the temporary files on destruction
	 */
	public function __destruct()
	{
		foreach ($this->_tmp_files as $file)
		{
			@unlink($file);
		}
	}

	/**
	 * Set the HTML body of the PDF
	 *
	 * @param  string $html The HTML string
	 * @return HTML2PDF_Document
	 */
	public function body($html)
	{
		$this->_body = (string) $html;

		return $this;
	}

	/**
	 * Set the HTML Header for each page of the PDF
	 *
	 * @param  string $html  The HTML string
	 * @return HTML2PDF_Document
	 */
	public function header($html)
	{
		// Add the temporary header file to the options
		$this->_options['header-html'] = $this->make_temp_file((string) $html);

		return $this;
	}

	/**
	 * Set the space between the document header and content
	 *
	 * @param  int    $spacing The spacing
	 * @return HTML2PDF_Document
	 */
	public function header_spacing($spacing = 0)
	{
		$this->_options['header-spacing'] = $spacing;

		return $this;
	}

	/**
	 * Set the margin spacing on the document in millimeters\
	 * this may be short-handed to CSS style as well
	 *
	 * @param  int    $top    The top margin
	 * @param  int    $left   The left margin
	 * @param  int    $bottom The bottom margin
	 * @param  int    $right  The right margin
	 * @return HTML2PDF_Document
	 */
	public function margins($top = NULL, $left = NULL, $bottom = NULL, $right = NULL)
	{
		// Set all margins to one size
		if ($left === NULL AND $bottom === NULL AND $right === NULL)
		{
			$this->_options['margin-top']    = $top.'mm';
			$this->_options['margin-left']   = $top.'mm';
			$this->_options['margin-bottom'] = $top.'mm';
			$this->_options['margin-right']  = $top.'mm';
		}

		// Set the top and left sizez
		elseif ($bottom === NULL AND $right === NULL)
		{
			$this->_options['margin-top']    = $top.'mm';
			$this->_options['margin-bottom'] = $top.'mm';
			$this->_options['margin-left']   = $left.'mm';
			$this->_options['margin-right']  = $left.'mm';
		}

		// Set all of the sides individually
		else
		{
			$this->_options['margin-top']    = $top.'mm';
			$this->_options['margin-left']   = $left.'mm';
			$this->_options['margin-bottom'] = $bottom.'mm';
			$this->_options['margin-right']  = $right.'mm';
		}

		return $this;
	}

	/**
	 * Set the page size of the document
	 *
	 * @link http://doc.trolltech.com/4.6/qprinter.html#PageSize-enum
	 *
	 * @param  string $size A valid page size
	 * @return HTML2PDF_Document
	 */
	public function page_size($size)
	{
		$this->_options['page-size'] = $size;

		return $this;
	}

	/**
	 * Save the document as a PDF
	 *
	 * @param string $path Where to save the file
	 */
	public function save($path=NULL)
	{
		// If path supplied, ensure the directory is writeable
		if ($path)
		{			
			if ( ! is_writable(dirname($path)))
				throw new Kohana_Exception("Unable to save PDF, path is not writeable");
		}
		// No path supplied, use temp file
		else
		{
			$path = $this->make_temp_file(NULL,'pdf');
		}
		
		// Save the PDF to the given path
		return $this->convert_to_pdf($path);
	}

	/**
	 * Render the PDF to the client browser
	 *
	 * @param string $download  Force the client to download the file
	 * @param string $file_name The name of the file to download
	 */
	public function render($download = FALSE, $file_name = FALSE)
	{
		// Response::send_file() options
		$options = array('mime_type' => 'application/pdf');

		// Display the document inline
		if ($download === FALSE)
		{
			$options['inline'] = TRUE;
		}

		// Send the PDF file to the client
		Request::current()
			->response()
			->send_file($this->convert_to_pdf(), $file_name, $options);
	}

	/**
	 * Build the command to generate the PDF
	 *
	 * @param  string $save_path PDF save location, defaults to STDOUT
	 * @return string            The path to the PDF file
	 */
	protected function convert_to_pdf($save_path = FALSE)
	{
		// Always force quiet mode
		$this->_options['quiet'] = NULL;

		// Never add an outline (http://code.google.com/p/wkhtmltopdf/issues/detail?id=600)
		$this->_options['no-outline'] = NULL;

		// Generate the options array
		$options = array();

		foreach ($this->_options as $key => $value)
		{
			if ( ! empty($value))
			{
				$value = escapeshellarg($value);
			}

			$options[] = trim("--{$key} {$value}");
		}

		// Save the PDF to a temporary file if there is no save path
		if ($save_path === FALSE)
		{
			$save_path = $this->make_temp_file('', 'pdf');
		}

		// Create input file. String input can fail when it's big
		$input_file = $this->make_temp_file($this->_body);		
		
		// Compile the command to generate the PDF
		$command = strtr(":binary :options :file :save", array(
			':binary'  => $this->_binary,
			':file'    => $input_file,
			':options' => implode(' ', $options),
			':save'    => $save_path,
		));

		// Setup the file descriptors specification
		$descriptsspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		// Store the pipes in this array
		$pipes = array();

		// Execute the command
		$resource = proc_open($command, $descriptsspec, $pipes);

		// Get the output from the pipes
		$output = array(
			1 => trim(stream_get_contents($pipes[1])),
			2 => trim(stream_get_contents($pipes[2])),
		);

		// Close the pipes
		array_map('fclose', $pipes);

		// Throw an exception if we returned with a non-zero value
		if (trim(proc_close($resource)))
			throw new Kohana_Exception(':error [:options]', array(
				':error'   => strstr($output[2], "\n", TRUE),
				':options' => implode(', ', $options),
			));

		return $save_path;
	}

	/**
	 * Generate a temporary file that will be removed upon
	 * destruction of this object.
	 *
	 * Although we are able to use the STDOUT pipe for the
	 * main body of the PDF document, for other options that
	 * require a HTML document we must pass a temporary .html
	 * file. Process substitution does NOT work with wkhtmltopdf
	 *
	 * @param  string $contents The contents of the temporary file
	 * @return string           The temporary file name
	 */
	protected function make_temp_file($contents, $ext = 'html')
	{
		// Get the name of the temporary file
		$location = sys_get_temp_dir().'/'.uniqid().'.'.$ext;

		// Store the name of the temproray file to be removed
		$this->_tmp_files[] = $location;

		// Save the file contents
		file_put_contents($location, $contents);

		return $location;
	}

}
