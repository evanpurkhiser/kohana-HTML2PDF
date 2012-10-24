<?php

class Kohana_HTML2PDF {

	/**
	 * Get an instance of the WkHTML class
	 *
	 * @param  string  $html  The HTML to render to a PDF
	 * @return Wkhtml
	 */
	static public function document($html = NULL)
	{
		// Find the binary file
		if ( ! $binary = trim(`which wkhtmltopdf`))
			throw new Kohana_Exception("wkhtmltopdf must be installed on this system");

		// Create an instance of the wkhtml pdf geneartor
		$wkhtml = new HTML2PDF_Document($binary);

		// Set the HTML body of the PDF
		return $wkhtml->body($html);
	}

}
