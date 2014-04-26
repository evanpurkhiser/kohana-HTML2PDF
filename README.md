### HTML2PDF Document converter

HTML2PDF is a Kohana 3.x module that can be used to convert a HTML document into
a PDF document. This module uses the [wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/)
as a back-end to handle the conversions.

#### Requirements

You must have the [wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/) static
binary (at least version 0.10 RC1) installed onto your machine and in the PATH
read by the user executing the PHP application

#### Usage

You can create a PDF document from HTML like so (Optionally specifying the HTML content)

    $document = HTML2PDF::document($html)

From here you can apply some different formatting options to the document including

 * `$document->body($html)` - Set the HTML body content of the PDF
 * `$document->header($html)` - Set the HTML to render at the top of each page
 * `$document->header_spacing($spacing)` - Set the spacing between the content and header
 * `$document->margins($top, $left, $bottom, $right)` - Set the margins for the document (in millimeters)

See the [class itself](classes/Kohana/HTML2PDF/Document.php) for all the available methods.

Finally you can then render the document as a PDF two different ways. You can
eaither save the PDF to a file on the file-system, or you can convert the PDF
and have the PDF BLOB returned.

    // Save to the filesystem
    $document->save('/home/evan/document.pdf');

    // Save to filesystem and return temporary file path
    $temp_path = $document->save();

    // Convert and put the BLOB into a variable
    $pdf_blob = $document->convert();
