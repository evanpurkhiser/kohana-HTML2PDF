<?php

class Wkhtml {

	/**
	 * Get an instance of the WkHTML class
	 *
	 * @param  string  $html  The HTML to render to a PDF
	 * @return Wkhtml
	 */
	static public function factory($html)
	{
		if ( ! $binary = trim(`which wkhtmltopdf`))
			throw new Kohana_Exception("wkhtmltopdf must be installed on this system");

		return new Wkhtml($binary, $html);
	}

	/**
	 * The partial command to conver the binary
	 */
	protected $_command;

	/**
	 * Setup the wkHTML object for actions
	 *
	 * @param  string  $binary The path to the wkhtmltopdb binary
	 * @param  string  $html   The HTML to render as a PDF
	 */
	public function __construct($binary, $html)
	{
		$this->_command = strtr("echo :html | :binary -q - :save", array(
			':binary' => $binary,
			':html'   => escapeshellarg($html),
		));
	}

	/**
	 * Save the document as a PDF
	 *
	 * @return  string  The path to the new PDF
	 */
	public function save($path)
	{
		// Ensure the directory is writeable
		if ( ! is_writable(dirname($path)))
			throw new Kohana_Exception("Unable to save PDF, path is not writeable");

		// Save the PDF to the given path
		exec(strtr($this->_command, array(':save' => $path)));

		return $path;
	}

	/**
	 * Render the PDF to the client browser
	 *
	 * @param  string  $download   Force the client to download the file
	 * @param  string  $file_name  The default filename
	 */
	public function render($download = FALSE, $file_name = 'document.pdf')
	{
		// Get the current response
		$response = Request::current()->response();

		// Setup the content disposition for the pdf file
		$disposition = ($download === FALSE) ? 'inline' : 'attachment';
		$response->headers('content-disposition', "{$disposition}; filename={$file_name}");

		// Content type should always be PDF
		$response->headers('content-type', 'application/pdf');
		$response->send_headers();

		// Pass the output through STDOUT
		passthru(strtr($this->_command, array(':save' => '-')));
		exit;
	}

}
